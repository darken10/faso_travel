<?php

namespace App\Livewire\Compagnie\Finance;

use App\Models\Finance\PromoCode;
use App\Models\Ticket\Ticket;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class PromoShow extends Component
{
    use WithPagination;

    public int $promoId;

    public function mount(int $promoId): void
    {
        $this->promoId = $promoId;
    }

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;

        $promo = PromoCode::where('compagnie_id', $compagnieId)->findOrFail($this->promoId);

        $base = Ticket::withoutGlobalScopes()->where('promo_code_id', $promo->id);

        $totalUtilisations = (clone $base)->count();
        $totalReduction    = (int) (clone $base)->sum('reduction');

        $tickets = (clone $base)
            ->with([
                'user', 'autre_personne',
                'voyageInstance.voyage.trajet.depart',
                'voyageInstance.voyage.trajet.arriver',
                'payements',
            ])
            ->latest()
            ->paginate(20);

        return view('livewire.compagnie.finance.promo-show', compact(
            'promo', 'tickets', 'totalUtilisations', 'totalReduction'
        ));
    }
}
