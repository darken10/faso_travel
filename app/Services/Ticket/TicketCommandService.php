<?php

namespace App\Services\Ticket;

use App\DTOs\Ticket\CreateTicketDTO;
use App\DTOs\Ticket\TransferTicketDTO;
use App\Enums\StatutTicket;
use App\Enums\TypeTicket;
use App\Events\PayementEffectuerEvent;
use App\Events\SendClientTicketByMailEvent;
use App\Events\TranfererTicketToOtherUserEvent;
use App\Helper\TicketHelpers;
use App\Models\Ticket\AutrePersonne;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service dédié aux opérations d'écriture sur les tickets.
 * Centralise la logique de création, annulation, transfert, pause, et validation.
 */
class TicketCommandService
{
    /**
     * Transitions de statut autorisées.
     * Toute la logique de machine à états est centralisée ici.
     */
    private const STATUS_TRANSITIONS = [
        'En attente' => ['Payer', 'Annuler', 'Refuser', 'Pause'],
        'Payer' => ['Valider', 'Annuler', 'Bloquer', 'Suspendre', 'Pause'],
        'Pause' => ['En attente', 'Payer', 'Annuler', 'Refuser', 'Valider'],
        'Bloquer' => ['Payer', 'Annuler'],
        'Suspendre' => ['Payer', 'Annuler'],
        'Valider' => ['Annuler'],
        'Annuler' => [],
        'Refuser' => [],
    ];

    // ─── Création ────────────────────────────────────────────────────────

    /**
     * Crée un ticket à partir d'un VoyageInstance.
     * Vérifie l'existence d'un ticket similaire en attente avant de créer.
     */
    public function createFromVoyageInstance(
        VoyageInstance $voyageInstance,
        TypeTicket $type,
        bool $isMyTicket = true,
        ?AutrePersonne $autrePersonne = null,
    ): array {
        $existingTicket = Ticket::query()
            ->where('user_id', Auth::id())
            ->where('voyage_instance_id', $voyageInstance->id)
            ->where('statut', StatutTicket::EnAttente)
            ->where('is_my_ticket', true)
            ->where('type', $type)
            ->latest()
            ->first();

        if ($existingTicket) {
            return [
                'ticket' => $existingTicket,
                'created' => false,
                'message' => 'Un ticket en attente existe déjà pour ce voyage.',
            ];
        }

        $ticket = DB::transaction(function () use ($voyageInstance, $type, $isMyTicket, $autrePersonne) {
            return Ticket::create([
                'user_id' => Auth::id(),
                'voyage_id' => $voyageInstance->voyage_id,
                'voyage_instance_id' => $voyageInstance->id,
                'date' => $voyageInstance->date,
                'type' => $type,
                'statut' => StatutTicket::EnAttente,
                'numero_ticket' => TicketHelpers::generateTicketNumber(),
                'code_sms' => TicketHelpers::generateTicketCodeSms(),
                'code_qr' => TicketHelpers::generateTicketCodeQr(),
                'is_my_ticket' => $isMyTicket,
                'autre_personne_id' => $autrePersonne?->id,
                'a_bagage' => false,
            ]);
        });

        return [
            'ticket' => $ticket,
            'created' => true,
            'message' => 'Ticket créé avec succès.',
        ];
    }

    // ─── Changement de statut ────────────────────────────────────────────

    /**
     * Change le statut d'un ticket en respectant la machine à états.
     *
     * @throws \InvalidArgumentException Si la transition n'est pas autorisée
     */
    public function changeStatus(Ticket $ticket, StatutTicket $newStatus): Ticket
    {
        $currentValue = $ticket->statut->value;
        $allowedTransitions = self::STATUS_TRANSITIONS[$currentValue] ?? [];

        if (!in_array($newStatus->value, $allowedTransitions)) {
            throw new \InvalidArgumentException(
                "Transition de statut non autorisée : '{$currentValue}' → '{$newStatus->value}'"
            );
        }

        return DB::transaction(function () use ($ticket, $newStatus, $currentValue) {
            $ticket->statut = $newStatus;
            $ticket->save();

            Log::info("Ticket #{$ticket->numero_ticket} : statut changé de {$currentValue} → {$newStatus->value}");

            return $ticket;
        });
    }

    // ─── Annulation ──────────────────────────────────────────────────────

