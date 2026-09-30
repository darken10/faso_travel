<?php

namespace App\Livewire\Compagnie\Ticket;

use App\Enums\ConflictResolution;
use App\Enums\SyncErrorCode;
use App\Models\Ticket\TicketValidation;
use App\Traits\ScopedToCompagnie;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Validations refusées à la synchronisation d'un agent.
 *
 * Un agent embarque un passager hors connexion ; à son retour en ligne, le serveur
 * refuse la validation (ticket déjà utilisé ailleurs, annulé entre-temps...). Le
 * passager est déjà dans le bus : l'administration et la finance instruisent le cas.
 */
#[Layout('layouts.compagnie-panel')]
class ConflitManager extends Component
{
    use ScopedToCompagnie;
    use WithPagination;

    public string $etat = 'ouverts';   // ouverts | traites | tous
    public string $motif = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public bool $showResolveModal = false;
    public ?int $resolvingId = null;
    public string $resolution = '';
    public string $note = '';

    public function mount(): void
    {
        Gate::authorize('manage-boarding-conflicts');
    }

    public function updatedEtat(): void { $this->resetPage(); }
    public function updatedMotif(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['etat', 'motif', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function openResolve(int $id): void
    {
        Gate::authorize('manage-boarding-conflicts');

        // Identifiant reçu du navigateur : jamais une donnée de confiance.
        $conflit = $this->baseQuery()->findOrFail($id);

        $this->resolvingId = $conflit->id;
        $this->resolution = $conflit->resolution?->value ?? '';
        $this->note = (string) $conflit->resolution_note;
        $this->showResolveModal = true;
    }

    public function closeResolve(): void
    {
        $this->reset(['showResolveModal', 'resolvingId', 'resolution', 'note']);
        $this->resetValidation();
    }

    public function resolve(): void
    {
        Gate::authorize('manage-boarding-conflicts');

        $this->validate([
            'resolution' => ['required', Rule::in(ConflictResolution::values())],
            'note'       => ['nullable', 'string', 'max:1000'],
        ], [
            'resolution.required' => 'Choisissez une issue.',
            'resolution.in'       => 'Issue invalide.',
        ]);

        $conflit = $this->baseQuery()->findOrFail($this->resolvingId);

        $conflit->update([
            'resolution'      => $this->resolution,
            'resolution_note' => trim($this->note) ?: null,
            'resolved_by_id'  => Auth::id(),
            'resolved_at'     => now(),
        ]);

        $this->closeResolve();
        $this->dispatch('toast', type: 'success', message: 'Conflit traité.');
    }

    /** Conflits de la compagnie, refus du serveur uniquement. */
    private function baseQuery()
    {
        return TicketValidation::query()
            ->conflicts()
            ->ofAgentsOfCompagnie($this->compagnieId());
    }

    private function filteredQuery()
    {
        return $this->baseQuery()
            ->when($this->etat === 'ouverts', fn ($q) => $q->whereNull('resolved_at'))
            ->when($this->etat === 'traites', fn ($q) => $q->whereNotNull('resolved_at'))
            ->when($this->motif !== '', fn ($q) => $q->where('error_code', $this->motif))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('client_created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('client_created_at', '<=', $this->dateTo));
    }

    public function render(): View
    {
        $conflits = $this->filteredQuery()
            ->with([
                'agent:id,name',
                'resolvedBy:id,name',
                'ticket.user:id,name',
                'ticket.autre_personne:id,name',
                'ticket.voyageInstance.voyage.trajet.depart',
                'ticket.voyageInstance.voyage.trajet.arriver',
            ])
            ->latest('client_created_at')
            ->paginate(15);

        return view('livewire.compagnie.ticket.conflit-manager', [
            'conflits' => $conflits,
            'ouverts'  => $this->baseQuery()->whereNull('resolved_at')->count(),
            'traites'  => $this->baseQuery()->whereNotNull('resolved_at')->count(),
            // ServerError n'est jamais journalisé (il est réessayé), inutile de le proposer.
            'motifs'   => array_values(array_filter(SyncErrorCode::cases(), fn ($c) => $c !== SyncErrorCode::ServerError)),
            'issues'   => ConflictResolution::cases(),
        ]);
    }
}
