<?php

namespace App\Enums;

/**
 * Opérations qu'un agent peut réaliser hors connexion et rejouer via batch-sync.
 */
enum SyncActionType: string
{
    case ValidateTicket = 'VALIDATE_TICKET';
    case PauseTicket    = 'PAUSE_TICKET';
    case BlockTicket    = 'BLOCK_TICKET';
    case MarkAbsent     = 'MARK_ABSENT';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
