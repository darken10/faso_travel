<?php

namespace App\Traits;

use App\Enums\CompagnieSettingGroup;
use App\Enums\CompagnieSettingKey;
use App\Enums\CompagnieSettingType;
use App\Models\Compagnie\Compagnie;
use App\Services\Compagnie\CompagnieSettingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Logique commune aux écrans de paramétrage compagnie (panel compagnie et
 * panel administrateur). Le composant hôte fournit la compagnie ciblée ;
 * le trait gère chargement, validation, écriture et réinitialisation.
 */
trait ManagesCompagnieSettings
{
    public ?int $compagnieId = null;

    public string $activeGroup = 'general';

    /** @var array<string, mixed> */
    public array $values = [];

    /** @var array<int, string> */
    public array $customizedKeys = [];

    protected function settingService(): CompagnieSettingService
    {
        return app(CompagnieSettingService::class);
    }

    protected function compagnie(): ?Compagnie
    {
        return $this->compagnieId ? Compagnie::find($this->compagnieId) : null;
    }

    /** L'utilisateur peut-il toucher aux paramètres réservés à la plateforme ? */
    public function canManageAdvanced(): bool
    {
        return Gate::allows('compagnie-settings.updateAdvanced');
    }

    /** L'écran est-il en consultation seule ? */
    public function isReadOnly(): bool
    {
        $compagnie = $this->compagnie();

        return ! $compagnie || Gate::denies('compagnie-settings.update', $compagnie);
    }

    /** Charge les valeurs effectives de la compagnie ciblée dans le formulaire. */
    protected function loadSettings(): void
    {
        $compagnie = $this->compagnie();

        if (! $compagnie) {
            $this->values = [];
            $this->customizedKeys = [];

            return;
        }

        $this->authorizeView($compagnie);

        $this->values = $this->settingService()->all($compagnie);
        $this->customizedKeys = $this->settingService()->customizedKeys($compagnie);
        $this->ensureGroupIsVisible();
    }

    public function selectGroup(string $group): void
    {
        if (isset($this->catalogue()[$group])) {
            $this->activeGroup = $group;
            $this->resetErrorBag();
        }
    }

    /** Bascule un paramètre booléen depuis son interrupteur. */
    public function toggleSetting(string $key): void
    {
        $settingKey = CompagnieSettingKey::tryFrom($key);

        if (! $settingKey || $settingKey->type() !== CompagnieSettingType::Boolean) {
            return;
        }

        if ($this->isReadOnly() || ($settingKey->definition()->isAdminOnly() && ! $this->canManageAdvanced())) {
            return;
        }

        $this->values[$key] = ! filter_var($this->values[$key] ?? $settingKey->default(), FILTER_VALIDATE_BOOLEAN);
    }

    /** Enregistre l'ensemble des paramètres accessibles à l'utilisateur. */
    public function save(): void
    {
        $compagnie = $this->compagnie();

        if (! $compagnie) {
            $this->dispatch('toast', type: 'error', message: 'Aucune compagnie sélectionnée.');

            return;
        }

        Gate::authorize('compagnie-settings.update', $compagnie);

        try {
            $this->settingService()->sync($compagnie, $this->editableValues(), $this->canManageAdvanced());
        } catch (ValidationException $e) {
            $this->setErrorBagFrom($e);
            $this->dispatch('toast', type: 'error', message: 'Certains paramètres sont invalides.');

            return;
        }

        $this->loadSettings();
        $this->dispatch('toast', type: 'success', message: 'Paramètres enregistrés.');
    }

    /** Rétablit les défauts du groupe affiché. */
    public function resetGroup(): void
    {
        $compagnie = $this->compagnie();
        $group = CompagnieSettingGroup::tryFrom($this->activeGroup);

        if (! $compagnie || ! $group) {
            return;
        }

        Gate::authorize('compagnie-settings.reset', $compagnie);

        if ($group->isAdminOnly() && ! $this->canManageAdvanced()) {
            $this->dispatch('toast', type: 'error', message: 'Groupe réservé aux administrateurs.');

            return;
        }

        $this->settingService()->resetGroup($compagnie, $group);
        $this->loadSettings();
        $this->dispatch('toast', type: 'success', message: 'Valeurs par défaut rétablies.');
    }

    /**
     * Catalogue affiché : les groupes avancés n'apparaissent qu'aux administrateurs.
     *
     * @return array<string, array{group: CompagnieSettingGroup, definitions: array<int, \App\DTOs\Compagnie\SettingDefinition>}>
     */
    protected function catalogue(): array
    {
        return $this->settingService()->catalogue($this->canManageAdvanced());
    }

    /**
     * Valeurs soumises, restreintes aux clés que l'utilisateur a le droit d'écrire.
     *
     * @return array<string, mixed>
     */
    protected function editableValues(): array
    {
        $autorisees = [];

        foreach ($this->values as $key => $value) {
            $settingKey = CompagnieSettingKey::tryFrom((string) $key);

            if (! $settingKey) {
                continue;
            }

            if ($settingKey->definition()->isAdminOnly() && ! $this->canManageAdvanced()) {
                continue;
            }

            $autorisees[$key] = $value;
        }

        return $autorisees;
    }

    /** Bascule sur un groupe visible si l'onglet courant ne l'est plus. */
    protected function ensureGroupIsVisible(): void
    {
        $catalogue = $this->catalogue();

        if (! isset($catalogue[$this->activeGroup])) {
            $this->activeGroup = (string) array_key_first($catalogue);
        }
    }

    /** Reporte les erreurs du service sur les champs du formulaire. */
    protected function setErrorBagFrom(ValidationException $exception): void
    {
        foreach ($exception->errors() as $key => $messages) {
            $this->addError('values.'.$key, $messages[0]);
        }
    }

    protected function authorizeView(Compagnie $compagnie): void
    {
        Gate::authorize('compagnie-settings.view', $compagnie);
    }
}
