<?php

namespace App\Services\Ticket;

use App\Enums\StatutTicket;
use App\Enums\StatutVoyageInstance;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Traitement des billets restés en suspens après le départ.
 *
 * Un billet payé mais jamais scanné correspond à un voyageur qui ne s'est pas
 * présenté. Le laisser au statut « Payer » indéfiniment fausse les rapports et
 * empêche le voyageur de reporter son trajet : on le bascule en « Pause », état
 * depuis lequel {@see TicketCommandService::activate()} permet de le réaffecter
 * à un autre départ.
 */
class TicketExpirationService
{
    /** Heures de battement laissées à l'agent pour scanner après le départ. */
    public const DELAI_GRACE_HEURES = 3;

    public function __construct(private readonly TicketCommandService $commandService) {}

    /**
     * Billets payés dont le départ est passé depuis plus de `$graceHours`.
     *
     * Les voyages annulés sont écartés : ils relèvent du remboursement, pas de
     * la mise en pause.
     */
    public function ticketsNonConsommes(int $graceHours = self::DELAI_GRACE_HEURES): Collection
    {
        return $this->queryNonConsommes($graceHours)
            ->with(['voyageInstance.voyage.compagnie', 'user'])
            ->get();
    }

    /**
     * Met en pause les billets non consommés.
     *
     * Chaque billet est traité isolément : l'échec de l'un ne doit pas
     * interrompre le lot, une tâche planifiée devant toujours aller au bout.
     *
     * @return array{paused: int, failed: int, total: int}
     */
    public function pauseNonConsommes(int $graceHours = self::DELAI_GRACE_HEURES): array
    {
        $tickets = $this->ticketsNonConsommes($graceHours);
        $paused = 0;
        $failed = 0;

        foreach ($tickets as $ticket) {
            try {
                $this->commandService->pause($ticket);
                $paused++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning("[tickets:pause-non-consommes] ticket {$ticket->id} : {$e->getMessage()}");
            }
        }

        return ['paused' => $paused, 'failed' => $failed, 'total' => $tickets->count()];
    }

    /** @return Builder<Ticket> */
    private function queryNonConsommes(int $graceHours): Builder
    {
        // `tickets` porte aussi une colonne `date` : les colonnes de l'instance
        // doivent être préfixées, sinon MySQL lève une ambiguïté.
        $departAt = "STR_TO_DATE(CONCAT(voyage_instances.date, ' ', "
            ."TIME_FORMAT(COALESCE(voyage_instances.heure, '00:00:00'), '%H:%i:%s')), '%Y-%m-%d %H:%i:%s')";

        return Ticket::query()
            ->where('statut', StatutTicket::Payer)
            ->whereHas('voyageInstance', fn (Builder $q) => $q
                ->where('statut', '!=', StatutVoyageInstance::ANNULE)
                ->whereRaw("{$departAt} < DATE_SUB(NOW(), INTERVAL ? HOUR)", [$graceHours]));
    }
}
