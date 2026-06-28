<?php

namespace App\Livewire\Compagnie\Voyage;

use App\Enums\JoursSemain;
use App\Models\Compagnie\Care;
use App\Models\Compagnie\Chauffer;
use App\Models\Compagnie\Gare;
use App\Models\Statut;
use App\Models\Voyage\Classe;
use App\Models\Voyage\Trajet;
use App\Models\Voyage\Voyage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.compagnie-panel')]
class VoyageForm extends Component
{
    public ?int $editingId = null;

    public ?int   $trajet_id         = null;
    public string $heure             = '';
    public string $temps             = '';   // lecture seule, dérivé du trajet
    public string $prix              = '';
    public string $prix_aller_retour = '';
    public ?int   $statut_id         = null;
    public ?int   $depart_id         = null;
    public ?int   $arrive_id         = null;
    public ?int   $classe_id         = null;
    public bool   $is_quotidient     = true;
    public array  $days              = [];
    public string $nb_pace           = '';

    // Période de validité
    public string $date_debut = '';
    public string $date_fin   = '';

    // Véhicule + chauffeur par défaut (utilisés par l'affectation auto)
    public ?int    $care_id     = null;
    public ?string $chauffer_id = null;

    public function mount(?int $voyageId = null): void
    {
        $this->date_debut = now()->toDateString();

        if (!$voyageId) {
            return;
        }

        $voyage = Voyage::withoutGlobalScopes()->findOrFail($voyageId);

        $this->editingId         = $voyage->id;
        $this->trajet_id         = $voyage->trajet_id;
        $this->heure             = $voyage->heure ? $voyage->heure->format('H:i') : '';
        $this->temps             = $this->resolveTempsFromTrajet($voyage->trajet_id);
        $this->prix              = (string) ($voyage->prix ?? '');
        $this->prix_aller_retour = (string) ($voyage->prix_aller_retour ?? '');
        $this->statut_id         = $voyage->statut_id;
        $this->depart_id         = $voyage->depart_id;
        $this->arrive_id         = $voyage->arrive_id;
        $this->classe_id         = $voyage->classe_id;
        $this->is_quotidient     = (bool) $voyage->is_quotidient;
        $this->days              = is_array($voyage->days) ? $voyage->days : [];
        $this->nb_pace           = (string) ($voyage->nb_pace ?? '');
        $this->date_debut        = $voyage->date_debut?->toDateString() ?? now()->toDateString();
        $this->date_fin          = $voyage->date_fin?->toDateString() ?? '';
        $this->care_id           = $voyage->care_id;
        $this->chauffer_id       = $voyage->chauffer_id;
    }

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

    public function save()
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
            'nb_pace'           => 'required|integer|min:1',
            'date_debut'        => 'required|date',
            'date_fin'          => 'nullable|date|after_or_equal:date_debut',
            'care_id'           => 'nullable|exists:cares,id',
            'chauffer_id'       => 'nullable|exists:chauffers,id',
        ], [
            'date_fin.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
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
            'nb_pace'           => (int) $this->nb_pace,
            'date_debut'        => $this->date_debut,
            'date_fin'          => $this->date_fin ?: null,
            'care_id'           => $this->care_id ?: null,
            'chauffer_id'       => $this->chauffer_id ?: null,
        ];

        if ($this->editingId) {
            Voyage::withoutGlobalScopes()->findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Voyage mis à jour.');
        } else {
            Voyage::create($data);
            session()->flash('success', 'Voyage créé.');
        }

        return redirect()->route('panel.compagnie.voyages');
    }

    public function render()
    {
        $compagnieId = auth()->user()->compagnie_id;

        return view('livewire.compagnie.voyage.voyage-form', [
            'trajets'     => Trajet::with(['depart', 'arriver'])->get(),
            'classes'     => Classe::orderBy('name')->get(),
            'statuts'     => Statut::all(),
            'allDays'     => JoursSemain::cases(),
            'garesDepart' => $this->getGaresDepart(),
            'garesArrive' => $this->getGaresArrive(),
            'cares'       => Care::where('compagnie_id', $compagnieId)->orderBy('immatrculation')->get(),
            'chauffeurs'  => Chauffer::where('compagnie_id', $compagnieId)->get(),
        ]);
    }
}
