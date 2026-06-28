<?php

namespace App\Livewire\Compagnie\Finance;

use App\Enums\StatutPayement;
use App\Enums\StatutTicket;
use App\Helper\QueryHelpers;
use App\Models\Finance\CategorieDepense;
use App\Models\Finance\Depense;
use App\Models\Finance\Recette;
use App\Models\Ticket\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.compagnie-panel')]
class BilanFinancier extends Component
{
    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;

        // --- 6-month chart data ---
        $chartLabels = [];
        $chartRecettes = [];
        $chartDepenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            $chartLabels[] = $date->translatedFormat('M Y');

            $ticketRevenue = QueryHelpers::AllPaymentsOfMyCompagnie(StatutPayement::Complete, [StatutTicket::Payer, StatutTicket::Valider])
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->sum('montant');

            $manualRevenue = Recette::where('compagnie_id', $compagnieId)
                ->whereMonth('date_recette', $month)
                ->whereYear('date_recette', $year)
                ->sum('montant');

            $chartRecettes[] = $ticketRevenue + $manualRevenue;

            $chartDepenses[] = Depense::where('compagnie_id', $compagnieId)
                ->whereMonth('date_depense', $month)
                ->whereYear('date_depense', $year)
                ->sum('montant');
        }

        // --- Doughnut data ---
        $doughnutData = Depense::withoutGlobalScopes()
            ->where('depenses.compagnie_id', $compagnieId)
            ->join('categorie_depenses', 'depenses.categorie_depense_id', '=', 'categorie_depenses.id')
            ->select('categorie_depenses.nom', DB::raw('SUM(depenses.montant) as total'))
            ->groupBy('categorie_depenses.id', 'categorie_depenses.nom')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // --- Totals ---
        $totalTicketRecettes = QueryHelpers::AllPaymentsOfMyCompagnie(StatutPayement::Complete, [StatutTicket::Payer, StatutTicket::Valider])
            ->sum('montant');
        $totalManualRecettes = Recette::where('compagnie_id', $compagnieId)->sum('montant');
        $totalRecettes = $totalTicketRecettes + $totalManualRecettes;
        $totalDepenses = Depense::where('compagnie_id', $compagnieId)->sum('montant');
        $solde = $totalRecettes - $totalDepenses;

        // Réductions accordées via codes promo (informatif : la recette ci-dessus est
        // déjà nette, on expose la remise et la recette brute correspondante).
        $totalReductionsPromo = (int) Ticket::withoutGlobalScopes()
            ->whereHas('voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->whereIn('statut', [StatutTicket::Payer, StatutTicket::Valider])
            ->sum('reduction');

        // This month
        $recettesMois = QueryHelpers::AllPaymentsOfMyCompagnie(StatutPayement::Complete, [StatutTicket::Payer, StatutTicket::Valider])
                ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('montant')
            + Recette::where('compagnie_id', $compagnieId)
                ->whereMonth('date_recette', now()->month)->whereYear('date_recette', now()->year)->sum('montant');
        $depensesMois = Depense::where('compagnie_id', $compagnieId)
            ->whereMonth('date_depense', now()->month)->whereYear('date_depense', now()->year)->sum('montant');

        return view('livewire.compagnie.finance.bilan-financier', compact(
            'chartLabels', 'chartRecettes', 'chartDepenses',
            'doughnutData',
            'totalRecettes', 'totalDepenses', 'solde',
            'recettesMois', 'depensesMois',
            'totalTicketRecettes', 'totalManualRecettes',
            'totalReductionsPromo'
        ));
    }
}
