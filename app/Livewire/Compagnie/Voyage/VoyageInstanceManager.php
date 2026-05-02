<?php

namespace App\Livewire\Compagnie\Voyage;

use App\Enums\StatutVoyageInstance;
use App\Models\Compagnie\Care;
use App\Models\Compagnie\Chauffer;
use App\Models\Voyage\Classe;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Services\Voyage\VoyageInstanceService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class VoyageInstanceManager extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?string $editingId = null;

    public ?int $voyage_id = null;
    public string $date = '';
    public ?int $care_id = null;
    public string $heure = '';
    public string $nb_place = '';
    public ?string $chauffer_id = null;
    public string $statut = '';
    public string $prix = '';
    public ?int $classe_id = null;

    public bool $showGenModal = false;
    public int  $genJours     = 30;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openGenModal(): void
    {
        $this->genJours     = 30;
        $this->showGenModal = true;
    }

    public function generateInstances(VoyageInstanceService $service): void
    {
        $this->validate(['genJours' => 'required|integer|min:1|max:90']);

        $compagnieId = auth()->user()->compagnie_id;
        $result      = $service->createForCompagnie($compagnieId, $this->genJours);

        $this->showGenModal = false;
        session()->flash('success',
            "{$result['created']} instance(s) créée(s) · {$result['skipped']} déjà existante(s) ignorée(s)."
        );
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'voyage_id', 'date', 'care_id', 'heure', 'nb_place', 'chauffer_id', 'statut', 'prix', 'classe_id']);
        $this->statut = StatutVoyageInstance::DISPONIBLE->value;
        $this->showModal = true;
    }

    public function openEdit(string $id): void
    {
        $instance = VoyageInstance::findOrFail($id);
        $this->editingId   = $id;
        $this->voyage_id   = $instance->voyage_id;
        $this->date        = $instance->date ? $instance->date->format('Y-m-d') : '';
        $this->care_id     = $instance->care_id;
        $this->heure       = $instance->heure ? $instance->heure->format('H:i') : '';
        $this->nb_place    = $instance->nb_place ?? '';
        $this->chauffer_id = $instance->chauffer_id;
        $this->statut      = $instance->statut->value ?? StatutVoyageInstance::DISPONIBLE->value;
        $this->prix        = $instance->prix ?? '';
        $this->classe_id   = $instance->classe_id;
        $this->showModal   = true;
    }

    public function save(): void
    {
        $this->validate([
            'voyage_id'   => 'required|exists:voyages,id',
            'date'        => 'required|date',
            'heure'       => 'required|string',
            'nb_place'    => 'required|integer|min:1',
            'statut'      => 'required|string',
            'prix'        => 'nullable|numeric|min:0',
            'care_id'     => 'nullable|exists:cares,id',
            'classe_id'   => 'nullable|exists:classes,id',
            'chauffer_id' => 'nullable|exists:chauffers,id',
        ]);

        $data = [
            'voyage_id'   => $this->voyage_id,
            'date'        => $this->date,
            'heure'       => $this->heure,
            'nb_place'    => $this->nb_place,
            'statut'      => $this->statut,
            'prix'        => $this->prix ?: null,
            'care_id'     => $this->care_id,
            'classe_id'   => $this->classe_id,
            'chauffer_id' => $this->chauffer_id,
        ];

        if ($this->editingId) {
            VoyageInstance::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Instance mise à jour.');
        } else {
            VoyageInstance::create($data);
            session()->flash('success', 'Instance créée.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'voyage_id', 'date', 'care_id', 'heure', 'nb_place', 'chauffer_id', 'statut', 'prix', 'classe_id']);
    }

    public function delete(string $id): void
    {
        VoyageInstance::findOrFail($id)->delete();
        session()->flash('success', 'Instance supprimée.');
    }

    public function render()
    {
        $compagnieId = auth()->user()->compagnie_id;

        $instances = VoyageInstance::query()
            ->whereHas('voyage', fn($q) => $q->where('compagnie_id', $compagnieId))
            ->with(['voyage.trajet.depart', 'voyage.trajet.arriver', 'care', 'chauffer'])
            ->when($this->search, fn($q) => $q->whereHas('voyage.trajet.depart', fn($r) => $r->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('voyage.trajet.arriver', fn($r) => $r->where('name', 'like', "%{$this->search}%")))
            ->orderByDesc('date')
            ->paginate(15);

        $voyages   = Voyage::withoutGlobalScopes()->where('compagnie_id', $compagnieId)->with(['trajet.depart', 'trajet.arriver'])->get();
        $cares     = Care::orderBy('immatrculation')->get();
        $classes   = Classe::orderBy('name')->get();
        $chaufers  = Chauffer::where('compagnie_id', $compagnieId)->get();
        $statuts   = StatutVoyageInstance::cases();

        return view('livewire.compagnie.voyage.voyage-instance-manager', compact(
            'instances', 'voyages', 'cares', 'classes', 'chaufers', 'statuts'
        ));
    }
}
