<?php

namespace App\Livewire\Compagnie\Parametre;

use App\Traits\ManagesCompagnieSettings;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Paramétrage de la compagnie de l'utilisateur connecté.
 *
 * La compagnie ciblée est toujours celle du compte : elle n'est pas
 * modifiable depuis cet écran, contrairement au panel administrateur.
 */
#[Layout('layouts.compagnie-panel')]
class ParametreManager extends Component
{
    use ManagesCompagnieSettings;

    public function mount(): void
    {
        $this->compagnieId = auth()->user()->compagnie_id;

        abort_if($this->compagnieId === null, 403, 'Compte non associé à une compagnie.');

        $this->loadSettings();
    }

    public function render(): View
    {
        return view('livewire.compagnie.parametre.parametre-manager', [
            'catalogue'         => $this->catalogue(),
            'readOnly'          => $this->isReadOnly(),
            'canManageAdvanced' => $this->canManageAdvanced(),
            'compagnie'         => $this->compagnie(),
        ]);
    }
}
