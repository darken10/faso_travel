<?php

namespace App\Livewire\Compagnie\Ticket;

use App\Enums\StatutTicket;
use App\Helper\TicketValidation;
use App\Models\Ticket\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.compagnie-panel')]
class TicketManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statutFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $perPage = '15';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatutFilter(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statutFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function valider(int $id): void
    {
        $ticket = Ticket::findOrFail($id);

        try {
            TicketValidation::valider($ticket);
            session()->flash('success', 'Ticket validé avec succès.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Erreur lors de la validation : ' . $e->getMessage());
        }
    }

    public function bloquer(int $id): void
    {
        $ticket = Ticket::findOrFail($id);

        try {
            TicketValidation::bloque($ticket);
            session()->flash('success', 'Ticket bloqué.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function activer(int $id): void
    {
        $ticket = Ticket::findOrFail($id);

        try {
            TicketValidation::active($ticket);
            session()->flash('success', 'Ticket réactivé.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    private function baseQuery()
    {
        $compagnieId = Auth::user()->compagnie_id;

        return Ticket::withoutGlobalScopes()
            ->with(['user', 'autre_personne', 'voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver', 'payements'])
            ->whereHas('voyageInstance', fn ($q) =>
                $q->whereHas('voyage', fn ($q2) => $q2->where('compagnie_id', $compagnieId))
            )
            ->when($this->search, fn ($q) =>
                $q->where(fn ($inner) =>
                    $inner->where('numero_ticket', 'like', '%' . $this->search . '%')
                          ->orWhere('code_sms', 'like', '%' . $this->search . '%')
                          ->orWhereHas('user', fn ($u) =>
                              $u->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                ->orWhere('phone', 'like', '%' . $this->search . '%')
                          )
                          ->orWhereHas('autre_personne', fn ($ap) =>
                              $ap->where('first_name', 'like', '%' . $this->search . '%')
                                 ->orWhere('last_name', 'like', '%' . $this->search . '%')
                          )
                )
            )
            ->when($this->statutFilter, fn ($q) => $q->where('tickets.statut', $this->statutFilter))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('date', '<=', $this->dateTo));
    }

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;

        $tickets = $this->baseQuery()
            ->latest()
            ->paginate((int) $this->perPage);

        // Statistiques rapides (sans filtres de recherche pour être représentatives)
        $statsBase = Ticket::withoutGlobalScopes()
            ->whereHas('voyageInstance', fn ($q) =>
                $q->whereHas('voyage', fn ($q2) => $q2->where('compagnie_id', $compagnieId))
            );

        $stats = [
            'total'   => (clone $statsBase)->count(),
            'payes'   => (clone $statsBase)->where('tickets.statut', StatutTicket::Payer)->count(),
            'valides' => (clone $statsBase)->where('tickets.statut', StatutTicket::Valider)->count(),
            'bloques' => (clone $statsBase)->where('tickets.statut', StatutTicket::Bloquer)->count(),
            'recette' => (clone $statsBase)->where('tickets.statut', StatutTicket::Valider)
                             ->join('payements', 'tickets.id', '=', 'payements.ticket_id')
                             ->sum('payements.montant'),
        ];

        $statuts = StatutTicket::cases();
        $hasFilters = $this->search || $this->statutFilter || $this->dateFrom || $this->dateTo;

        return view('livewire.compagnie.ticket.ticket-manager', compact('tickets', 'statuts', 'stats', 'hasFilters'));
    }
}
