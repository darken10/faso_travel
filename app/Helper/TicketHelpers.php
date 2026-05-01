<?php

namespace App\Helper;

use App\Enums\StatutTicket;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class TicketHelpers
{
    public static function generateTicketNumber(): string
    {
        return 'TK ' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function generateTicketCodeSms(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function generateTicketCodeQr(): string
    {
        return now()->format('ymdHi') . Ulid::generate(now()) . Ulid::generate(now());
    }

    /**
     * Attribue le prochain numéro de siège disponible de façon atomique.
     * Utilise un verrou pessimiste pour éviter les race conditions.
     */
    public static function getNumeroChaise(VoyageInstance $voyage): int
    {
        return DB::transaction(function () use ($voyage) {
            $instance = VoyageInstance::lockForUpdate()->findOrFail($voyage->id);

            $takenSeats = Ticket::where('voyage_instance_id', $instance->id)
                ->whereNotIn('statut', [StatutTicket::Annuler])
                ->pluck('numero_chaise')
                ->toArray();

            $maxSeats = $instance->nb_place
                ?? $instance->care?->number_place
                ?? $instance->voyage?->cares?->last()?->number_place
                ?? 50;

            for ($seat = 1; $seat <= $maxSeats; $seat++) {
                if (!in_array($seat, $takenSeats)) {
                    return $seat;
                }
            }

            throw new \RuntimeException('Aucune place disponible pour ce voyage.');
        });
    }

    public static function regenerateTicket(Ticket $ticket): bool
    {
        try {
            DB::beginTransaction();
            $ticket->image_uri  = null;
            $ticket->code_qr    = self::generateTicketCodeQr();
            $ticket->code_sms   = self::generateTicketCodeSms();
            $ticket->code_qr_uri = null;
            $ticket->pdf_uri    = null;
            $ticket->save();
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return false;
        }

        return true;
    }

    public static function getEmailToSendMail(Ticket $ticket): ?string
    {
        if ($ticket->is_my_ticket) {
            return $ticket->user?->email;
        }

        if ($ticket->autre_personne_id !== null) {
            return $ticket->autre_personne?->email ?? $ticket->user?->email;
        }

        if ($ticket->transferer_a_user_id !== null) {
            return User::find($ticket->transferer_a_user_id)?->email;
        }

        return $ticket->user?->email;
    }
}
