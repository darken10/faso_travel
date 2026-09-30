<?php

namespace App\Enums;

/**
 * Codes d'erreur exploitables par le client.
 *
 * Les messages du contrôleur sont en prose française et destinés à l'affichage ;
 * c'est sur ces codes que l'application agent doit brancher sa logique (réessayer,
 * abandonner, ouvrir un conflit).
 */
enum SyncErrorCode: string
{
    /** Le ticket a déjà été consommé — typiquement par un autre agent. Conflit. */
    case AlreadyValidated = 'ALREADY_VALIDATED';

    /** Statut incompatible avec l'opération (annulé, remboursé, suspendu...). */
    case InvalidStatus = 'INVALID_STATUS';

    /** Ticket inexistant ou hors du périmètre de la compagnie de l'agent. */
    case NotFound = 'NOT_FOUND';

    /** Le ticket n'appartient pas au voyage annoncé par l'agent. */
    case WrongVoyage = 'WRONG_VOYAGE';

    /** Échec inattendu côté serveur — l'opération peut être réessayée. */
    case ServerError = 'SERVER_ERROR';

    /** Libellé affiché à l'administration et à la finance. */
    public function label(): string
    {
        return match ($this) {
            self::AlreadyValidated => 'Déjà validé par un autre agent',
            self::InvalidStatus    => 'Ticket annulé ou non valide au moment de la synchronisation',
            self::NotFound         => 'Ticket introuvable (QR non reconnu)',
            self::WrongVoyage      => 'Ticket d\'un autre voyage',
            self::ServerError      => 'Erreur serveur',
        };
    }

}
