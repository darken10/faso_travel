<?php

namespace App\Livewire\Admin;

use App\Models\Ville\Pays;
use App\Models\Ville\Region;
use App\Models\Ville\Ville;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.admin-panel')]
class VilleManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $pays_id = null;
    public ?int $region_id = null;
    public string $name = '';
    public string $lat = '';
    public string $lng = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPaysId(): void
    {
        $this->region_id = null;
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'pays_id', 'region_id', 'name', 'lat', 'lng']);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $ville = Ville::with('region.pays')->findOrFail($id);
        $this->editingId = $id;
        $this->pays_id = $ville->region->pays_id;
        $this->region_id = $ville->region_id;
        $this->name = $ville->name;
        $this->lat = $ville->lat;
        $this->lng = $ville->lng;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'region_id' => 'required|exists:regions,id',
            'name'      => 'required|string|max:255',
            'lat'       => 'required|numeric',
            'lng'       => 'required|numeric',
        ]);

        $data = [
            'name'      => $this->name,
            'region_id' => $this->region_id,
            'lat'       => $this->lat,
            'lng'       => $this->lng,
        ];

        if ($this->editingId) {
            Ville::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Ville mise à jour.');
        } else {
            Ville::create($data);
            session()->flash('success', 'Ville créée.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'pays_id', 'region_id', 'name', 'lat', 'lng']);
    }

    public function delete(int $id): void
    {
        Ville::findOrFail($id)->delete();
        session()->flash('success', 'Ville supprimée.');
    }

    public function getRegionsByPays()
    {
        if (!$this->pays_id) return collect();
        return Region::where('pays_id', $this->pays_id)->orderBy('name')->get();
    }

    public function render()
    {
        $villes = Ville::query()
            ->with('region.pays')
            ->when($this->search, fn($q) => $q->where('villes.name', 'like', "%{$this->search}%")
                ->orWhereHas('region', fn($r) => $r->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('region.pays', fn($p) => $p->where('name', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(15);

        $allPays = Pays::orderBy('name')->get();
        $regions = $this->getRegionsByPays();

        return view('livewire.admin.ville-manager', compact('villes', 'allPays', 'regions'));
    }
}
