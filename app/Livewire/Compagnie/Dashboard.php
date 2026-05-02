<?php

namespace App\Livewire\Compagnie;

use App\Enums\StatutPayement;
use App\Enums\StatutTicket;
use App\Helper\QueryHelpers;
use App\Models\Compagnie\Gare;
use App\Models\Finance\Depense;
use App\Models\Finance\Recette;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.compagnie-panel')]
class Dashboard extends Component
{
    public function render()
    {
        $compagnieId = Auth::user()?->compagnie_id;

        // ── Stat cards ──
        $totalVoyages = QueryHelpers::AllVoyagesOfMyCompagnie()->count();
        $totalGares   = Gare::where('compagnie_id', $compagnieId)->count();
        $totalPosts   = QueryHelpers::AllPostsOfMyCompagnie()->count();
        $totalUsers   = QueryHelpers::AllUsersOfMyCompagnie()->count();

        $ticketsPayes   = QueryHelpers::AllTicketOfMyCompagnie()->count();
        $ticketsValides = QueryHelpers::AllTicketOfMyCompagnie(StatutTicket::Valider)->count();
        $ticketsBloques = QueryHelpers::AllTicketOfMyCompagnie(StatutTicket::Bloquer)->count();

        // ── Finance ──
        $recetteTickets    = QueryHelpers::AllPaymentsOfMyCompagnie(StatutPayement::Complete, StatutTicket::Valider)->sum('montant');
        $recetteManuelles  = Recette::where('compagnie_id', $compagnieId)->sum('montant');
        $totalRecettes     = $recetteTickets + $recetteManuelles;
        $totalDepenses     = Depense::where('compagnie_id', $compagnieId)->sum('montant');
        $solde             = $totalRecettes - $totalDepenses;

        $depensesMois      = Depense::where('compagnie_id', $compagnieId)
            ->whereMonth('date_depense', now()->month)
            ->whereYear('date_depense', now()->year)
            ->sum('montant');

        $recettesMois = QueryHelpers::AllPaymentsOfMyCompagnie(StatutPayement::Complete, StatutTicket::Valider)
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

            $ticketRev = QueryHelpers::AllPaymentsOfMyCompagnie(StatutPayement::Complete, StatutTicket::Valider)
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
