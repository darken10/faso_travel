<?php

namespace App\Livewire\Compagnie\Caisse;

use App\Models\Finance\Caisse;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class HistoriqueCaisses extends Component
{
    use WithPagination;

    public function render()
    {
        $caisses = Caisse::with('user')
            ->latest('opened_at')
            ->paginate(15);

        return view('livewire.compagnie.caisse.historique-caisses', compact('caisses'));
    }
}
