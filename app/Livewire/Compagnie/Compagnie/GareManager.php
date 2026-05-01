<?php

namespace App\Livewire\Compagnie\Compagnie;

use App\Models\Compagnie\Gare;
use App\Models\Statut;
use App\Models\Ville\Ville;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class GareManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public ?int $ville_id = null;
    public string $lat = '';
    public string $lng = '';
    public ?int $statut_id = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'ville_id', 'lat', 'lng', 'statut_id']);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $gare = Gare::findOrFail($id);
        $this->editingId  = $id;
        $this->name       = $gare->name;
        $this->ville_id   = $gare->ville_id;
        $this->lat        = $gare->lat ?? '';
        $this->lng        = $gare->lng ?? '';
        $this->statut_id  = $gare->statut_id;
        $this->showModal  = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'      => 'required|string|max:255',
            'ville_id'  => 'required|exists:villes,id',
            'lat'       => 'required|numeric',
            'lng'       => 'required|numeric',
            'statut_id' => 'nullable|exists:statuts,id',
        ]);

        $data = [
            'name'      => $this->name,
            'ville_id'  => $this->ville_id,
            'lat'       => $this->lat,
            'lng'       => $this->lng,
            'statut_id' => $this->statut_id,
        ];

        if ($this->editingId) {
            Gare::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Gare mise à jour.');
        } else {
            Gare::create($data);
            session()->flash('success', 'Gare créée.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'ville_id', 'lat', 'lng', 'statut_id']);
    }

    public function delete(int $id): void
    {
        $gare = Gare::findOrFail($id);
        if ($gare->is_default) {
            session()->flash('error', 'Impossible de supprimer une gare par défaut.');
            return;
        }
        $gare->delete();
        session()->flash('success', 'Gare supprimée.');
    }

    public function render()
    {
        $gares = Gare::query()
            ->with(['ville', 'statut'])
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhereHas('ville', fn($r) => $r->where('name', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(15);

        $villes  = Ville::orderBy('name')->get();
        $statuts = Statut::all();

        return view('livewire.compagnie.compagnie.gare-manager', compact('gares', 'villes', 'statuts'));
    }
}
