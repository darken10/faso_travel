<?php

namespace App\Enums;

/**
 * Issue du traitement d'une opération de synchronisation.
 *
 * Le client doit distinguer ces trois cas : Applied et AlreadyApplied sont tous
 * deux des succès du point de vue de la file (l'opération peut être retirée),
 * alors que Rejected demande un arbitrage — c'est le cas de conflit remonté à
 * l'agent puis à l'administration.
 */
enum SyncResult: string
{
    /** L'opération a été exécutée par cet appel. */
    case Applied = 'applied';

    /** Déjà traitée lors d'un envoi précédent — rejeu idempotent, aucun effet. */
    case AlreadyApplied = 'already_applied';

    /** Refusée par le serveur : le client doit remonter un conflit. */
    case Rejected = 'rejected';

    public function isSuccess(): bool
    {
        return $this !== self::Rejected;
    }
}
