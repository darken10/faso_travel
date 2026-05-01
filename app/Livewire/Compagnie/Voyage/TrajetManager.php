<?php

namespace App\Livewire\Compagnie\Voyage;

use App\Models\Ville\Ville;
use App\Models\Voyage\Trajet;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class TrajetManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $depart_id = null;
    public ?int $arriver_id = null;
    public string $distance = '';
    public string $temps = '';
    public string $etat = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'depart_id', 'arriver_id', 'distance', 'temps', 'etat']);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $trajet = Trajet::findOrFail($id);
        $this->editingId  = $id;
        $this->depart_id  = $trajet->depart_id;
        $this->arriver_id = $trajet->arriver_id;
        $this->distance   = $trajet->distance ?? '';
        $this->temps      = $trajet->temps ?? '';
        $this->etat       = $trajet->etat ?? '';
        $this->showModal  = true;
    }

    public function save(): void
    {
        $this->validate([
            'depart_id'  => 'required|exists:villes,id',
            'arriver_id' => 'required|exists:villes,id|different:depart_id',
            'distance'   => 'nullable|numeric|min:0',
            'temps'      => 'nullable|string',
            'etat'       => 'nullable|string|max:255',
        ]);

        $data = [
            'depart_id'  => $this->depart_id,
            'arriver_id' => $this->arriver_id,
            'distance'   => $this->distance ?: null,
            'temps'      => $this->temps ?: null,
            'etat'       => $this->etat ?: null,
        ];

        if ($this->editingId) {
            Trajet::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Trajet mis à jour.');
        } else {
            Trajet::create($data);
            session()->flash('success', 'Trajet créé.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'depart_id', 'arriver_id', 'distance', 'temps', 'etat']);
    }

    public function delete(int $id): void
    {
        Trajet::findOrFail($id)->delete();
        session()->flash('success', 'Trajet supprimé.');
    }

    public function render()
    {
        $trajets = Trajet::query()
            ->with(['depart', 'arriver'])
            ->when($this->search, fn($q) => $q->whereHas('depart', fn($r) => $r->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('arriver', fn($r) => $r->where('name', 'like', "%{$this->search}%")))
            ->latest()
            ->paginate(15);

        $villes = Ville::orderBy('name')->get();

        return view('livewire.compagnie.voyage.trajet-manager', compact('trajets', 'villes'));
    }
}
