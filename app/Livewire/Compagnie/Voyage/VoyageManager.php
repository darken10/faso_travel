<?php

namespace App\Livewire\Compagnie\Voyage;

use App\Enums\JoursSemain;
use App\Models\Compagnie\Gare;
use App\Models\Statut;
use App\Models\Voyage\Classe;
use App\Models\Voyage\Trajet;
use App\Models\Voyage\Voyage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class VoyageManager extends Component
{
    use WithPagination;

    public string $search        = '';
    public bool   $showModal     = false;
    public ?int   $editingId     = null;

    public ?int   $trajet_id         = null;
    public string $heure             = '';
    public string $temps             = '';   // read-only, dérivé du trajet sélectionné
    public string $prix              = '';
    public string $prix_aller_retour = '';
    public ?int   $statut_id         = null;
    public ?int   $depart_id         = null;
    public ?int   $arrive_id         = null;
    public ?int   $classe_id         = null;
    public bool   $is_quotidient     = false;
    public array  $days              = [];

    public function updatedTrajetId(): void
    {
        $this->depart_id = null;
        $this->arrive_id = null;
        $this->temps     = $this->resolveTempsFromTrajet($this->trajet_id);
    }

    private function resolveTempsFromTrajet(?int $trajetId): string
    {
        if (!$trajetId) return '';
        $trajet = Trajet::find($trajetId);
        if (!$trajet?->temps) return '';
        $parts = explode(':', $trajet->temps);
        return sprintf('%02d:%02d', (int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0));
    }

    public function getGaresDepart(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->trajet_id) return new \Illuminate\Database\Eloquent\Collection();
        $trajet = Trajet::find($this->trajet_id);
        if (!$trajet) return new \Illuminate\Database\Eloquent\Collection();
        return Gare::where('ville_id', $trajet->depart_id)->orWhere('is_default', true)->get();
    }

    public function getGaresArrive(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->trajet_id) return new \Illuminate\Database\Eloquent\Collection();
        $trajet = Trajet::find($this->trajet_id);
        if (!$trajet) return new \Illuminate\Database\Eloquent\Collection();
        return Gare::where('ville_id', $trajet->arriver_id)->orWhere('is_default', true)->get();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset([
            'editingId', 'trajet_id', 'heure', 'temps', 'prix', 'prix_aller_retour',
            'statut_id', 'depart_id', 'arrive_id', 'classe_id', 'is_quotidient', 'days',
        ]);
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $voyage = Voyage::findOrFail($id);

        $this->editingId         = $id;
        $this->trajet_id         = $voyage->trajet_id;
        $this->heure             = $voyage->heure ? $voyage->heure->format('H:i') : '';
        $this->temps             = $this->resolveTempsFromTrajet($voyage->trajet_id);
        $this->prix              = $voyage->prix ?? '';
        $this->prix_aller_retour = $voyage->prix_aller_retour ?? '';
        $this->statut_id         = $voyage->statut_id;
        $this->depart_id         = $voyage->depart_id;
        $this->arrive_id         = $voyage->arrive_id;
        $this->classe_id         = $voyage->classe_id;
        $this->is_quotidient     = (bool) $voyage->is_quotidient;
        $this->days              = is_array($voyage->days) ? $voyage->days : [];
        $this->showModal         = true;
    }

    public function save(): void
    {
        $this->validate([
            'trajet_id'         => 'required|exists:trajets,id',
            'heure'             => 'required|string',
            'prix'              => 'required|numeric|min:0',
            'statut_id'         => 'nullable|exists:statuts,id',
            'depart_id'         => 'required|exists:gares,id',
            'arrive_id'         => 'required|exists:gares,id',
            'classe_id'         => 'nullable|exists:classes,id',
            'prix_aller_retour' => 'nullable|numeric|min:0',
        ]);

        $trajet = Trajet::find($this->trajet_id);

        $data = [
            'trajet_id'         => $this->trajet_id,
            'heure'             => $this->heure,
            'temps'             => $trajet?->temps,
            'prix'              => $this->prix,
            'prix_aller_retour' => $this->prix_aller_retour ?: null,
            'statut_id'         => $this->statut_id,
            'depart_id'         => $this->depart_id,
            'arrive_id'         => $this->arrive_id,
            'classe_id'         => $this->classe_id,
            'is_quotidient'     => $this->is_quotidient,
            'days'              => $this->days,
        ];

        if ($this->editingId) {
            Voyage::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Voyage mis à jour.');
        } else {
            Voyage::create($data);
            session()->flash('success', 'Voyage créé.');
        }

        $this->showModal = false;
        $this->reset([
            'editingId', 'trajet_id', 'heure', 'temps', 'prix', 'prix_aller_retour',
            'statut_id', 'depart_id', 'arrive_id', 'classe_id', 'is_quotidient', 'days',
        ]);
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

        $trajets = Trajet::with(['depart', 'arriver'])->get();
        $classes = Classe::orderBy('name')->get();
        $statuts = Statut::all();
        $allDays = JoursSemain::cases();

        $garesDepart = $this->getGaresDepart();
        $garesArrive = $this->getGaresArrive();

        return view('livewire.compagnie.voyage.voyage-manager', compact(
            'voyages', 'trajets', 'classes', 'statuts', 'allDays', 'garesDepart', 'garesArrive'
        ));
    }
}
