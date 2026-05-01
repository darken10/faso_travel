<?php

namespace App\Livewire\Admin;

use App\Models\Ville\Pays;
use App\Models\Ville\Region;
use App\Models\Ville\Ville;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.admin-panel')]
class RegionManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public bool $showVillesModal = false;
    public ?int $editingId = null;
    public ?int $selectedRegionId = null;

    public string $name = '';
    public ?int $pays_id = null;

    // Ville inline form
    public string $ville_name = '';
    public string $ville_lat = '';
    public string $ville_lng = '';
    public bool $showVilleForm = false;
    public ?int $editingVilleId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'name', 'pays_id']);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $region = Region::findOrFail($id);
        $this->editingId = $id;
        $this->name = $region->name;
        $this->pays_id = $region->pays_id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'    => 'required|string|max:255',
            'pays_id' => 'required|exists:pays,id',
        ]);

        $data = ['name' => $this->name, 'pays_id' => $this->pays_id];

        if ($this->editingId) {
            Region::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Région mise à jour.');
        } else {
            Region::create($data);
            session()->flash('success', 'Région créée.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'pays_id']);
    }

    public function delete(int $id): void
    {
        Region::findOrFail($id)->delete();
        session()->flash('success', 'Région supprimée.');
    }

    // ─── Gestion des villes inline ───
    public function openVilles(int $regionId): void
    {
        $this->selectedRegionId = $regionId;
        $this->reset(['ville_name', 'ville_lat', 'ville_lng', 'editingVilleId', 'showVilleForm']);
        $this->showVillesModal = true;
    }

    public function openVilleForm(): void
    {
        $this->reset(['ville_name', 'ville_lat', 'ville_lng', 'editingVilleId']);
        $this->showVilleForm = true;
    }

    public function editVille(int $id): void
    {
        $ville = Ville::findOrFail($id);
        $this->editingVilleId = $id;
        $this->ville_name = $ville->name;
        $this->ville_lat = $ville->lat;
        $this->ville_lng = $ville->lng;
        $this->showVilleForm = true;
    }

    public function saveVille(): void
    {
        $this->validate([
            'ville_name' => 'required|string|max:255',
            'ville_lat'  => 'required|numeric',
            'ville_lng'  => 'required|numeric',
        ]);

        $data = [
            'name'      => $this->ville_name,
            'lat'       => $this->ville_lat,
            'lng'       => $this->ville_lng,
            'region_id' => $this->selectedRegionId,
        ];

        if ($this->editingVilleId) {
            Ville::findOrFail($this->editingVilleId)->update($data);
        } else {
            Ville::create($data);
        }

        $this->reset(['ville_name', 'ville_lat', 'ville_lng', 'editingVilleId', 'showVilleForm']);
    }

    public function deleteVille(int $id): void
    {
        Ville::findOrFail($id)->delete();
    }

    public function render()
    {
        $regions = Region::query()
            ->with('pays')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        $allPays = Pays::orderBy('name')->get();

        $villes = $this->selectedRegionId
            ? Ville::where('region_id', $this->selectedRegionId)->orderBy('name')->get()
            : collect();

        $selectedRegion = $this->selectedRegionId ? Region::with('pays')->find($this->selectedRegionId) : null;

        return view('livewire.admin.region-manager', compact('regions', 'allPays', 'villes', 'selectedRegion'));
    }
}
