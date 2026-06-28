<?php

namespace App\Livewire\Compagnie\Compagnie;

use App\Models\Compagnie\Gare;
use App\Models\Statut;
use App\Models\Ville\Pays;
use App\Models\Ville\Region;
use App\Models\Ville\Ville;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class GareManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name      = '';
    public ?int $pays_id    = null;
    public ?int $region_id  = null;
    public ?int $ville_id   = null;
    public string $lat      = '';
    public string $lng      = '';
    public ?int $statut_id  = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openDocPanel(int $id): void
    {
        $gare = Gare::findOrFail($id);
        $this->dispatch('open-doc-panel',
            type:     Gare::class,
            id:       (string) $id,
            label:    $gare->name,
            typeName: 'Gare',
        );
    }

    #[On('doc-panel-saved')]
    public function refreshDocCounts(): void {}

    public function updatedPaysId(): void
    {
        $this->region_id = null;
        $this->ville_id  = null;
    }

    public function updatedRegionId(): void
    {
        $this->ville_id = null;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'pays_id', 'region_id', 'ville_id', 'lat', 'lng', 'statut_id']);
        $this->showModal = true;
        $this->dispatch('gare-modal-opened', lat: null, lng: null);
    }

    public function openEdit(int $id): void
    {
        $gare = Gare::with('ville.region')->findOrFail($id);

        $this->editingId = $id;
        $this->name      = $gare->name;
        $this->ville_id  = $gare->ville_id;
        $this->region_id = $gare->ville?->region_id;
        $this->pays_id   = $gare->ville?->region?->pays_id;
        $this->lat       = $gare->lat ?? '';
        $this->lng       = $gare->lng ?? '';
        $this->statut_id = $gare->statut_id;
        $this->showModal = true;
        $this->dispatch('gare-modal-opened',
            lat: $this->lat !== '' ? (float) $this->lat : null,
            lng: $this->lng !== '' ? (float) $this->lng : null,
        );
    }

    public function save(): void
    {
        $this->validate([
            'name'      => 'required|string|max:255',
            'ville_id'  => 'required|exists:villes,id',
            'lat'       => 'required|numeric|between:-90,90',
            'lng'       => 'required|numeric|between:-180,180',
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
            $this->dispatch('toast', type: 'success', message: 'Gare mise à jour.');
        } else {
            Gare::create($data);
            $this->dispatch('toast', type: 'success', message: 'Gare créée.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'pays_id', 'region_id', 'ville_id', 'lat', 'lng', 'statut_id']);
    }

    public function delete(int $id): void
    {
        $gare = Gare::findOrFail($id);
        if ($gare->is_default) {
            $this->dispatch('toast', type: 'error', message: 'Impossible de supprimer une gare par défaut.');
            return;
        }
        $gare->delete();
        $this->dispatch('toast', type: 'success', message: 'Gare supprimée.');
    }

    public function render()
    {
        $gares = Gare::withCount('documents')
            ->with(['ville', 'statut'])
            ->when($this->search, fn($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhereHas('ville', fn($r) => $r->where('name', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(15);

        $pays = Pays::orderBy('name')->get(['id', 'name']);

        $regions = $this->pays_id
            ? Region::where('pays_id', $this->pays_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        $villes = $this->region_id
            ? Ville::where('region_id', $this->region_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        $statuts = Statut::all();

        return view('livewire.compagnie.compagnie.gare-manager', compact(
            'gares', 'pays', 'regions', 'villes', 'statuts'
        ));
    }
}
