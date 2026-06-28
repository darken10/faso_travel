<?php

namespace App\Livewire\Compagnie\Voyage;

use App\Enums\StatutTicket;
use App\Enums\StatutVoyageInstance;
use App\Enums\TypeNotification;
use App\Models\Compagnie\Care;
use App\Models\Compagnie\Chauffer;
use App\Models\Voyage\Classe;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Exports\InstancesExport;
use App\Notifications\Ticket\TicketNotification;
use App\Services\Voyage\VoyageInstanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.compagnie-panel')]
class VoyageInstanceManager extends Component
{
    use WithPagination;

    public string $search = '';
    /** Filtre temporel de la liste : upcoming | past | all */
    public string $periode = 'upcoming';
    /** Filtre par plage de dates (sur la date de l'instance) */
    public string $dateDebut = '';
    public string $dateFin = '';
    public bool $showModal = false;
    public ?string $editingId = null;

    public ?int $voyage_id = null;
    public string $date = '';
    public ?int $care_id = null;
    public string $heure = '';
    public ?string $chauffer_id = null;
    public string $statut = '';
    public ?int $classe_id = null;

    public bool $showGenModal = false;
    public int  $genJours     = 30;

    // ── Modale affectation (chauffeur / véhicule) ─────────────────────────────
    public bool    $showAssignModal              = false;
    public ?string $assigningId                  = null;
    public ?int    $assignCareId                 = null;
    public ?string $assignChauffeurId            = null;
    public string $assignPreviewNbPlace         = '';
    public string $assignPreviewPrix            = '';
    public string $assignPreviewPrixAllerRetour = '';

    // ── Prévisualisation modale création/édition ──────────────────────────────
    public string $previewNbPlace         = '';
    public string $previewPrix            = '';
    public string $previewPrixAllerRetour = '';

    // ── Modale alerte (annulation / retard) ───────────────────────────────────
    public bool    $showAlertModal = false;
    public ?string $alertingId     = null;
    public string  $alertType      = 'ANNULE';
    public string  $alertReason    = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPeriode(): void
    {
        $this->resetPage();
    }

    public function updatingDateDebut(): void
    {
        $this->resetPage();
    }

    public function updatingDateFin(): void
    {
        $this->resetPage();
    }

    public function resetDateFilters(): void
    {
        $this->dateDebut = '';
        $this->dateFin = '';
        $this->resetPage();
    }

    // ── Hooks réactifs ────────────────────────────────────────────────────────

    public function updatedAssignCareId(): void
    {
        $care     = $this->assignCareId ? Care::find($this->assignCareId) : null;
        $instance = VoyageInstance::find($this->assigningId);
        $nb       = $care?->number_place ?: ($instance?->voyage?->nb_pace ?: 0);
        $this->assignPreviewNbPlace = $nb ? (string) $nb : '—';
    }

    public function updatedVoyageId(): void
    {
        $this->refreshCreatePreview();
    }

    public function updatedCareId(): void
    {
        $this->refreshCreatePreview();
    }

    private function refreshCreatePreview(): void
    {
        $voyage = $this->voyage_id ? Voyage::find($this->voyage_id) : null;
        $care   = $this->care_id   ? Care::find($this->care_id)     : null;

        $nb = $care?->number_place ?: ($voyage?->nb_pace ?: 0);
        $this->previewNbPlace         = $nb ? (string) $nb : '—';
        $this->previewPrix            = $voyage?->prix             ? number_format($voyage->prix, 0, ',', ' ') : '—';
        $this->previewPrixAllerRetour = $voyage?->prix_aller_retour ? number_format($voyage->prix_aller_retour, 0, ',', ' ') : '—';
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
        $this->dispatch('toast', type: 'success', message:
            "{$result['created']} instance(s) créée(s) · {$result['skipped']} déjà existante(s) ignorée(s)."
        );
    }

    /** Manifeste d'embarquement PDF d'une seule instance. */
    public function exportManifeste(string $id)
    {
        $instance = VoyageInstance::with([
            'voyage.trajet.depart', 'voyage.trajet.arriver', 'voyage.compagnie',
            'care', 'chauffer', 'tickets.user', 'tickets.autre_personne',
        ])
            ->whereHas('voyage', fn ($q) => $q->where('compagnie_id', auth()->user()->compagnie_id))
            ->findOrFail($id);

        $passengers = $instance->tickets
            ->filter(fn ($t) => $t->statut !== StatutTicket::Annuler)
            ->sortBy('numero_chaise');

        $pdf = Pdf::loadView('exports.manifeste', compact('instance', 'passengers'));

        return response()->streamDownload(
            fn () => print($pdf->output()),
            'manifeste-' . \Carbon\Carbon::parse($instance->date)->format('Y-m-d') . '.pdf',
        );
    }

