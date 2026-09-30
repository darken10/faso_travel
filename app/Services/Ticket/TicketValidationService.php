<?php

namespace App\Services\Ticket;

use App\Helper\TicketValidation;
use App\Models\Ticket\Ticket;

/**
 * Façade d'instance au-dessus du helper statique App\Helper\TicketValidation.
 *
 * Les contrôleurs API (Admin\TicketController, Admin\ticket\TicketApiController)
 * injectent cette classe dans leur constructeur. Elle expose les noms de méthodes
 * attendus côté appelant, qui diffèrent de ceux du helper :
 *
 *   validate()                 → TicketValidation::valider()
 *   block()                    → TicketValidation::bloque()
 *   pause()                    → TicketValidation::pause()
 *   searchByNumberAndCodeSMS() → TicketValidation::searchTicketByNumberAndCodeSMS()
 *
 * Aucune logique métier ici : elle reste dans le helper, qui est couvert par
 * tests/Feature/Ticket/TicketValidationTest.php.
 */
class TicketValidationService
{
    /**
     * Valide un ticket (embarquement).
     *
     * Attention : pour un ticket AllerRetour, le helper consomme le trajet aller
     * en basculant le ticket en Pause + type RetourSimple. Un second appel
     * consomme le retour. L'appelant doit donc garantir qu'une même opération
     * n'est jouée qu'une fois.
     */
    public function validate(Ticket $ticket): bool
    {
        return TicketValidation::valider($ticket);
    }

    public function pause(Ticket $ticket): bool
    {
        return TicketValidation::pause($ticket);
    }

    public function block(Ticket $ticket): bool
    {
        return TicketValidation::bloque($ticket);
    }

    public function searchByNumberAndCodeSMS(string $numero, string $codeSMS): ?Ticket
    {
        return TicketValidation::searchTicketByNumberAndCodeSMS($numero, $codeSMS);
    }
}