    public function cancel(Ticket $ticket): Ticket
    {
        if ($ticket->statut === StatutTicket::Valider) {
            throw new \DomainException('Un ticket validé ne peut pas être annulé.');
        }

        return $this->changeStatus($ticket, StatutTicket::Annuler);
    }

    // ─── Mise en pause ───────────────────────────────────────────────────

    public function pause(Ticket $ticket): Ticket
    {
        $this->assertTicketOwnership($ticket);

        if ($ticket->statut !== StatutTicket::Payer) {
            throw new \DomainException('Seul un ticket payé peut être mis en pause.');
        }

        return $this->changeStatus($ticket, StatutTicket::Pause);
    }

    // ─── Activation (dépause) ───────────────────────────────────────────

    public function activate(Ticket $ticket, VoyageInstance $newInstance, int $seatNumber): Ticket
    {
        $this->assertTicketOwnership($ticket);

        if ($ticket->statut !== StatutTicket::Pause) {
            throw new \DomainException('Seul un ticket en pause peut être réactivé.');
        }

        $originalCompagnieId = $ticket->voyageInstance->voyage->compagnie_id;
        $newCompagnieId      = $newInstance->voyage->compagnie_id;

        if ($originalCompagnieId !== $newCompagnieId) {
            throw new \DomainException('Le nouveau voyage doit appartenir à la même compagnie.');
        }

        $seatTaken = Ticket::where('voyage_instance_id', $newInstance->id)
            ->where('numero_chaise', $seatNumber)
            ->where('statut', '!=', StatutTicket::Annuler)
            ->exists();

        if ($seatTaken) {
            throw new \DomainException('Ce siège n\'est plus disponible.');
        }

        return DB::transaction(function () use ($ticket, $newInstance, $seatNumber) {
            $ticket->voyage_instance_id = $newInstance->id;
            $ticket->voyage_id          = $newInstance->voyage_id;
            $ticket->date               = $newInstance->date;
            $ticket->numero_chaise      = $seatNumber;
            $ticket->save();

            return $this->changeStatus($ticket->fresh(), StatutTicket::Payer);
        });
    }

    // ─── Transfert ───────────────────────────────────────────────────────

    /**
     * Transfère un ticket vers un autre utilisateur.
     */
    public function transfer(Ticket $ticket, User $recipient, string $password): Ticket
    {
        $this->assertTicketOwnership($ticket);

        if (!in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Pause])) {
            throw new \DomainException('Seul un ticket payé ou en pause peut être transféré.');
        }

        if (!\Hash::check($password, $ticket->user->password)) {
            throw new \DomainException('Mot de passe incorrect.');
        }

        return DB::transaction(function () use ($ticket, $recipient) {
            $ticket->is_my_ticket = false;
            $ticket->transferer_at = now();
            $ticket->transferer_a_user_id = $recipient->id;
            $ticket->save();

            TicketHelpers::regenerateTicket($ticket);
            TranfererTicketToOtherUserEvent::dispatch($ticket);

            return $ticket->fresh();
        });
    }

    // ─── Régénération ────────────────────────────────────────────────────

    /**
     * Régénère les codes QR/SMS d'un ticket et renvoie par mail.
     */
    public function regenerate(Ticket $ticket): bool
    {
        if (!in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Pause])) {
            throw new \DomainException('Le ticket est dans un état invalide pour la régénération.');
        }

        $result = TicketHelpers::regenerateTicket($ticket);

        if ($result) {
            PayementEffectuerEvent::dispatch($ticket);
            SendClientTicketByMailEvent::dispatch($ticket);
        }

        return $result;
    }

    // ─── Renvoi par mail ─────────────────────────────────────────────────

    public function resend(Ticket $ticket, string $notificationType): void
    {
        if (!in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::EnAttente, StatutTicket::Pause])) {
            throw new \DomainException('Le ticket est dans un état invalide pour le renvoi.');
        }

        PayementEffectuerEvent::dispatch($ticket);
        SendClientTicketByMailEvent::dispatch($ticket, $notificationType);
    }

    // ─── Helpers privés ──────────────────────────────────────────────────

    private function assertTicketOwnership(Ticket $ticket): void
    {
        if (!$ticket->is_my_ticket && $ticket->transferer_a_user_id !== null) {
            throw new \DomainException("Ce ticket n'est plus en votre possession.");
        }
    }
}
