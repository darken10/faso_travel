<?php

namespace App\Livewire\Compagnie\Voyage;

use App\Models\Voyage\Voyage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\ScopedToCompagnie;

#[Layout('layouts.compagnie-panel')]
class VoyageManager extends Component
{
    use ScopedToCompagnie;

    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        Voyage::ofCompagnie($this->compagnieId())->findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Voyage supprimé.');
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
