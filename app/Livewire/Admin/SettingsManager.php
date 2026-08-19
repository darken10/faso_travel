<?php

namespace App\Livewire\Admin;

use App\Models\Compagnie\Compagnie;
use App\Traits\ManagesCompagnieSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Paramétrage de n'importe quelle compagnie depuis le panel d'administration.
 *
 * Même formulaire que l'espace compagnie, augmenté d'un sélecteur de compagnie
 * et du groupe « Avancé » réservé à la plateforme.
 */
#[Layout('layouts.admin-panel')]
class SettingsManager extends Component
{
    use ManagesCompagnieSettings;

    #[Url(as: 'compagnie', history: true)]
    public ?int $selectedCompagnieId = null;

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('compagnie-settings.viewAny');

        $this->compagnieId = $this->selectedCompagnieId;

        if ($this->compagnieId) {
            $this->loadSettings();
        }
    }

    /** Change la compagnie configurée et recharge le formulaire. */
    public function updatedSelectedCompagnieId(mixed $value): void
    {
        $this->compagnieId = $value !== '' && $value !== null ? (int) $value : null;
        $this->selectedCompagnieId = $this->compagnieId;
        $this->resetErrorBag();
        $this->loadSettings();
    }

    /** Rétablit l'intégralité des défauts pour la compagnie sélectionnée. */
    public function resetAll(): void
    {
        $compagnie = $this->compagnie();

        if (! $compagnie) {
            return;
        }

        Gate::authorize('compagnie-settings.reset', $compagnie);

        $this->settingService()->resetAll($compagnie);
        $this->loadSettings();
        $this->dispatch('toast', type: 'success', message: 'Paramétrage entièrement réinitialisé.');
    }

    public function render(): View
    {
        $compagnies = Compagnie::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('sigle', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get(['id', 'name', 'sigle']);

        return view('livewire.admin.settings-manager', [
            'compagnies'        => $compagnies,
            'compagnie'         => $this->compagnie(),
            'catalogue'         => $this->catalogue(),
            'readOnly'          => $this->isReadOnly(),
            'canManageAdvanced' => $this->canManageAdvanced(),
        ]);
    }
}
