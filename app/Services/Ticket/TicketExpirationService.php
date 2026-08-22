<?php

namespace App\Services\Ticket;

use App\Enums\CompagnieSettingKey;
use App\Enums\StatutTicket;
use App\Enums\StatutVoyageInstance;
use App\Models\Ticket\Ticket;
use App\Services\Compagnie\CompagnieSettingService;
use Carbon\Carbon;
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
 *
 * Le délai de battement est propre à chaque compagnie et se règle depuis le
 * panel d'administration (groupe « Avancé »), une compagnie de courtes liaisons
 * urbaines n'ayant pas les mêmes marges qu'une compagnie longue distance.
 */
class TicketExpirationService
{
    public function __construct(
        private readonly TicketCommandService $commandService,
        private readonly CompagnieSettingService $settings,
    ) {}

    /**
     * Billets payés dont le départ est passé depuis plus longtemps que le
     * battement accordé à leur compagnie.
     *
     * @param  int|null  $graceHoursOverride  Force un battement identique pour
     *                                        toutes les compagnies (rattrapage
     *                                        ponctuel en ligne de commande).
     * @return Collection<int, Ticket>
     */
    public function ticketsNonConsommes(?int $graceHoursOverride = null): Collection
    {
        return $this->queryDepartPasse()
            ->with(['voyageInstance.voyage.compagnie', 'user'])
            ->get()
            ->filter(fn (Ticket $ticket) => $this->battementEcoule($ticket, $graceHoursOverride))
            ->values();
    }

    /**
     * Met en pause les billets non consommés.
     *
     * Chaque billet est traité isolément : l'échec de l'un ne doit pas
     * interrompre le lot, une tâche planifiée devant toujours aller au bout.
     *
     * @return array{paused: int, failed: int, total: int}
     */
    public function pauseNonConsommes(?int $graceHoursOverride = null): array
    {
        $tickets = $this->ticketsNonConsommes($graceHoursOverride);
        $paused = 0;
        $failed = 0;

        foreach ($tickets as $ticket) {
            try {
                $this->commandService->pause($ticket, automatique: true);
                $paused++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning("[tickets:pause-non-consommes] ticket {$ticket->id} : {$e->getMessage()}");
            }
        }

        return ['paused' => $paused, 'failed' => $failed, 'total' => $tickets->count()];
    }

    /** Battement accordé à la compagnie qui opère le voyage du billet. */
    public function battementPour(Ticket $ticket): int
    {
        $compagnieId = $ticket->voyageInstance?->voyage?->compagnie_id;

        if (! $compagnieId) {
            return (int) CompagnieSettingKey::DELAI_PAUSE_NON_CONSOMME->default();
        }

        return (int) $this->settings->get($compagnieId, CompagnieSettingKey::DELAI_PAUSE_NON_CONSOMME);
    }

    /** Date et heure de départ effectives du billet. */
    public function departAt(Ticket $ticket): ?Carbon
    {
        $instance = $ticket->voyageInstance;

        if (! $instance?->date) {
            return null;
        }

        $heure = $instance->heure ? Carbon::parse($instance->heure)->format('H:i:s') : '00:00:00';

        return Carbon::parse(Carbon::parse($instance->date)->toDateString().' '.$heure);
    }

    /** Le battement accordé est-il écoulé pour ce billet ? */
    private function battementEcoule(Ticket $ticket, ?int $graceHoursOverride): bool
    {
        $depart = $this->departAt($ticket);

        if (! $depart) {
            return false;
        }

        $battement = $graceHoursOverride ?? $this->battementPour($ticket);

        return $depart->copy()->addHours($battement)->isPast();
    }

    /**
     * Billets payés dont le départ est déjà passé, tous battements confondus.
     *
     * Le filtrage fin se fait ensuite en PHP, le battement dépendant de la
     * compagnie : une seule requête suffit, au lieu d'une par compagnie.
     *
     * @return Builder<Ticket>
     */
    private function queryDepartPasse(): Builder
    {
        // `tickets` porte aussi une colonne `date` : les colonnes de l'instance
        // doivent être préfixées, sinon MySQL lève une ambiguïté.
        $departAt = "STR_TO_DATE(CONCAT(voyage_instances.date, ' ', "
            ."TIME_FORMAT(COALESCE(voyage_instances.heure, '00:00:00'), '%H:%i:%s')), '%Y-%m-%d %H:%i:%s')";

        return Ticket::query()
            ->where('statut', StatutTicket::Payer)
            ->whereHas('voyageInstance', fn (Builder $q) => $q
                ->where('statut', '!=', StatutVoyageInstance::ANNULE)
                ->whereRaw("{$departAt} < NOW()"));
    }
}
