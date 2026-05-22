<?php

namespace App\Services\Ticket;

use App\Enums\StatutTicket;
use App\Enums\TypeTicket;
use App\Events\Admin\TicketValiderEvent;
use App\Events\Ticket\TicketActiveEvent;
use App\Events\Ticket\TicketBlockerEvent;
use App\Events\Ticket\TicketPauseEvent;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Service dédié à la validation physique des tickets (côté contrôleur/administrateur).
 * Gère la logique aller-retour et les recherches par QR/SMS.
 */
class TicketValidationService
{
    /**
     * Valide un ticket : gère la logique aller simple, aller-retour et retour simple.
     */
    public function validate(Ticket $ticket): bool
    {
        return DB::transaction(function () use ($ticket) {
            if ($ticket->type === TypeTicket::AllerRetour) {
                $ticket->statut = StatutTicket::Pause;
                $ticket->type = TypeTicket::RetourSimple;
                $ticket->valider_by_id = Auth::id();
                $ticket->valider_at = now();
            } elseif ($ticket->type === TypeTicket::RetourSimple) {
                $ticket->statut = StatutTicket::Valider;
                $ticket->retour_validate_at = now();
                $ticket->retour_validate_by = Auth::id();
            } else {
                $ticket->statut = StatutTicket::Valider;
                $ticket->valider_by_id = Auth::id();
                $ticket->valider_at = now();
            }

            $ticket->save();

            $shouldDispatch = $ticket->statut === StatutTicket::Valider
                || ($ticket->statut === StatutTicket::Pause && $ticket->type === TypeTicket::RetourSimple);

            if ($shouldDispatch) {
                TicketValiderEvent::dispatch($ticket);
            }

            return $shouldDispatch;
        });
    }

    /**
     * Recherche un ticket par numéro de téléphone ou numéro de ticket + code SMS.
     */
    public function searchByNumberAndCodeSMS(string $numero, string $codeSMS): ?Ticket
    {
        $user = User::where('numero', $numero)->first();

        $tickets = Ticket::query()
            ->where('user_id', $user?->id)
            ->orWhere('numero_ticket', 'TK ' . $numero)
            ->get();

        if ($tickets->isEmpty()) {
            return null;
        }

        return $tickets->where('code_sms', $codeSMS)->last();
    }

    /**
     * Bloque un ticket.
     */
    public function block(Ticket $ticket): bool
    {
        return DB::transaction(function () use ($ticket) {
            $ticket->statut = StatutTicket::Bloquer;
            $ticket->save();

            if ($ticket->statut === StatutTicket::Bloquer) {
                TicketBlockerEvent::dispatch($ticket);
                return true;
            }

            return false;
        });
    }

    /**
     * Met un ticket en pause.
     */
    public function pause(Ticket $ticket): bool
    {
        return DB::transaction(function () use ($ticket) {
            $ticket->statut = StatutTicket::Pause;
            $ticket->save();

            if ($ticket->statut === StatutTicket::Pause) {
                TicketPauseEvent::dispatch($ticket);
                return true;
            }

            return false;
        });
    }

    /**
     * Active un ticket (le remet en "Payer" via l'événement Active).
     */
    public function activate(Ticket $ticket): bool
    {
        return DB::transaction(function () use ($ticket) {
            $ticket->statut = StatutTicket::Pause;
            $ticket->save();

            if ($ticket->statut === StatutTicket::Pause) {
                TicketActiveEvent::dispatch($ticket);
                return true;
            }

            return false;
        });
    }
}
