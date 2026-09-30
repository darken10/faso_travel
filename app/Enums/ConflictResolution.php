<?php

namespace App\Enums;

/**
 * Issue donnée par l'administration ou la finance à une validation refusée.
 *
 * Le passager est déjà monté dans le bus quand le refus arrive : il n'y a rien à
 * "annuler", seulement à constater et, au besoin, à régulariser.
 */
enum ConflictResolution: string
{
    /** Incident sans conséquence (doublon de scan, erreur d'agent...). */
    case Dismissed = 'dismissed';

    /** Situation du passager régularisée (paiement complété, ticket réémis...). */
    case Regularized = 'regularized';

    /** Fraude avérée (ticket dupliqué, faux QR...). */
    case Fraud = 'fraud';

    public function label(): string
    {
        return match ($this) {
            self::Dismissed   => 'Classé sans suite',
            self::Regularized => 'Régularisé',
            self::Fraud       => 'Fraude avérée',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
