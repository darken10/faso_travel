<?php

namespace App\Livewire\Compagnie\Voyage;

use App\Enums\StatutTicket;
use App\Enums\TypeNotification;
use App\Models\Compagnie\Care;
use App\Models\Compagnie\Chauffer;
use App\Models\Voyage\VoyageInstance;
use App\Notifications\Ticket\TicketNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.compagnie-panel')]
class VoyageInstanceShow extends Component
{
    public string $instanceId;

    // Affectation
    public bool    $showAssignModal   = false;
    public ?int    $assignCareId       = null;
    public ?string $assignChauffeurId  = null;

    // Alerte (annulation / retard)
    public bool   $showAlertModal = false;
    public string $alertType      = 'ANNULE';
    public string $alertReason    = '';

    public function mount(string $instanceId): void
    {
        $this->instanceId = $instanceId;
    }

    private function instance(): VoyageInstance
    {
        $compagnieId = auth()->user()->compagnie_id;

        return VoyageInstance::with([
            'voyage.trajet.depart',
            'voyage.trajet.arriver',
            'voyage.compagnie',
            'voyage.classe',
            'chauffer',
            'care',
            'tickets' => fn ($q) => $q->with(['user', 'autre_personne', 'payements']),
        ])
            ->whereHas('voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->findOrFail($this->instanceId);
    }

    // ── Affectation ───────────────────────────────────────────────────────────
    public function openAssignModal(): void
    {
        $instance = $this->instance();
        $this->assignCareId      = $instance->care_id;
        $this->assignChauffeurId = $instance->chauffer_id;
        $this->showAssignModal   = true;
    }

    public function saveAssignment(): void
    {
        $this->validate([
            'assignCareId'      => 'nullable|exists:cares,id',
            'assignChauffeurId' => 'nullable|exists:chauffers,id',
        ]);

        $instance = VoyageInstance::findOrFail($this->instanceId);
        $nbPlace  = $instance->nb_place;
        if ($this->assignCareId) {
            $care    = Care::find($this->assignCareId);
            $nbPlace = $care?->number_place ?: ($instance->voyage?->nb_pace ?: $nbPlace);
        }

        $instance->update([
            'care_id'     => $this->assignCareId ?: null,
            'chauffer_id' => $this->assignChauffeurId ?: null,
            'nb_place'    => $nbPlace,
        ]);

        $this->showAssignModal = false;
        session()->flash('success', 'Affectation enregistrée.');
    }

    // ── Alerte annulation / retard ─────────────────────────────────────────────
    public function openAlertModal(string $type): void
    {
        $this->alertType      = $type;
        $this->alertReason    = '';
        $this->showAlertModal = true;
    }

    public function confirmAlert(): void
    {
        $this->validate([
            'alertType'   => 'required|in:ANNULE,RETARDE',
            'alertReason' => 'nullable|string|max:500',
        ]);

        $instance = VoyageInstance::findOrFail($this->instanceId);
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
                // une notif en échec ne doit pas bloquer
            }
        }

        $this->showAlertModal = false;
        session()->flash('success', $isAnnule
            ? "Instance annulée · {$tickets->count()} ticket(s) mis en pause et client(s) notifié(s)."
            : "Instance signalée comme retardée · {$tickets->count()} client(s) notifié(s).");
    }

    public function render()
    {
        $instance = $this->instance();
        $compagnieId = auth()->user()->compagnie_id;

        // Tickets actifs (hors annulés) indexés par siège.
        $activeTickets = $instance->tickets->filter(
            fn ($t) => $t->statut !== StatutTicket::Annuler
        );
        $bySeat = $activeTickets->keyBy('numero_chaise');

        // Plan des sièges.
        $seats = collect(range(1, max(1, (int) $instance->nb_place)))
            ->map(fn ($n) => [
                'number'   => $n,
                'occupied' => $bySeat->has($n),
                'ticket'   => $bySeat->get($n),
            ]);

        $occupied  = $activeTickets->count();
        $total     = (int) $instance->nb_place;
        $available = max(0, $total - $occupied);

        // Recette = somme des paiements des tickets payés.
        $revenue = $instance->tickets
            ->where('statut', StatutTicket::Payer)
            ->sum(fn ($t) => $t->payements->sum('montant'));

        return view('livewire.compagnie.voyage.voyage-instance-show', [
            'instance'   => $instance,
            'seats'      => $seats,
            'passengers' => $activeTickets->sortBy('numero_chaise'),
            'occupied'   => $occupied,
            'total'      => $total,
            'available'  => $available,
            'revenue'    => $revenue,
            'cares'      => Care::where('compagnie_id', $compagnieId)->orderBy('immatrculation')->get(),
            'chauffeurs' => Chauffer::where('compagnie_id', $compagnieId)->get(),
        ]);
    }
}
