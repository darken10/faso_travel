<?php

namespace App\Livewire\Compagnie\Voyage;

use App\Models\Voyage\Voyage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class VoyageManager extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        Voyage::findOrFail($id)->delete();
        session()->flash('success', 'Voyage supprimé.');
    }

    public function render()
    {
        $voyages = Voyage::withoutGlobalScopes()
            ->where('compagnie_id', auth()->user()->compagnie_id)
            ->with(['trajet.depart', 'trajet.arriver', 'gareDepart', 'gareArrive', 'statut'])
            ->when($this->search, fn($q) => $q
                ->whereHas('trajet.depart', fn($r) => $r->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('trajet.arriver', fn($r) => $r->where('name', 'like', "%{$this->search}%")))
            ->latest()
            ->paginate(15);

        return view('livewire.compagnie.voyage.voyage-manager', compact('voyages'));
    }
}
