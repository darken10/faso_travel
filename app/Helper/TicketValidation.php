<?php

namespace App\Helper;

use App\Enums\StatutTicket;
use App\Enums\TypeTicket;
use App\Events\Admin\TicketValiderEvent;
use App\Events\Ticket\TicketActiveEvent;
use App\Events\Ticket\TicketBlockerEvent;
use App\Events\Ticket\TicketPauseEvent;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketValidation
{
    public static function valider(Ticket $ticket): bool
    {
        DB::beginTransaction();
        try {
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
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if ($ticket->statut === StatutTicket::Valider
            || ($ticket->statut === StatutTicket::Pause && $ticket->type === TypeTicket::RetourSimple)
        ) {
            TicketValiderEvent::dispatch($ticket);
            return true;
        }

        return false;
    }

    public static function searchTicketByNumberAndCodeSMS(string $numero, string $codeSMS): ?Ticket
    {
        $user = User::where('numero', $numero)->first();

        $tickets = Ticket::where('user_id', $user?->id)
            ->orWhere('numero_ticket', 'TK ' . $numero)
            ->get();

        if ($tickets->isEmpty()) {
            return null;
        }

        $ticket = $tickets->where('code_sms', $codeSMS)->last();

        return ($ticket instanceof Ticket) ? $ticket : null;
    }

    public static function bloque(Ticket $ticket): bool
    {
        DB::beginTransaction();
        try {
            $ticket->statut = StatutTicket::Bloquer;
            $ticket->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if ($ticket->statut === StatutTicket::Bloquer) {
            TicketBlockerEvent::dispatch($ticket);
            return true;
        }

        return false;
    }

    public static function pause(Ticket $ticket): bool
    {
        DB::beginTransaction();
        try {
            $ticket->statut = StatutTicket::Pause;
            $ticket->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if ($ticket->statut === StatutTicket::Pause) {
            TicketPauseEvent::dispatch($ticket);
            return true;
        }

        return false;
    }

    public static function active(Ticket $ticket): bool
    {
        DB::beginTransaction();
        try {
            $ticket->statut = StatutTicket::Payer;
            $ticket->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        if ($ticket->statut === StatutTicket::Payer) {
            TicketActiveEvent::dispatch($ticket);
            return true;
        }

        return false;
    }
}
