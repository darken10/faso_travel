<?php

namespace App\Services\Compagnie;

use App\DTOs\Compagnie\CompagnieSettings;
use App\DTOs\Compagnie\SettingDefinition;
use App\Enums\CompagnieSettingGroup;
use App\Enums\CompagnieSettingKey;
use App\Enums\CompagnieSettingType;
use App\Models\Compagnie\Compagnie;
use App\Models\CompagnieSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Point d'entrée unique du paramétrage des compagnies.
 *
 * Résout chaque paramètre en combinant la valeur par défaut du catalogue et
 * l'éventuelle valeur propre à la compagnie, met le résultat en cache et
 * valide toute écriture contre la définition du catalogue.
 */
class CompagnieSettingService
{
    private const CACHE_PREFIX = 'compagnie_settings:';

    private const CACHE_TTL = 3600;

    /**
     * Toutes les valeurs effectives d'une compagnie, indexées par clé.
     *
     * @return array<string, mixed>
     */
    public function all(Compagnie|int $compagnie): array
    {
        $compagnieId = $this->resolveId($compagnie);

        return Cache::remember(
            self::CACHE_PREFIX.$compagnieId,
            self::CACHE_TTL,
            fn () => $this->resolveAll($compagnieId),
        );
    }

    /** Valeur effective d'un paramètre. */
    public function get(Compagnie|int $compagnie, CompagnieSettingKey|string $key, mixed $default = null): mixed
    {
        $resolved = $key instanceof CompagnieSettingKey ? $key : CompagnieSettingKey::tryFrom($key);

        if (! $resolved) {
            return $default;
        }

        $value = $this->all($compagnie)[$resolved->value] ?? null;

        return $value ?? $default ?? $resolved->default();
    }

    /** Objet d'accès typé aux paramètres d'une compagnie. */
    public function bag(Compagnie|int $compagnie): CompagnieSettings
    {
        return new CompagnieSettings($this->all($compagnie));
    }

    /**
     * Écrit un paramètre après validation.
     *
     * @throws ValidationException
     */
    public function set(Compagnie|int $compagnie, CompagnieSettingKey $key, mixed $value, bool $allowAdminOnly = false): void
    {
        $this->setMany($compagnie, [$key->value => $value], $allowAdminOnly);
    }

    /**
     * Écrit plusieurs paramètres en une transaction, après validation globale.
     *
     * @param  array<string, mixed>  $values  Indexé par clé de paramètre.
     * @return array<string, mixed> Les valeurs validées effectivement écrites.
     *
     * @throws ValidationException
     */
    public function setMany(Compagnie|int $compagnie, array $values, bool $allowAdminOnly = false): array
    {
        $compagnieId = $this->resolveId($compagnie);
        $validated = $this->validate($values, $allowAdminOnly);

        DB::transaction(function () use ($compagnieId, $validated) {
            foreach ($validated as $key => $value) {
                $definition = CompagnieSettingKey::from($key)->definition();

                CompagnieSetting::updateOrCreate(
                    ['compagnie_id' => $compagnieId, 'key' => $key],
                    [
                        'value' => $definition->type->serialize($value),
                        'type'  => $definition->type->value,
                    ],
                );
            }
        });

        $this->forget($compagnieId);

        return $validated;
    }

    /**
     * Aligne le paramétrage stocké sur les valeurs fournies.
     *
     * Contrairement à {@see setMany()}, une valeur identique au défaut du
     * catalogue ne crée pas de ligne — et supprime celle qui existait. La table
     * ne contient donc que les écarts réels au défaut.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed> Les valeurs validées.
     *
     * @throws ValidationException
     */
    public function sync(Compagnie|int $compagnie, array $values, bool $allowAdminOnly = false): array
    {
        $compagnieId = $this->resolveId($compagnie);
        $validated = $this->validate($values, $allowAdminOnly);

        DB::transaction(function () use ($compagnieId, $validated) {
            $aSupprimer = [];

            foreach ($validated as $key => $value) {
                $definition = CompagnieSettingKey::from($key)->definition();
                $serialized = $definition->type->serialize($value);

                if ($serialized === $definition->serializedDefault()) {
                    $aSupprimer[] = $key;

                    continue;
                }

                CompagnieSetting::updateOrCreate(
                    ['compagnie_id' => $compagnieId, 'key' => $key],
                    ['value' => $serialized, 'type' => $definition->type->value],
                );
            }

            if ($aSupprimer !== []) {
                CompagnieSetting::forCompagnie($compagnieId)->whereIn('key', $aSupprimer)->delete();
            }
        });

        $this->forget($compagnieId);

        return $validated;
    }

    /** Supprime la valeur propre à la compagnie : le paramètre revient à son défaut. */
    public function reset(Compagnie|int $compagnie, CompagnieSettingKey $key): void
    {
        $compagnieId = $this->resolveId($compagnie);

        CompagnieSetting::forCompagnie($compagnieId)->where('key', $key->value)->delete();

        $this->forget($compagnieId);
    }

    /** Réinitialise tout un groupe de paramètres. */
    public function resetGroup(Compagnie|int $compagnie, CompagnieSettingGroup $group): void
    {
        $compagnieId = $this->resolveId($compagnie);
        $keys = array_map(fn (CompagnieSettingKey $key) => $key->value, CompagnieSettingKey::inGroup($group));

        CompagnieSetting::forCompagnie($compagnieId)->whereIn('key', $keys)->delete();

        $this->forget($compagnieId);
    }

