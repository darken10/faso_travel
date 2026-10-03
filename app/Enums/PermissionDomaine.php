<?php

namespace App\Enums;

/**
 * Domaines fonctionnels du catalogue de permissions.
 *
 * Le domaine est le premier segment du nom d'une permission
 * (`<domaine>.<ressource>.<action>`) et sert à regrouper les permissions dans les écrans
 * d'administration des rôles.
 */
enum PermissionDomaine: string
{
    case Platform = 'platform';
    case Compagnie = 'compagnie';
    case Reseau = 'reseau';
    case Voyage = 'voyage';
    case Guichet = 'guichet';
    case Embarquement = 'embarquement';
    case Caisse = 'caisse';
    case Finance = 'finance';
    case Contenu = 'contenu';
    case Crm = 'crm';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Platform => 'Plateforme',
            self::Compagnie => 'Compagnie',
            self::Reseau => 'Réseau et ressources',
            self::Voyage => 'Offre de voyage',
            self::Guichet => 'Guichet et billetterie',
            self::Embarquement => 'Embarquement',
            self::Caisse => 'Caisse',
            self::Finance => 'Finance et comptabilité',
            self::Contenu => 'Contenu et communication',
            self::Crm => 'Relation client',
            self::Client => 'Espace voyageur',
        };
    }

    /**
     * Portée naturelle du domaine, utilisée comme valeur par défaut d'une permission.
     */
    public function scope(): string
    {
        return match ($this) {
            self::Platform => 'platform',
            self::Client => 'client',
            default => 'compagnie',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->toArray();
    }
}
