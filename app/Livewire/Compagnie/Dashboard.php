<?php

namespace App\Livewire\Compagnie;

use App\Enums\StatutCaisse;
use App\Enums\StatutPayement;
use App\Enums\StatutTicket;
use App\Enums\StatutVoyageInstance;
use App\Helper\QueryHelpers;
use App\Models\Compagnie\Gare;
use App\Models\Finance\Caisse;
use App\Models\Finance\Depense;
use App\Models\Finance\Recette;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.compagnie-panel')]
class Dashboard extends Component
{
    public function render()
    {
        $compagnieId = Auth::user()?->compagnie_id;

        $dateHeure = "STR_TO_DATE(CONCAT(date,' ',TIME_FORMAT(heure,'%H:%i:%s')), '%Y-%m-%d %H:%i:%s')";

        // ── KPIs opérationnels du jour ──
        $ventesJour = Ticket::withoutGlobalScopes()
            ->whereHas('voyageInstance.voyage', fn($q) => $q->where('compagnie_id', $compagnieId))
            ->whereIn('statut', [StatutTicket::Payer, StatutTicket::Valider])
            ->whereDate('created_at', today())
            ->count();

        $recetteJour = QueryHelpers::AllPaymentsOfMyCompagnie(StatutPayement::Complete, [StatutTicket::Payer, StatutTicket::Valider])
            ->whereDate('created_at', today())
            ->sum('montant');

        // ── Vue agent : mes ventes + ma caisse ──
        $mesVentesJour = Ticket::withoutGlobalScopes()
            ->where('user_id', Auth::id())
            ->whereIn('statut', [StatutTicket::Payer, StatutTicket::Valider])
            ->whereDate('created_at', today())
            ->count();
        $maCaisse = Caisse::sessionOuverte();

        // ── Prochains départs (7 prochains jours) ──
        $upcoming = VoyageInstance::query()
            ->whereHas('voyage', fn($q) => $q->where('compagnie_id', $compagnieId))
            ->with(['voyage.trajet.depart', 'voyage.trajet.arriver', 'care'])
            ->withCount(['tickets as occupied_count' => fn($q) => $q->where('statut', '!=', StatutTicket::Annuler)])
            ->where('statut', '!=', StatutVoyageInstance::ANNULE)
            ->whereRaw("{$dateHeure} >= NOW()")
            ->whereRaw("{$dateHeure} <= DATE_ADD(NOW(), INTERVAL 7 DAY)")
            ->orderByRaw("{$dateHeure} ASC")
            ->get();

        $prochainsDeparts = $upcoming->take(6);

        // Taux de remplissage moyen des départs à venir (7 j).
        $placesTotales = $upcoming->sum('nb_place');
        $tauxRemplissage = $placesTotales > 0
            ? (int) round($upcoming->sum('occupied_count') / $placesTotales * 100)
            : 0;

        // ── Alertes ──
        $caissesNonCloturees = Caisse::where('compagnie_id', $compagnieId)
            ->where('statut', StatutCaisse::Ouverte->value)
            ->whereDate('opened_at', '<', today())
            ->count();

        // Départs sous-remplis : dans les 48h et < 30% d'occupation.
        $departsSousRemplis = $upcoming
            ->filter(fn($i) => $i->nb_place > 0
                && ($i->occupied_count / $i->nb_place) < 0.30
                && $i->getHeureDepart()->lte(now()->addHours(48)))
            ->count();

        // ── Stat cards ──
        $totalVoyages = QueryHelpers::AllVoyagesOfMyCompagnie()->count();
        $totalGares   = Gare::where('compagnie_id', $compagnieId)->count();
        $totalPosts   = QueryHelpers::AllPostsOfMyCompagnie()->count();
        $totalUsers   = QueryHelpers::AllUsersOfMyCompagnie()->count();

        $ticketsPayes   = QueryHelpers::AllTicketOfMyCompagnie()->count();
        $ticketsValides = QueryHelpers::AllTicketOfMyCompagnie(StatutTicket::Valider)->count();
        $ticketsBloques = QueryHelpers::AllTicketOfMyCompagnie(StatutTicket::Bloquer)->count();

        // ── Finance ──
        $recetteTickets    = QueryHelpers::AllPaymentsOfMyCompagnie(StatutPayement::Complete, [StatutTicket::Payer, StatutTicket::Valider])->sum('montant');
        $recetteManuelles  = Recette::where('compagnie_id', $compagnieId)->sum('montant');
        $totalRecettes     = $recetteTickets + $recetteManuelles;
        $totalDepenses     = Depense::where('compagnie_id', $compagnieId)->sum('montant');
        $solde             = $totalRecettes - $totalDepenses;

        $depensesMois      = Depense::where('compagnie_id', $compagnieId)
            ->whereMonth('date_depense', now()->month)
            ->whereYear('date_depense', now()->year)
            ->sum('montant');

        $recettesMois = QueryHelpers::AllPaymentsOfMyCompagnie(StatutPayement::Complete, [StatutTicket::Payer, StatutTicket::Valider])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('montant')
            + Recette::where('compagnie_id', $compagnieId)
            ->whereMonth('date_recette', now()->month)
            ->whereYear('date_recette', now()->year)
            ->sum('montant');

        // ── Chart: 6-month recettes vs dépenses ──
        $chartLabels   = [];
        $chartRecettes = [];
        $chartDepenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $month = $date->month;
            $year  = $date->year;

            $chartLabels[] = $date->translatedFormat('M Y');

            $ticketRev = QueryHelpers::AllPaymentsOfMyCompagnie(StatutPayement::Complete, [StatutTicket::Payer, StatutTicket::Valider])
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->sum('montant');

            $manualRev = Recette::where('compagnie_id', $compagnieId)
                ->whereMonth('date_recette', $month)
                ->whereYear('date_recette', $year)
                ->sum('montant');

            $chartRecettes[] = $ticketRev + $manualRev;

            $chartDepenses[] = Depense::where('compagnie_id', $compagnieId)
                ->whereMonth('date_depense', $month)
                ->whereYear('date_depense', $year)
                ->sum('montant');
        }

