<?php

namespace App\Livewire\Compagnie\Voyage;

use App\Models\Ville\Pays;
use App\Models\Ville\Region;
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

    public ?int $depart_pays_id   = null;
    public ?int $depart_region_id = null;
    public ?int $depart_id        = null;

    public ?int $arriver_pays_id   = null;
    public ?int $arriver_region_id = null;
    public ?int $arriver_id        = null;

    public string $distance = '';
    public string $temps   = '';
    public string $etat    = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartPaysId(): void
    {
        $this->depart_region_id = null;
        $this->depart_id        = null;
    }

    public function updatedDepartRegionId(): void
    {
        $this->depart_id = null;
    }

    public function updatedArriverPaysId(): void
    {
        $this->arriver_region_id = null;
        $this->arriver_id        = null;
    }

    public function updatedArriverRegionId(): void
    {
        $this->arriver_id = null;
    }

    public function openCreate(): void
    {
        $this->reset([
            'editingId',
            'depart_pays_id', 'depart_region_id', 'depart_id',
            'arriver_pays_id', 'arriver_region_id', 'arriver_id',
            'distance', 'temps', 'etat',
        ]);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $trajet = Trajet::with(['depart.region', 'arriver.region'])->findOrFail($id);

        $this->editingId        = $id;
        $this->depart_id        = $trajet->depart_id;
        $this->depart_region_id = $trajet->depart?->region_id;
        $this->depart_pays_id   = $trajet->depart?->region?->pays_id;

        $this->arriver_id        = $trajet->arriver_id;
        $this->arriver_region_id = $trajet->arriver?->region_id;
        $this->arriver_pays_id   = $trajet->arriver?->region?->pays_id;

        $this->distance = (string) ($trajet->distance ?? '');

        // Tronquer HH:MM:SS → HH:MM pour <input type="time">
        if ($trajet->temps) {
            $parts       = explode(':', $trajet->temps);
            $this->temps = sprintf('%02d:%02d', (int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0));
        } else {
            $this->temps = '';
        }

        $this->etat      = $trajet->etat ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'depart_id'  => 'required|exists:villes,id',
            'arriver_id' => 'required|exists:villes,id|different:depart_id',
            'distance'   => 'nullable|numeric|min:0',
            'temps'      => 'nullable|date_format:H:i',
            'etat'       => 'nullable|string|max:255',
        ]);

        $data = [
            'depart_id'  => $this->depart_id,
            'arriver_id' => $this->arriver_id,
            'distance'   => $this->distance ?: null,
            'temps'      => $this->temps ? $this->temps . ':00' : null,
            'etat'       => $this->etat ?: null,
        ];

        if ($this->editingId) {
            Trajet::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Trajet mis à jour.');
        } else {
            Trajet::create($data);
            $this->dispatch('toast', type: 'success', message: 'Trajet créé.');
        }

        $this->showModal = false;
        $this->reset([
            'editingId',
            'depart_pays_id', 'depart_region_id', 'depart_id',
            'arriver_pays_id', 'arriver_region_id', 'arriver_id',
            'distance', 'temps', 'etat',
        ]);
    }

    public function delete(int $id): void
    {
        Trajet::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Trajet supprimé.');
    }

    public function render()
    {
        $trajets = Trajet::query()
            ->with(['depart', 'arriver'])
            ->when($this->search, fn($q) => $q
                ->whereHas('depart', fn($r) => $r->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('arriver', fn($r) => $r->where('name', 'like', "%{$this->search}%")))
            ->latest()
            ->paginate(15);

        $pays = Pays::orderBy('name')->get(['id', 'name']);

        $departRegions = $this->depart_pays_id
            ? Region::where('pays_id', $this->depart_pays_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        $departVilles = $this->depart_region_id
            ? Ville::where('region_id', $this->depart_region_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        $arriverRegions = $this->arriver_pays_id
            ? Region::where('pays_id', $this->arriver_pays_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        $arriverVilles = $this->arriver_region_id
            ? Ville::where('region_id', $this->arriver_region_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('livewire.compagnie.voyage.trajet-manager', compact(
            'trajets', 'pays',
            'departRegions', 'departVilles',
            'arriverRegions', 'arriverVilles',
        ));
    }
}
