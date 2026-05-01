<?php

namespace App\Livewire\Compagnie\Ticket;

use App\Enums\StatutTicket;
use App\Helper\TicketValidation;
use App\Models\Ticket\Ticket;
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

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatutFilter(): void { $this->resetPage(); }

    public function valider(int $id): void
    {
        $ticket = Ticket::findOrFail($id);
        $this->authorize('update', $ticket);

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
        $this->authorize('update', $ticket);

        try {
            TicketValidation::bloque($ticket);
            session()->flash('success', 'Ticket bloqué.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function pause(int $id): void
    {
        $ticket = Ticket::findOrFail($id);
        $this->authorize('update', $ticket);

        try {
            TicketValidation::pause($ticket);
            session()->flash('success', 'Ticket mis en pause.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function activer(int $id): void
    {
        $ticket = Ticket::findOrFail($id);
        $this->authorize('update', $ticket);

        try {
            TicketValidation::active($ticket);
            session()->flash('success', 'Ticket réactivé.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function render()
    {
        $compagnieId = Auth::user()->compagnie_id;

        $tickets = Ticket::withoutGlobalScopes()
            ->whereHas('voyageInstance', fn ($q) =>
                $q->whereHas('voyage', fn ($q2) => $q2->where('compagnie_id', $compagnieId))
            )
            ->when($this->search, fn ($q) =>
                $q->where('numero_ticket', 'like', '%' . $this->search . '%')
                  ->orWhereHas('autrePersonne', fn ($q2) =>
                      $q2->where('first_name', 'like', '%' . $this->search . '%')
                         ->orWhere('last_name', 'like', '%' . $this->search . '%')
                  )
            )
            ->when($this->statutFilter, fn ($q) => $q->where('statut', $this->statutFilter))
            ->latest()
            ->paginate(15);

        $statuts = StatutTicket::cases();

        return view('livewire.compagnie.ticket.ticket-manager', compact('tickets', 'statuts'));
    }
}
