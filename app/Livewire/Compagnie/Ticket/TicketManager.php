<?php

namespace App\Livewire\Compagnie\Ticket;

use App\Enums\StatutTicket;
use App\Exports\TicketsExport;
use App\Helper\TicketValidation;
use App\Models\Ticket\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.compagnie-panel')]
class TicketManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statutFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $perPage = '15';

    public bool   $showConfirmModal    = false;
    public ?int   $confirmTicketId     = null;
    public string $confirmAction       = '';
    public string $confirmTitle        = '';
    public string $confirmMessage      = '';
    public string $confirmButtonLabel  = '';
    public string $confirmButtonClass  = '';

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatutFilter(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function openConfirm(int $id, string $action): void
    {
        $this->confirmTicketId = $id;
        $this->confirmAction   = $action;

        match ($action) {
            'valider' => [
                $this->confirmTitle       = 'Valider ce ticket',
                $this->confirmMessage     = 'Êtes-vous sûr de vouloir valider ce ticket ? Cette action marquera le ticket comme validé.',
                $this->confirmButtonLabel = 'Valider',
                $this->confirmButtonClass = 'bg-green-600 hover:bg-green-700 text-white',
            ],
            'bloquer' => [
                $this->confirmTitle       = 'Bloquer ce ticket',
                $this->confirmMessage     = 'Êtes-vous sûr de vouloir bloquer ce ticket ? Le client ne pourra plus l\'utiliser.',
                $this->confirmButtonLabel = 'Bloquer',
                $this->confirmButtonClass = 'bg-red-600 hover:bg-red-700 text-white',
            ],
            'activer' => [
                $this->confirmTitle       = 'Réactiver ce ticket',
                $this->confirmMessage     = 'Êtes-vous sûr de vouloir réactiver ce ticket ?',
                $this->confirmButtonLabel = 'Réactiver',
                $this->confirmButtonClass = 'bg-blue-600 hover:bg-blue-700 text-white',
            ],
            default => null,
        };

        $this->showConfirmModal = true;
    }

    public function executeConfirm(): void
    {
        if (! $this->confirmTicketId || ! $this->confirmAction) {
            return;
        }

        match ($this->confirmAction) {
            'valider' => $this->valider($this->confirmTicketId),
            'bloquer' => $this->bloquer($this->confirmTicketId),
            'activer' => $this->activer($this->confirmTicketId),
            default   => null,
        };

        $this->showConfirmModal = false;
        $this->confirmTicketId  = null;
        $this->confirmAction    = '';
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statutFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function export()
    {
        return Excel::download(
            new TicketsExport($this->baseQuery()->latest()),
            'tickets-' . now()->format('Y-m-d') . '.xlsx',
        );
    }

    public function valider(int $id): void
    {
        $ticket = Ticket::findOrFail($id);

        try {
            TicketValidation::valider($ticket);
            $this->dispatch('toast', type: 'success', message: 'Ticket validé avec succès.');
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: 'Erreur lors de la validation : ' . $e->getMessage());
        }
    }

    public function bloquer(int $id): void
    {
        $ticket = Ticket::findOrFail($id);

        try {
            TicketValidation::bloque($ticket);
            $this->dispatch('toast', type: 'success', message: 'Ticket bloqué.');
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: 'Erreur : ' . $e->getMessage());
        }
    }

    public function activer(int $id): void
    {
        $ticket = Ticket::findOrFail($id);

        try {
            TicketValidation::active($ticket);
            $this->dispatch('toast', type: 'success', message: 'Ticket réactivé.');
        } catch (\Throwable $e) {
            $this->dispatch('toast', type: 'error', message: 'Erreur : ' . $e->getMessage());
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
                                ->orWhere('numero', 'like', '%' . $this->search . '%')
                          )
                          ->orWhereHas('autre_personne', fn ($ap) =>
                              $ap->where('first_name', 'like', '%' . $this->search . '%')
                                 ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                 ->orWhere('numero', 'like', '%' . $this->search . '%')
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
