<?php

namespace App\Livewire\Compagnie\Caisse;

use App\Models\Finance\Caisse;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class DetailCaisse extends Component
{
    use WithPagination;

    public Caisse $caisse;

    public function render()
    {
        $tickets = $this->caisse->tickets()
            ->with(['autrePersonne', 'voyageInstance.voyage.trajet'])
            ->latest()
            ->paginate(15);

        return view('livewire.compagnie.caisse.detail-caisse', compact('tickets'));
    }
}
