<?php

namespace App\Policies;

use App\Enums\StatutTicket;
use App\Enums\UserRole;
use App\Models\Ticket\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id
            || $this->isCompagnieAgent($user, $ticket)
            || $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id
            && in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Actif]);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $this->isAdmin($user);
    }

    public function pause(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id
            && $ticket->statut === StatutTicket::Actif;
    }

    public function transfer(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id
            && in_array($ticket->statut, [StatutTicket::Payer, StatutTicket::Actif]);
    }

    public function validate(User $user, Ticket $ticket): bool
    {
        return $this->isCompagnieAgent($user, $ticket) || $this->isAdmin($user);
    }

    public function block(User $user, Ticket $ticket): bool
    {
        return $this->isCompagnieAgent($user, $ticket) || $this->isAdmin($user);
    }

    public function regenerate(User $user, Ticket $ticket): bool
    {
        return $user->id === $ticket->user_id;
    }

    private function isAdmin(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Root]);
    }

    private function isCompagnieAgent(User $user, Ticket $ticket): bool
    {
        if (!$user->compagnie_id) {
            return false;
        }

        $ticketCompagnieId = $ticket->voyageInstance?->voyage?->compagnie_id;

        return $user->compagnie_id === $ticketCompagnieId;
    }
}
