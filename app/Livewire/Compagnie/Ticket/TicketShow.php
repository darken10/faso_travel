<?php

namespace App\Livewire\Compagnie\Ticket;

use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Ticket\QrCodeService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.compagnie-panel')]
class TicketShow extends Component
{
    public int $ticketId;

    public function mount(int $ticketId): void
    {
        $this->ticketId = $ticketId;
    }

    public function render()
    {
        $compagnieId = auth()->user()->compagnie_id;

        $ticket = Ticket::withoutGlobalScopes()
            ->with([
                'user',
                'autre_personne',
                'payements',
                'voyageInstance.voyage.trajet.depart',
                'voyageInstance.voyage.trajet.arriver',
                'voyageInstance.voyage.compagnie',
                'voyageInstance.care',
                'voyageInstance.chauffer',
            ])
            ->whereHas('voyageInstance.voyage', fn ($q) => $q->where('compagnie_id', $compagnieId))
            ->findOrFail($this->ticketId);

        $qr = null;
        if ($ticket->code_qr) {
            try {
                $qr = 'data:image/png;base64,' . base64_encode(app(QrCodeService::class)->pngContent($ticket->code_qr));
            } catch (\Throwable) {
                $qr = null;
            }
        }

        // Pas de restriction par compagnie ici : le destinataire d'un transfert
        // est un voyageur, pas un membre de la compagnie. L'identifiant provient
        // du billet déjà vérifié ci-dessus, jamais d'une saisie utilisateur.
        $transferRecipient = $ticket->transferer_a_user_id
            ? User::find($ticket->transferer_a_user_id)
            : null;

        return view('livewire.compagnie.ticket.ticket-show', [
            'ticket'            => $ticket,
            'qr'                => $qr,
            'transferRecipient' => $transferRecipient,
        ]);
    }
}
