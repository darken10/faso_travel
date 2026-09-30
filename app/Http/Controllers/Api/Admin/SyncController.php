<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\StatutTicket;
use App\Enums\StatutVoyageInstance;
use App\Http\Controllers\Controller;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\VoyageInstance;
use App\Services\Sync\SyncCursor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SyncController extends Controller
{
    /** Nombre de tickets par page, et plafond imposable par le client. */
    private const DEFAULT_LIMIT = 200;
    private const MAX_LIMIT     = 500;

    /**
     * Alimente le cache local de l'application agent.
     *
     * GET /api/admin/sync/pull?since=<ISO8601>&cursor=<opaque>&limit=200
     *
     * Deux modes selon la présence de `since` :
     *   - absent  : instantané complet de la fenêtre (amorçage, ou reprise après
     *               une purge du cache local) ;
     *   - présent : uniquement ce qui a changé depuis, y compris les suppressions.
     *
     * La fenêtre couvre les voyages du jour et du lendemain : un agent doit
     * pouvoir embarquer un départ de nuit qui bascule sur la date suivante.
     *
     * Les tickets sont paginés en keyset. `last_sync_at` ne vaut quelque chose
     * qu'une fois la dernière page atteinte — tant que `cursor.has_more` est vrai,
     * il est null et le client ne doit pas avancer son horodatage de référence,
     * sous peine de ne jamais recevoir les pages suivantes.
     */
    public function pull(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'since'  => 'nullable|date',
            'cursor' => 'nullable|string|max:128',
            'limit'  => 'nullable|integer|min:1|max:' . self::MAX_LIMIT,
        ]);

        // Capturé AVANT toute lecture. Calculé après, un ticket modifié pendant la
        // requête porterait un updated_at antérieur à ce repère et ne serait
        // jamais reçu : le client repartirait de plus tard que sa modification.
        $syncedAt = now();

        $compagnieId = $request->user()->compagnie_id;

        if ($compagnieId === null) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non associé à une compagnie.',
            ], 403);
        }

        $since  = isset($validated['since']) ? Carbon::parse($validated['since']) : null;
        $cursor = SyncCursor::decode($validated['cursor'] ?? null);
        $limit  = (int) ($validated['limit'] ?? self::DEFAULT_LIMIT);

        $from = now()->startOfDay();
        $to   = now()->addDay()->endOfDay();

        [$tickets, $nextCursor] = $this->pullTickets((int) $compagnieId, $from, $to, $since, $cursor, $limit);
        $hasMore = $nextCursor !== null;

        return response()->json([
            'success' => true,
            // Repère de reprise de CET appel. Sur une pagination, le client retient
            // celui de la première page : c'est le plus ancien, donc le plus sûr.
            'synced_at'    => $syncedAt->toISOString(),
            // Conservé pour compatibilité : null tant qu'il reste des pages.
            'last_sync_at' => $hasMore ? null : $syncedAt->toISOString(),
            'data' => [
                // Les voyages ne se paginent pas : ne les renvoyer qu'à la première
                // page évite de les retransmettre à chaque page de tickets.
                'voyages' => $cursor === null
                    ? $this->pullVoyages((int) $compagnieId, $from, $to, $since)
                    : [],
                'tickets' => $tickets,
                'deleted' => [
                    // Les tickets sont supprimés en dur : aucune trace ne subsiste
                    // pour les signaler. Le client les purge en comparant à un
                    // instantané complet.
                    'voyages' => $this->deletedVoyages((int) $compagnieId, $since),
                ],
            ],
            'cursor' => [
                'next'     => $nextCursor?->encode(),
                'has_more' => $hasMore,
            ],
        ]);
    }

    /**
     * Voyages de la fenêtre. Non paginés : une compagnie en exploite quelques
     * dizaines sur deux jours, là où les tickets se comptent en milliers.
     */
    private function pullVoyages(int $compagnieId, Carbon $from, Carbon $to, ?Carbon $since): Collection
    {
        return VoyageInstance::query()
            ->tap(fn (Builder $q) => $this->scopeToWindow($q, $compagnieId, $from, $to))
            ->when($since, fn (Builder $q) => $q->where('updated_at', '>=', $since))
            ->with(['voyage.trajet.depart', 'voyage.trajet.arriver', 'voyage.compagnie', 'care'])
            ->withCount([
                // Deux requêtes agrégées au lieu de deux COUNT par voyage.
                'tickets as boarded_count' => fn (Builder $q) => $q->where('statut', StatutTicket::Valider),
                'tickets as ticket_count'  => fn (Builder $q) => $q->whereIn('statut', [
                    StatutTicket::Payer, StatutTicket::Valider, StatutTicket::Pause,
                ]),
            ])
            ->orderBy('date')
            ->get()
            ->map(fn (VoyageInstance $instance) => $this->formatVoyage($instance));
    }

    /**
     * Tickets de la fenêtre, paginés en keyset.
     *
     * @return array{0: Collection, 1: ?SyncCursor}
     */
    private function pullTickets(
        int $compagnieId,
        Carbon $from,
        Carbon $to,
        ?Carbon $since,
        ?SyncCursor $cursor,
        int $limit,
    ): array {
        $rows = Ticket::query()
            // Le modèle Ticket déclare $with = [user, payements, voyageInstance] :
            // sur une synchronisation en masse, cela chargerait les paiements de
            // chaque ticket pour rien.
            ->without(['user', 'payements', 'voyageInstance'])
            ->whereHas('voyageInstance', fn (Builder $q) => $this->scopeToWindow($q, $compagnieId, $from, $to))
            ->when($since, fn (Builder $q) => $q->where('updated_at', '>=', $since))
            ->when($cursor, fn (Builder $q) => $cursor->applyTo($q))
            ->with([
                'user:id,name,numero,numero_identifiant',
                'autre_personne:id,name,numero,numero_identifiant',
            ])
            ->orderBy('updated_at')
            ->orderBy('id')
            // Une ligne de plus que demandé : sa présence signale qu'il reste des
            // pages, sans avoir à compter l'ensemble du jeu de résultats.
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $page    = $hasMore ? $rows->take($limit) : $rows;
        $last    = $page->last();

        return [
            $page->map(fn (Ticket $ticket) => $this->formatTicket($ticket))->values(),
            $hasMore && $last ? new SyncCursor($last->updated_at, $last->id) : null,
        ];
    }

    /**
     * Voyages annulés par suppression depuis la dernière synchronisation.
     *
     * Sans cette liste, un voyage soft-deleted disparaîtrait simplement du pull
     * et resterait indéfiniment dans le cache de l'agent.
     */
    private function deletedVoyages(int $compagnieId, ?Carbon $since): array
    {
        // Un instantané complet ne contient que des voyages vivants : lister tous
        // ceux jamais supprimés serait sans borne et n'apprendrait rien au client,
        // qui ne les a de toute façon jamais reçus.
        if ($since === null) {
            return [];
        }

        return VoyageInstance::onlyTrashed()
            ->whereHas('voyage', fn (Builder $q) => $q->where('compagnie_id', $compagnieId))
            ->where('deleted_at', '>=', $since)
            ->pluck('id')
            ->all();
    }

    /** Restreint aux instances de la compagnie comprises dans la fenêtre J+0..J+1. */
    private function scopeToWindow(Builder $query, int $compagnieId, Carbon $from, Carbon $to): Builder
    {
        return $query
            ->whereHas('voyage', fn (Builder $q) => $q->where('compagnie_id', $compagnieId))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
    }

    private function formatTicket(Ticket $ticket): array
    {
        $isAutre   = $ticket->autre_personne_id !== null;
        $passager  = $isAutre ? $ticket->autre_personne : $ticket->user;

        return [
            'id'                 => $ticket->id,
            'voyage_instance_id' => $ticket->voyage_instance_id,
            'numero_ticket'      => $ticket->numero_ticket,

            // Le QR encode un secret brut de 32 caractères : le diffuser
            // reviendrait à placer sur chaque téléphone d'agent de quoi fabriquer
            // les billets de la compagnie. L'agent n'a besoin que de reconnaître
            // un code scanné, donc on ne transmet que son empreinte.
            'qr_hash'            => $ticket->code_qr ? hash('sha256', $ticket->code_qr) : null,
            // Même principe pour le code SMS. Six chiffres restent devinables par
            // force brute, mais aucun secret réutilisable ne quitte le serveur.
            'sms_hash'           => $ticket->code_sms ? hash('sha256', $ticket->code_sms) : null,

            'passenger_name'     => $passager?->name ?? '',
            // numero est un entier local ; l'indicatif est dans une colonne
            // separee. Renvoyer numero seul donnerait un numero inappelable.
            'passenger_phone'    => $this->formatPhone($passager?->numero_identifiant, $passager?->numero),
            'seat_number'        => $ticket->numero_chaise,
            'type'               => $ticket->type?->value,
            'statut'             => $ticket->statut->value,
            'status'             => $this->mapPassengerStatus($ticket->statut),
            'boarded_at'         => $ticket->valider_at,
            'updated_at'         => $ticket->updated_at,
        ];
    }

    /** Recompose un numero appelable a partir de l'indicatif et du numero local. */
    private function formatPhone(?string $indicatif, int|string|null $numero): string
    {
        if ($numero === null || $numero === '') {
            return '';
        }

        return trim(($indicatif ?? '') . ' ' . $numero);
    }

    private function mapPassengerStatus(StatutTicket $statut): string
    {
        return match ($statut) {
            StatutTicket::Valider                    => 'boarded',
            StatutTicket::Payer, StatutTicket::Pause => 'pending',
            default                                  => 'cancelled',
        };
    }

    private function formatVoyage(VoyageInstance $instance): array
    {
        $voyage = $instance->voyage;
        $trajet = $voyage?->trajet;

        return [
            'id'             => $instance->id,
            // Le voyage n'a pas de colonne reference : l'ancien repli sur l'uuid de
            // l'instance affichait un identifiant illisible dans l'application.
            'numero_voyage'  => $voyage?->reference ?? '',
            'departure_time' => $instance->date->format('Y-m-d') . 'T' . ($instance->heure ? $instance->heure->format('H:i:s') : '00:00:00'),
            'status'         => $this->mapStatut($instance->statut),
            'total_seats'    => $instance->nb_place,
            'boarded_count'  => $instance->boarded_count ?? 0,
            'ticket_count'   => $instance->ticket_count ?? 0,
            'departure'      => ['id' => $trajet?->depart?->id ?? 0, 'name' => $trajet?->depart?->name ?? ''],
            'arrival'        => ['id' => $trajet?->arriver?->id ?? 0, 'name' => $trajet?->arriver?->name ?? ''],
            'compagnie'      => ['id' => $voyage?->compagnie?->id ?? 0, 'name' => $voyage?->compagnie?->name ?? ''],
            'vehicle'        => $instance->care ? [
                'id'              => $instance->care->id,
                'immatriculation' => $instance->care->immatrculation,
                'capacity'        => $instance->care->number_place,
                'numero'          => $instance->care->numero,
            ] : null,
            'updated_at'     => $instance->updated_at,
        ];
    }

    private function mapStatut(?StatutVoyageInstance $statut): string
    {
        return match ($statut) {
            StatutVoyageInstance::DISPONIBLE => 'boarding',
            StatutVoyageInstance::INACTIF    => 'departed',
            StatutVoyageInstance::ANNULE     => 'cancelled',
            StatutVoyageInstance::RETARDE    => 'scheduled',
            default                          => 'scheduled',
        };
    }
}
