<?php

namespace App\Console\Commands;

use App\Services\Ticket\TicketExpirationService;
use Illuminate\Console\Command;

/**
 * Bascule en « Pause » les billets payés qui n'ont jamais été scannés et dont
 * le voyage est parti. Planifiée dans `routes/console.php`.
 *
 * Le battement appliqué est celui réglé par l'administrateur pour chaque
 * compagnie ; `--hours` ne sert qu'à forcer une valeur commune le temps d'un
 * rattrapage manuel.
 */
class PauseTicketsNonConsommes extends Command
{
    protected $signature = 'tickets:pause-non-consommes
                            {--hours= : Force un battement commun, en heures (par défaut : réglage de chaque compagnie)}
                            {--dry-run : Simuler sans modifier la base}';

    protected $description = 'Met en pause les tickets payés dont le voyage est passé sans avoir été validés';

    public function handle(TicketExpirationService $service): int
    {
        $override = $this->option('hours');
        $override = $override === null || $override === '' ? null : (int) $override;

        if ($override !== null && $override < 0) {
            $this->error('Le délai de battement ne peut pas être négatif.');

            return self::FAILURE;
        }

        $this->info($override === null
            ? 'Recherche des tickets non consommés, selon le battement réglé par compagnie...'
            : "Recherche des tickets non consommés, battement forcé à {$override}h...");

        if ($this->option('dry-run')) {
            return $this->simuler($service, $override);
        }

        $bilan = $service->pauseNonConsommes($override);

        if ($bilan['total'] === 0) {
            $this->info('Aucun ticket non consommé à mettre en pause.');

            return self::SUCCESS;
        }

        $this->info("{$bilan['paused']} ticket(s) mis en pause.");

        if ($bilan['failed'] > 0) {
            $this->warn("{$bilan['failed']} ticket(s) non traités — voir les logs.");
        }

        return self::SUCCESS;
    }

    /** Affiche ce qui serait modifié, sans rien écrire. */
    private function simuler(TicketExpirationService $service, ?int $override): int
    {
        $tickets = $service->ticketsNonConsommes($override);

        if ($tickets->isEmpty()) {
            $this->info('Aucun ticket non consommé à mettre en pause.');

            return self::SUCCESS;
        }

        $this->warn("{$tickets->count()} ticket(s) seraient mis en pause.");

        $this->table(
            ['ID', 'Numéro', 'Compagnie', 'Départ prévu', 'Battement'],
            $tickets->map(fn ($ticket) => [
                $ticket->id,
                $ticket->numero_ticket,
                $ticket->voyageInstance?->voyage?->compagnie?->name ?? '—',
                $service->departAt($ticket)?->format('d/m/Y à H:i') ?? '—',
                ($override ?? $service->battementPour($ticket)).'h',
            ]),
        );

        $this->info('Mode dry-run : aucune modification effectuée.');

        return self::SUCCESS;
    }
}