        // ── Doughnut: dépenses by category (top 8) ──
        $depensesCategories = Depense::withoutGlobalScopes()
            ->where('depenses.compagnie_id', $compagnieId)
            ->join('categorie_depenses', 'depenses.categorie_depense_id', '=', 'categorie_depenses.id')
            ->selectRaw('categorie_depenses.nom, SUM(depenses.montant) as total')
            ->groupBy('categorie_depenses.id', 'categorie_depenses.nom')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $doughnutLabels = $depensesCategories->pluck('nom')->toArray();
        $doughnutData   = $depensesCategories->pluck('total')->toArray();

        // ── Carte : gares géolocalisées ──
        $garesGeo = Gare::where('compagnie_id', $compagnieId)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->with('ville:id,name')
            ->get(['id', 'name', 'lat', 'lng', 'ville_id'])
            ->map(fn($g) => [
                'name'  => $g->name,
                'ville' => $g->ville?->name ?? '',
                'lat'   => (float) $g->lat,
                'lng'   => (float) $g->lng,
            ]);

        return view('livewire.compagnie.dashboard', compact(
            'ventesJour', 'recetteJour', 'tauxRemplissage', 'prochainsDeparts',
            'caissesNonCloturees', 'departsSousRemplis', 'mesVentesJour', 'maCaisse',
            'totalVoyages', 'totalGares', 'totalPosts', 'totalUsers',
            'ticketsPayes', 'ticketsValides', 'ticketsBloques',
            'recetteTickets', 'recetteManuelles', 'totalRecettes',
            'totalDepenses', 'solde', 'depensesMois', 'recettesMois',
            'chartLabels', 'chartRecettes', 'chartDepenses',
            'doughnutLabels', 'doughnutData',
            'garesGeo'
        ));
    }
}
