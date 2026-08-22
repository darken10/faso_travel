<?php

namespace App\Console\Commands;

use App\Services\Ticket\TicketExpirationService;
use Illuminate\Console\Command;

/**
 * Bascule en « Pause » les billets payés qui n'ont jamais été scannés et dont
 * le voyage est parti. Planifiée dans `routes/console.php`.
 */
class PauseTicketsNonConsommes extends Command
{
    protected $signature = 'tickets:pause-non-consommes
                            {--hours= : Heures de battement après le départ avant mise en pause}
                            {--dry-run : Simuler sans modifier la base}';

    protected $description = 'Met en pause les tickets payés dont le voyage est passé sans avoir été validés';

    public function handle(TicketExpirationService $service): int
    {
        $hours = (int) ($this->option('hours') ?: TicketExpirationService::DELAI_GRACE_HEURES);

        if ($hours < 0) {
            $this->error('Le délai de battement ne peut pas être négatif.');

            return self::FAILURE;
        }

        $this->info("Recherche des tickets payés dont le départ remonte à plus de {$hours}h...");

        if ($this->option('dry-run')) {
            return $this->simuler($service, $hours);
        }

        $bilan = $service->pauseNonConsommes($hours);

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
    private function simuler(TicketExpirationService $service, int $hours): int
    {
        $tickets = $service->ticketsNonConsommes($hours);

        if ($tickets->isEmpty()) {
            $this->info('Aucun ticket non consommé à mettre en pause.');

            return self::SUCCESS;
        }

        $this->warn("{$tickets->count()} ticket(s) seraient mis en pause.");

        $this->table(
            ['ID', 'Numéro', 'Compagnie', 'Départ prévu'],
            $tickets->map(fn ($ticket) => [
                $ticket->id,
                $ticket->numero_ticket,
                $ticket->voyageInstance?->voyage?->compagnie?->name ?? '—',
                // `date` et `heure` sont tous deux castés en datetime : on ne
                // garde de chacun que la partie utile, sinon la cellule affiche
                // deux horodatages complets accolés.
                $ticket->voyageInstance
                    ? $ticket->voyageInstance->date?->format('d/m/Y')
                        .' à '.($ticket->voyageInstance->heure?->format('H:i') ?? '--:--')
                    : '—',
            ]),
        );

        $this->info('Mode dry-run : aucune modification effectuée.');

        return self::SUCCESS;
    }
}
