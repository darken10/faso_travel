<?php

namespace App\Services\Ticket;

use App\Enums\StatutTicket;
use App\Enums\TypeTicket;
use App\Helper\TicketHelpers;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketCommandService
{
    public function createFromVoyageInstance(VoyageInstance $instance, mixed $type): array
    {
        $userId = Auth::id();

        // Vérifie si l'utilisateur a déjà un ticket actif sur cette instance
        $existing = Ticket::where('voyage_instance_id', $instance->id)
            ->where('user_id', $userId)
            ->whereNotIn('statut', [StatutTicket::Annuler])
            ->first();

        if ($existing) {
            return ['created' => false, 'message' => 'Vous avez déjà un ticket pour ce voyage.', 'ticket' => $existing];
        }

        $ticket = DB::transaction(function () use ($instance, $type, $userId) {
            $t = new Ticket();
            $t->user_id             = $userId;
            $t->voyage_instance_id  = $instance->id;
            $t->voyage_id           = $instance->voyage_id;
            $t->date                = $instance->date;
            $t->type                = $type instanceof TypeTicket ? $type : TypeTicket::AllerSimple;
            $t->statut              = StatutTicket::EnAttente;
            $t->is_my_ticket        = true;
            $t->numero_ticket       = TicketHelpers::generateTicketNumber();
            $t->code_sms            = TicketHelpers::generateTicketCodeSms();
            $t->code_qr             = TicketHelpers::generateTicketCodeQr();
            $t->numero_chaise       = TicketHelpers::getNumeroChaise($instance);
            $t->save();

            return $t->load((new TicketQueryService())::RELATIONS);
        });

        return ['created' => true, 'message' => 'Ticket créé avec succès.', 'ticket' => $ticket];
    }

    public function cancel(Ticket $ticket): void
    {
        if ($ticket->statut === StatutTicket::Annuler) {
            throw new \DomainException('Ce ticket est déjà annulé.');
        }

        if ($ticket->statut === StatutTicket::Valider) {
            throw new \DomainException('Un ticket validé ne peut plus être annulé.');
        }

        $ticket->statut = StatutTicket::Annuler;
        $ticket->save();
    }

    /**
     * @param  bool  $automatique  Vrai lorsque la pause vient de la tâche planifiée
     *                             (billet jamais scanné), faux pour un geste d'agent.
     *                             Les rapports d'activité distinguent les deux.
     */
    public function pause(Ticket $ticket, bool $automatique = false): void
    {
        if ($ticket->statut !== StatutTicket::Payer) {
            throw new \DomainException('Seul un ticket payé peut être mis en pause.');
        }

        $ticket->statut      = StatutTicket::Pause;
        $ticket->paused_at   = now();
        $ticket->paused_auto = $automatique;
        $ticket->save();
    }

    public function activate(Ticket $ticket, VoyageInstance $newInstance, int $seatNumber): void
    {
        if ($ticket->statut !== StatutTicket::Pause) {
            throw new \DomainException('Seul un ticket en pause peut être réactivé.');
        }

        if ((string) $newInstance->id === (string) $ticket->voyage_instance_id) {
            throw new \DomainException('Veuillez choisir un voyage différent de celui du ticket.');
        }

        $this->assertInstanceIsEquivalent($ticket, $newInstance);

        if ($newInstance->getHeureDepart()->isPast()) {
            throw new \DomainException('Ce voyage est déjà parti.');
        }

        // Même résolution que le plan des sièges exposé par l'API (VoyageService::getTripSeats),
        // sinon une instance avec nb_place = 0 rendrait tous les sièges affichés invalides.
        $maxSeats = $newInstance->nb_place ?: ($newInstance->care?->number_place ?? 50);
        if ($seatNumber < 1 || $seatNumber > $maxSeats) {
            throw new \InvalidArgumentException("Le numéro de siège {$seatNumber} est invalide.");
        }

        DB::transaction(function () use ($ticket, $newInstance, $seatNumber) {
            // Verrou pessimiste : sans lui, deux réactivations concurrentes passent toutes
            // les deux le test de disponibilité et occupent le même siège.
            $taken = Ticket::where('voyage_instance_id', $newInstance->id)
                ->where('numero_chaise', $seatNumber)
                ->whereNotIn('statut', [StatutTicket::Annuler])
                ->lockForUpdate()
                ->exists();

            if ($taken) {
                throw new \InvalidArgumentException('Ce siège vient d\'être occupé. Veuillez en choisir un autre.');
            }

            $ticket->voyage_instance_id = $newInstance->id;
            $ticket->voyage_id          = $newInstance->voyage_id;
            $ticket->date               = $newInstance->date;
            $ticket->numero_chaise      = $seatNumber;
            $ticket->statut             = StatutTicket::Payer;
            $ticket->save();
        });
    }

    /**
     * Le voyage de destination doit être équivalent à celui du ticket : même trajet
     * et même compagnie. Sans ce contrôle, le client peut poster n'importe quel
     * voyage_instance_id et déplacer son ticket vers un trajet qu'il n'a pas payé.
     */
    private function assertInstanceIsEquivalent(Ticket $ticket, VoyageInstance $newInstance): void
    {
        $current = $ticket->voyageInstance?->voyage;
        $target  = $newInstance->voyage;

        if (!$current || !$target) {
            throw new \DomainException('Voyage introuvable pour ce ticket.');
        }

        if ((string) $target->trajet_id !== (string) $current->trajet_id
            || (string) $target->compagnie_id !== (string) $current->compagnie_id) {
            throw new \DomainException('Ce voyage ne correspond pas au trajet initial du ticket.');
        }
    }
}
