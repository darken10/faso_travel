<?php

namespace App\Services\Ticket;

use App\Enums\SyncErrorCode;
use App\Enums\SyncResult;
use App\Models\Ticket\TicketValidation;

/**
 * Issue d'un appel à TicketSyncService::apply().
 *
 * Distingue deux choses que le journal seul ne permet pas de séparer : ce qui
 * s'est passé (le journal, écrit une fois pour toutes) et ce que cet appel-ci a
 * fait (appliqué, ou simplement reconnu un rejeu).
 */
final readonly class SyncOutcome
{
    public function __construct(
        public TicketValidation $journal,
        public bool $replayed,
    ) {}

    public static function applied(TicketValidation $journal): self
    {
        return new self($journal, replayed: false);
    }

    public static function replayed(TicketValidation $journal): self
    {
        return new self($journal, replayed: true);
    }

    /**
     * Statut renvoyé au client.
     *
     * Le rejeu d'une opération réussie devient already_applied : le téléphone
     * peut retirer l'opération de sa file. Le rejeu d'un refus reste un refus,
     * avec son code d'erreur d'origine — le conflit doit toujours remonter.
     */
    public function status(): SyncResult
    {
        if ($this->replayed && $this->journal->result->isSuccess()) {
            return SyncResult::AlreadyApplied;
        }

        return $this->journal->result;
    }

    public function errorCode(): ?SyncErrorCode
    {
        return $this->journal->error_code;
    }

    public function isSuccess(): bool
    {
        return $this->status()->isSuccess();
    }
}
