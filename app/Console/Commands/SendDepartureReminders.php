<?php

namespace App\Console\Commands;

use App\Enums\StatutTicket;
use App\Enums\StatutVoyageInstance;
use App\Models\Voyage\VoyageInstance;
use App\Notifications\Ticket\DepartureReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDepartureReminders extends Command
{
    protected $signature = 'notifications:departure-reminders {--hours=2 : Fenêtre avant départ}';

    protected $description = 'Notifie les passagers des départs imminents (push + email + in-app)';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $dateHeure = "STR_TO_DATE(CONCAT(date,' ',TIME_FORMAT(heure,'%H:%i:%s')), '%Y-%m-%d %H:%i:%s')";

        // Instances partant dans la fenêtre, pas encore rappelées, non annulées.
        $instances = VoyageInstance::query()
            ->whereNull('rappel_depart_at')
            ->where('statut', '!=', StatutVoyageInstance::ANNULE)
            ->whereRaw("{$dateHeure} BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? HOUR)", [$hours])
            ->with(['voyage.trajet.depart', 'voyage.trajet.arriver', 'voyage.gareArriver', 'tickets.user'])
            ->get();

        $notified = 0;

        foreach ($instances as $instance) {
            $tickets = $instance->tickets
                ->whereIn('statut', [StatutTicket::Payer, StatutTicket::Valider]);

            foreach ($tickets as $ticket) {
                try {
                    $ticket->user?->notify(new DepartureReminderNotification($ticket, $instance));
                    $notified++;
                } catch (\Throwable $e) {
                    Log::warning('[departure-reminders] ticket ' . $ticket->id . ' : ' . $e->getMessage());
                }
            }

            $instance->update(['rappel_depart_at' => now()]);
        }

        $this->info("Rappels envoyés : {$notified} passager(s) sur {$instances->count()} départ(s).");

        return self::SUCCESS;
    }
}
