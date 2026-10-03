<?php

namespace App\Policies;

use App\Enums\StatutTicket;
use App\Models\Ticket\Ticket;
use App\Models\User;

/**
 * Autorisations des billets.
 *
 * Deux populations se croisent ici : le voyageur, qui n'agit que sur ses propres billets,
 * et le personnel de la compagnie, qui agit sur ceux de ses départs. Les méthodes de la
 * première catégorie testent la propriété et l'état du billet ; celles de la seconde
 * passent par une permission.
 */
class TicketPolicy
{
    /** Le filtrage des lignes relève des scopes de requête, pas de l'autorisation. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $this->luiAppartient($user, $ticket)
            || $user->hasPermission('guichet.ticket.view.all', $ticket)
            || $user->hasPermission('guichet.ticket.view.gare', $ticket);
    }

    /**
     * Un compte vérifié peut acheter.
     *
     * `isVerified()` et non `hasVerifiedEmail()` : depuis l'ajout de `phone_verified_at`,
     * un compte créé par OTP téléphone est légitime et n'a pas d'e-mail vérifié.
     */
    public function create(User $user): bool
    {
        return $user->isVerified();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->luiAppartient($user, $ticket)
            && in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Valider], true);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('guichet.ticket.cancel', $ticket);
    }

    public function pause(User $user, Ticket $ticket): bool
    {
        return $this->luiAppartient($user, $ticket)
            && $ticket->statut === StatutTicket::Valider;
    }

    public function transfer(User $user, Ticket $ticket): bool
    {
        return $this->luiAppartient($user, $ticket)
            && in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Valider], true);
    }

    /**
     * Valider un embarquement.
     *
     * Exigeait seulement un rattachement à la compagnie : un comptable pouvait donc
     * valider un billet au quai.
     */
    public function validate(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('embarquement.ticket.validate', $ticket);
    }

    public function block(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('embarquement.ticket.block', $ticket);
    }

    public function unblock(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('guichet.ticket.unblock', $ticket);
    }

    public function refund(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('finance.remboursement.approve', $ticket);
    }

    public function regenerate(User $user, Ticket $ticket): bool
    {
        return $this->luiAppartient($user, $ticket);
    }

    /** Le billet appartient-il au compte, directement ou comme acheteur pour un tiers ? */
    private function luiAppartient(User $user, Ticket $ticket): bool
    {
        return (int) $user->id === (int) $ticket->user_id;
    }
}