    /** Réinitialise l'intégralité du paramétrage d'une compagnie. */
    public function resetAll(Compagnie|int $compagnie): void
    {
        $compagnieId = $this->resolveId($compagnie);

        CompagnieSetting::forCompagnie($compagnieId)->delete();

        $this->forget($compagnieId);
    }

    /**
     * Clés effectivement personnalisées par la compagnie (par opposition aux défauts).
     *
     * @return array<int, string>
     */
    public function customizedKeys(Compagnie|int $compagnie): array
    {
        return CompagnieSetting::forCompagnie($this->resolveId($compagnie))
            ->pluck('key')
            ->all();
    }

    /**
     * Sous-ensemble exposable aux clients non authentifiés (application mobile).
     *
     * @return array<string, mixed>
     */
    public function publicSettings(Compagnie|int $compagnie): array
    {
        $all = $this->all($compagnie);

        return collect(CompagnieSettingKey::cases())
            ->filter(fn (CompagnieSettingKey $key) => $key->definition()->isPublic())
            ->mapWithKeys(fn (CompagnieSettingKey $key) => [$key->value => $all[$key->value] ?? $key->default()])
            ->all();
    }

    /**
     * Valide un lot de valeurs contre le catalogue.
     *
     * Les clés inconnues sont ignorées ; les paramètres réservés aux
     * administrateurs sont rejetés sauf autorisation explicite.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $values, bool $allowAdminOnly = false): array
    {
        $rules = [];
        $attributes = [];
        $payload = [];

        foreach ($values as $key => $value) {
            $settingKey = CompagnieSettingKey::tryFrom((string) $key);

            if (! $settingKey) {
                continue;
            }

            $definition = $settingKey->definition();

            if ($definition->isAdminOnly() && ! $allowAdminOnly) {
                throw ValidationException::withMessages([
                    $key => "Le paramètre « {$definition->label} » est réservé aux administrateurs.",
                ]);
            }

            $payload[$key] = $this->normalize($definition, $value);
            $rules[$key] = $definition->validationRules();
            $attributes[$key] = $definition->label;

            if ($itemRules = $definition->itemValidationRules()) {
                $rules["{$key}.*"] = $itemRules;
                $attributes["{$key}.*"] = $definition->label;
            }
        }

        if ($payload === []) {
            return [];
        }

        return Validator::make($payload, $rules, [], $attributes)->validate();
    }

    /**
     * Règles de validation d'un groupe, préfixées pour un formulaire Livewire.
     *
     * @return array<string, array<int, string>>
     */
    public function rulesForGroup(CompagnieSettingGroup $group, string $prefix = 'values'): array
    {
        $rules = [];

        foreach (CompagnieSettingKey::inGroup($group) as $key) {
            $definition = $key->definition();
            $rules["{$prefix}.{$key->value}"] = $definition->validationRules();

            if ($itemRules = $definition->itemValidationRules()) {
                $rules["{$prefix}.{$key->value}.*"] = $itemRules;
            }
        }

        return $rules;
    }

    /**
     * Définitions groupées, pour le rendu des panels.
     *
     * @return array<string, array{group: CompagnieSettingGroup, definitions: array<int, SettingDefinition>}>
     */
    public function catalogue(bool $includeAdminOnly = false): array
    {
        $groups = $includeAdminOnly ? CompagnieSettingGroup::cases() : CompagnieSettingGroup::forCompagnie();
        $catalogue = [];

        foreach ($groups as $group) {
            $definitions = array_values(array_filter(
                array_map(fn (CompagnieSettingKey $key) => $key->definition(), CompagnieSettingKey::inGroup($group)),
                fn (SettingDefinition $definition) => $includeAdminOnly || ! $definition->isAdminOnly(),
            ));

            if ($definitions !== []) {
                $catalogue[$group->value] = ['group' => $group, 'definitions' => $definitions];
            }
        }

        return $catalogue;
    }

    /** Invalide le cache d'une compagnie. */
    public function forget(Compagnie|int $compagnie): void
    {
        Cache::forget(self::CACHE_PREFIX.$this->resolveId($compagnie));
    }

    /**
     * Fusionne les défauts du catalogue avec les valeurs stockées.
     *
     * @return array<string, mixed>
     */
    private function resolveAll(int $compagnieId): array
    {
        $stored = CompagnieSetting::forCompagnie($compagnieId)->get()->keyBy('key');
        $resolved = [];

        foreach (CompagnieSettingKey::cases() as $key) {
            $row = $stored->get($key->value);
            $value = $row?->getCastedValue();

            $resolved[$key->value] = $value ?? $key->default();
        }

        return $resolved;
    }

    /** Aligne une valeur entrante sur le type attendu avant validation. */
    private function normalize(SettingDefinition $definition, mixed $value): mixed
    {
        if ($definition->type->isMultiple()) {
            return array_values(array_filter((array) $value, fn ($item) => $item !== null && $item !== ''));
        }

        if ($definition->type === CompagnieSettingType::Boolean) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        return $value === '' ? null : $value;
    }

    private function resolveId(Compagnie|int $compagnie): int
    {
        return $compagnie instanceof Compagnie ? (int) $compagnie->id : $compagnie;
    }
}