    // ── Affectation ───────────────────────────────────────────────────────────

    public function openAssignModal(string $id): void
    {
        $instance = VoyageInstance::with('voyage', 'care')->findOrFail($id);
        $this->assigningId       = $id;
        $this->assignCareId      = $instance->care_id;
        $this->assignChauffeurId = $instance->chauffer_id;

        $care   = $instance->care;
        $voyage = $instance->voyage;
        $nb     = $care?->number_place ?: ($voyage?->nb_pace ?: 0);
        $this->assignPreviewNbPlace         = $nb ? (string) $nb : '—';
        $this->assignPreviewPrix            = $voyage?->prix             ? number_format($voyage->prix, 0, ',', ' ')             : '—';
        $this->assignPreviewPrixAllerRetour = $voyage?->prix_aller_retour ? number_format($voyage->prix_aller_retour, 0, ',', ' ') : '—';

        $this->showAssignModal = true;
    }

    public function saveAssignment(): void
    {
        $this->validate([
            'assignCareId'      => 'nullable|exists:cares,id',
            'assignChauffeurId' => 'nullable|exists:chauffers,id',
        ]);

        $instance = VoyageInstance::findOrFail($this->assigningId);

        $nbPlace = $instance->nb_place;
        if ($this->assignCareId) {
            $care    = Care::find($this->assignCareId);
            $nbPlace = $care?->number_place ?: ($instance->voyage?->nb_pace ?: $nbPlace);
        }

        $instance->update([
            'care_id'     => $this->assignCareId ?: null,
            'chauffer_id' => $this->assignChauffeurId ?: null,
            'nb_place'    => $nbPlace,
            'prix'        => $instance->voyage?->prix ?? 0,
        ]);

        $this->showAssignModal = false;
        $this->dispatch('toast', type: 'success', message: 'Affectation enregistrée.');
    }

    // ── Alerte annulation / retard ───────────────────────────────────────────

    public function openAlertModal(string $id): void
    {
        $this->alertingId     = $id;
        $this->alertType      = 'ANNULE';
        $this->alertReason    = '';
        $this->showAlertModal = true;
    }

    public function confirmAlert(): void
    {
        $this->validate([
            'alertType'   => 'required|in:ANNULE,RETARDE',
            'alertReason' => 'nullable|string|max:500',
        ]);

        $instance = VoyageInstance::findOrFail($this->alertingId);
        $isAnnule = $this->alertType === 'ANNULE';

        $instance->update(['statut' => $this->alertType]);

        $tickets = $instance->tickets()
            ->whereIn('statut', [
                StatutTicket::Payer->value,
                StatutTicket::Valider->value,
                StatutTicket::EnAttente->value,
            ])
            ->with('user')
            ->get();

        $notifType  = $isAnnule ? TypeNotification::VOYAGE_ANNULE : TypeNotification::VOYAGE_RETARDE;
        $notifTitle = $isAnnule ? 'Voyage annulé' : 'Voyage retardé';
        $notifMsg   = $this->alertReason ?: ($isAnnule
            ? 'Votre voyage a été annulé. Votre ticket est suspendu en attente de remboursement.'
            : 'Votre voyage a été retardé. Nous vous tiendrons informé des nouvelles horaires.');

        foreach ($tickets as $ticket) {
            if ($isAnnule) {
                $ticket->update(['statut' => StatutTicket::Pause->value]);
            }
            try {
                $ticket->user?->notify(new TicketNotification($ticket, $notifType, $notifTitle, $notifMsg));
            } catch (\Throwable) {
                // notification failure shouldn't block the action
            }
        }

        $this->showAlertModal = false;
        $this->dispatch('toast', type: 'success', message: $isAnnule
            ? "Instance annulée · {$tickets->count()} ticket(s) mis en pause et client(s) notifié(s)."
            : "Instance signalée comme retardée · {$tickets->count()} client(s) notifié(s)."
        );
    }

    public function openCreate(): void
    {
        $this->reset(['editingId', 'voyage_id', 'date', 'care_id', 'heure', 'chauffer_id', 'statut', 'classe_id',
                      'previewNbPlace', 'previewPrix', 'previewPrixAllerRetour']);
        $this->statut = StatutVoyageInstance::DISPONIBLE->value;
        $this->showModal = true;
    }

