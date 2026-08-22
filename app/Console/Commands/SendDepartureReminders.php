<?php

namespace App\Console\Commands;

use App\Enums\RappelDepart;
use App\Services\Ticket\RappelDepartService;
use Illuminate\Console\Command;

/**
 * Rappels de départ, tous paliers confondus.
 *
 * Les paliers sont fixes ; c'est chaque compagnie qui décide, depuis le panel,
 * lesquels elle active et à quelle avance ils partent.
 */
class SendDepartureReminders extends Command
{
    protected $signature = 'notifications:departure-reminders
                            {--palier= : Limite l\'envoi à un palier (veille|avant_depart|embarquement)}';

    protected $description = 'Notifie les passagers des départs à venir (push + email + in-app)';

    public function handle(RappelDepartService $service): int
    {
        $paliers = $this->paliersDemandes();

        if ($paliers === null) {
            $this->error('Palier inconnu. Valeurs acceptées : '
                .implode(', ', array_column(RappelDepart::cases(), 'value')));

            return self::FAILURE;
        }

        $total = 0;

        foreach ($paliers as $palier) {
            $bilan = $service->envoyerPalier($palier);
            $total += $bilan['notifies'];

            $this->line(sprintf(
                '  %-14s %d passager(s) sur %d départ(s)',
                $palier->value,
                $bilan['notifies'],
                $bilan['departs'],
            ));
        }

        $this->info("Rappels envoyés : {$total} message(s).");

        return self::SUCCESS;
    }

    /** @return array<int, RappelDepart>|null */
    private function paliersDemandes(): ?array
    {
        $demande = $this->option('palier');

        if (! $demande) {
            return RappelDepart::ordonnes();
        }

        $palier = RappelDepart::tryFrom($demande);

        return $palier ? [$palier] : null;
    }
}
