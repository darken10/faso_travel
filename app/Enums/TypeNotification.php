<?php

namespace App\Enums;

enum TypeNotification: string
{
    case CreationPost = 'Createion Post';
    case PayerTicket = 'Payer Ticket';
    case UpdateTicket = 'Update Ticket';
    case TransactionTicket = 'Transaction Ticket';
    case RecevoirTicket = 'Recevoir Ticket';

    case TICKET_MISE_PAUSE = 'MISE PAUSE';
    case TICKET_ACTIVE = 'ACTIVE';
    case TICKET_CLOSED = 'CLOSED';
    case TICKET_SENDED = 'SENDED';
    case TICKET_RECEIVED = 'RECEIVED';
    case TICKET_VALIDATED = 'VALIDATED';
    case TICKET_REPORTED = 'REPORTED';

    case TICKET_UPDATED = 'UPDATED';
    case TICKET_PAYER = 'PAYER';
    case TICKET_REDELIVERED = 'REDELIVERED';
    case TICKET_REGENERATED = 'REGENERATED';

    case VOYAGE_ANNULE  = 'VOYAGE_ANNULE';
    case VOYAGE_RETARDE = 'VOYAGE_RETARDE';
}
