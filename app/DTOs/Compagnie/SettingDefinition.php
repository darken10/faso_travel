<?php

namespace App\DTOs\Compagnie;

use App\Enums\CompagnieSettingGroup;
use App\Enums\CompagnieSettingKey;
use App\Enums\CompagnieSettingType;

/**
 * Décrit un paramètre de compagnie : son type, sa valeur par défaut,
 * ses règles de validation et la façon dont il est présenté dans les panels.
 */
final class SettingDefinition
{
    /**
     * @param  array<string, string>  $options   Valeurs autorisées pour les select/multiselect (valeur => libellé).
     * @param  array<int, string>     $rules     Règles de validation additionnelles.
     */
    public function __construct(
        public readonly CompagnieSettingKey $key,
        public readonly CompagnieSettingType $type,
        public readonly CompagnieSettingGroup $group,
        public readonly string $label,
        public readonly mixed $default,
        public readonly string $help = '',
        public readonly array $options = [],
        public readonly array $rules = [],
        public readonly string $suffix = '',
        public readonly bool $public = false,
        public readonly bool $adminOnly = false,
    ) {}

    /** Le paramètre n'est modifiable que par un administrateur de la plateforme. */
    public function isAdminOnly(): bool
    {
        return $this->adminOnly || $this->group->isAdminOnly();
    }

    /** Le paramètre est exposé aux applications clientes non authentifiées. */
    public function isPublic(): bool
    {
        return $this->public && ! $this->isAdminOnly();
    }

    /**
     * Règles de validation complètes (type + spécifiques + options).
     *
     * @return array<int, string>
     */
    public function validationRules(): array
    {
        $rules = array_merge(['nullable'], $this->type->baseRules(), $this->rules);

        if ($this->options !== [] && $this->type === CompagnieSettingType::Select) {
            $rules[] = 'in:'.implode(',', array_keys($this->options));
        }

        return array_values(array_unique($rules));
    }

    /**
     * Règles appliquées à chaque élément d'un paramètre multi-valeurs.
     *
     * @return array<int, string>|null
     */
    public function itemValidationRules(): ?array
    {
        if (! $this->type->isMultiple() || $this->options === []) {
            return null;
        }

        return ['string', 'in:'.implode(',', array_keys($this->options))];
    }

    /** Valeur par défaut sérialisée telle qu'elle serait stockée. */
    public function serializedDefault(): ?string
    {
        return $this->type->serialize($this->default);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key'        => $this->key->value,
            'type'       => $this->type->value,
            'group'      => $this->group->value,
            'label'      => $this->label,
            'help'       => $this->help,
            'default'    => $this->default,
            'options'    => $this->options,
            'suffix'     => $this->suffix,
            'public'     => $this->isPublic(),
            'admin_only' => $this->isAdminOnly(),
        ];
    }
}
