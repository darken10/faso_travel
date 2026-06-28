<?php

namespace App\Services\Report;

use App\Enums\StatutPayement;
use App\Enums\StatutTicket;
use App\Models\Finance\Depense;
use App\Models\Finance\Recette;
use App\Models\Ticket\Payement;
use App\Models\Ticket\Ticket;
use Carbon\Carbon;

class ReportService
{
    /**
     * Agrège toutes les statistiques d'une compagnie sur une période.
     *
     * @return array<string, mixed>
     */
    public function data(int $compagnieId, Carbon $start, Carbon $end): array
    {
        $start = $start->copy()->startOfDay();
        $end   = $end->copy()->endOfDay();

        // ── Tickets payés/validés de la période (par date de vente) ────────────
        $tickets = Ticket::withoutGlobalScopes()
            ->whereHas('voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->whereIn('statut', [StatutTicket::Payer, StatutTicket::Valider])
            ->whereBetween('created_at', [$start, $end])
            ->with([
                'user:id,first_name,last_name,name',
                'payements:id,ticket_id,montant',
                'voyageInstance.voyage.trajet.depart:id,name',
                'voyageInstance.voyage.trajet.arriver:id,name',
            ])
            ->get();

        $revenueBilletterie = $tickets->sum(fn ($t) => $t->payements->sum('montant'));
        $ticketsCount       = $tickets->count();

        // ── Top trajets (nb tickets + recette + remplissage) ───────────────────
        $topTrajets = $tickets
            ->groupBy(fn ($t) => ($t->voyageInstance?->voyage?->trajet?->depart?->name ?? '—')
                . ' → ' . ($t->voyageInstance?->voyage?->trajet?->arriver?->name ?? '—'))
            ->map(fn ($grp, $label) => [
                'trajet'  => $label,
                'tickets' => $grp->count(),
                'recette' => $grp->sum(fn ($t) => $t->payements->sum('montant')),
            ])
            ->sortByDesc('recette')
            ->values()
            ->all();

        // ── Ventes par agent ───────────────────────────────────────────────────
        $parAgent = $tickets
            ->groupBy('user_id')
            ->map(fn ($grp) => [
                'agent'   => optional($grp->first()->user)->name
                    ?? trim((optional($grp->first()->user)->first_name ?? '') . ' ' . (optional($grp->first()->user)->last_name ?? ''))
                    ?: '—',
                'tickets' => $grp->count(),
                'montant' => $grp->sum(fn ($t) => $t->payements->sum('montant')),
            ])
            ->sortByDesc('montant')
            ->values()
            ->all();

        // ── Finances ───────────────────────────────────────────────────────────
        $recettesManuelles = (int) Recette::where('compagnie_id', $compagnieId)
            ->whereBetween('date_recette', [$start->toDateString(), $end->toDateString()])
            ->sum('montant');

        $depenses = Depense::where('compagnie_id', $compagnieId)
            ->whereBetween('date_depense', [$start->toDateString(), $end->toDateString()])
            ->with('categorie:id,nom')
            ->get();

        $totalDepenses = (int) $depenses->sum('montant');

        $depensesParCategorie = $depenses
            ->groupBy(fn ($d) => $d->categorie?->nom ?? 'Sans catégorie')
            ->map(fn ($grp, $nom) => ['categorie' => $nom, 'montant' => (int) $grp->sum('montant')])
            ->sortByDesc('montant')
            ->values()
            ->all();

        $totalRecettes = (int) $revenueBilletterie + $recettesManuelles;
        $benefice      = $totalRecettes - $totalDepenses;

        return [
            'start'                => $start,
            'end'                  => $end,
            'revenueBilletterie'   => (int) $revenueBilletterie,
            'recettesManuelles'    => $recettesManuelles,
            'totalRecettes'        => $totalRecettes,
            'totalDepenses'        => $totalDepenses,
            'benefice'             => $benefice,
            'ticketsCount'         => $ticketsCount,
            'topTrajets'           => $topTrajets,
            'parAgent'             => $parAgent,
            'depensesParCategorie' => $depensesParCategorie,
        ];
    }

    /**
     * Paiements de la période (pour l'export comptable).
     */
    public function paiementsQuery(int $compagnieId, Carbon $start, Carbon $end)
    {
        return Payement::query()
            ->whereHas('ticket.voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->where('statut', StatutPayement::Complete->value)
            ->with([
                'ticket.user', 'ticket.autre_personne',
                'ticket.voyageInstance.voyage.trajet.depart',
                'ticket.voyageInstance.voyage.trajet.arriver',
            ])
            ->latest();
    }
}