    public function openEdit(string $id): void
    {
        $instance = VoyageInstance::with('voyage', 'care')->findOrFail($id);
        $this->editingId   = $id;
        $this->voyage_id   = $instance->voyage_id;
        $this->date        = $instance->date ? $instance->date->format('Y-m-d') : '';
        $this->care_id     = $instance->care_id;
        $this->heure       = $instance->heure ? $instance->heure->format('H:i') : '';
        $this->chauffer_id = $instance->chauffer_id;
        $this->statut      = $instance->statut->value ?? StatutVoyageInstance::DISPONIBLE->value;
        $this->classe_id   = $instance->classe_id;

        $voyage = $instance->voyage;
        $care   = $instance->care;
        $nb     = $care?->number_place ?: ($voyage?->nb_pace ?: 0);
        $this->previewNbPlace         = $nb ? (string) $nb : '—';
        $this->previewPrix            = $voyage?->prix             ? number_format($voyage->prix, 0, ',', ' ')             : '—';
        $this->previewPrixAllerRetour = $voyage?->prix_aller_retour ? number_format($voyage->prix_aller_retour, 0, ',', ' ') : '—';

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'voyage_id'   => 'required|exists:voyages,id',
            'date'        => 'required|date',
            'heure'       => 'required|string',
            'statut'      => 'required|string',
            'care_id'     => 'nullable|exists:cares,id',
            'classe_id'   => 'nullable|exists:classes,id',
            'chauffer_id' => 'nullable|exists:chauffers,id',
        ]);

        $voyage  = Voyage::find($this->voyage_id);
        $care    = $this->care_id ? Care::find($this->care_id) : null;
        $nbPlace = $care?->number_place ?: ($voyage?->nb_pace ?: 0);

        $data = [
            'voyage_id'   => $this->voyage_id,
            'date'        => $this->date,
            'heure'       => $this->heure,
            'nb_place'    => $nbPlace,
            'statut'      => $this->statut,
            'prix'        => $voyage?->prix ?? 0,
            'care_id'     => $this->care_id ?: null,
            'classe_id'   => $this->classe_id,
            'chauffer_id' => $this->chauffer_id ?: null,
        ];

        if ($this->editingId) {
            VoyageInstance::findOrFail($this->editingId)->update($data);
            $this->dispatch('toast', type: 'success', message: 'Instance mise à jour.');
        } else {
            VoyageInstance::create($data);
            $this->dispatch('toast', type: 'success', message: 'Instance créée.');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'voyage_id', 'date', 'care_id', 'heure', 'chauffer_id', 'statut', 'classe_id']);
    }

    public function delete(string $id): void
    {
        VoyageInstance::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: 'Instance supprimée.');
    }

    /** Requête filtrée des instances (réutilisée par la liste et l'export). */
    private function filteredInstances()
    {
        $compagnieId = auth()->user()->compagnie_id;
        $dateHeure = "STR_TO_DATE(CONCAT(date,' ',TIME_FORMAT(heure,'%H:%i:%s')), '%Y-%m-%d %H:%i:%s')";

        return VoyageInstance::query()
            ->whereHas('voyage', fn($q) => $q->where('compagnie_id', $compagnieId))
            ->with(['voyage.trajet.depart', 'voyage.trajet.arriver', 'care', 'chauffer'])
            ->withCount(['tickets as occupied_count' => fn($q) => $q->where('statut', '!=', StatutTicket::Annuler)])
            ->when($this->search, fn($q) => $q->whereHas('voyage.trajet.depart', fn($r) => $r->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('voyage.trajet.arriver', fn($r) => $r->where('name', 'like', "%{$this->search}%")))
            ->when($this->periode === 'upcoming', fn($q) => $q->whereRaw("{$dateHeure} >= NOW()"))
            ->when($this->periode === 'past', fn($q) => $q->whereRaw("{$dateHeure} < NOW()"))
            ->when($this->dateDebut, fn($q) => $q->whereDate('date', '>=', $this->dateDebut))
            ->when($this->dateFin, fn($q) => $q->whereDate('date', '<=', $this->dateFin))
            ->orderByRaw($this->periode === 'past' ? "{$dateHeure} DESC" : "{$dateHeure} ASC");
    }

    /** Export Excel du planning des voyages (instances filtrées). */
    public function exportPlanning()
    {
        return Excel::download(new InstancesExport($this->filteredInstances()), 'planning-voyages-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function render()
    {
        $compagnieId = auth()->user()->compagnie_id;

        $instances = $this->filteredInstances()->paginate(15);

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
