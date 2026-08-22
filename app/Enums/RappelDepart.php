<?php

namespace App\Enums;

use App\Models\Voyage\VoyageInstance;

/**
 * Paliers de rappel envoyés avant un départ.
 *
 * Chaque compagnie active ou désactive indépendamment chacun d'eux, et règle
 * l'avance de tir, depuis le panel (groupe « Notifications »).
 *
 * Aucune colonne ne marque les envois : la trace est celle que le canal
 * `database` écrit déjà dans la table `notifications`. Un passager reçoit donc
 * au plus un message par palier, sans schéma supplémentaire.
 */
enum RappelDepart: string
{
    /** La veille — inutile lorsque le départ a lieu le jour même de l'achat. */
    case Veille = 'veille';

    /** Peu avant le départ, le temps de rejoindre la gare. */
    case AvantDepart = 'avant_depart';

    /** À l'heure de l'embarquement. */
    case Embarquement = 'embarquement';

    public function label(): string
    {
        return match ($this) {
            self::Veille       => 'Rappel de la veille',
            self::AvantDepart  => 'Départ imminent',
            self::Embarquement => 'Embarquement en cours',
        };
    }

    /** Paramètre compagnie activant ce palier. */
    public function cleActivation(): CompagnieSettingKey
    {
        return match ($this) {
            self::Veille       => CompagnieSettingKey::RAPPEL_VEILLE_ACTIF,
            self::AvantDepart  => CompagnieSettingKey::RAPPEL_AVANT_DEPART_ACTIF,
            self::Embarquement => CompagnieSettingKey::RAPPEL_EMBARQUEMENT_ACTIF,
        };
    }

    /**
     * Paramètre compagnie réglant l'avance de tir, ou null pour un palier qui
     * part exactement à l'heure du départ.
     */
    public function cleDelai(): ?CompagnieSettingKey
    {
        return match ($this) {
            self::Veille       => CompagnieSettingKey::RAPPEL_VEILLE_HEURES,
            self::AvantDepart  => CompagnieSettingKey::RAPPEL_AVANT_DEPART_MINUTES,
            self::Embarquement => null,
        };
    }

    /** Le palier de la veille n'a pas de sens pour un départ du jour même. */
    public function concerne(VoyageInstance $instance): bool
    {
        if ($this !== self::Veille) {
            return true;
        }

        return ! $instance->created_at?->isSameDay($instance->date);
    }

    public function couleur(): string
    {
        return match ($this) {
            self::Veille       => '#1D4ED8',
            self::AvantDepart  => '#B45309',
            self::Embarquement => '#059669',
        };
    }

    /** @return array<int, self> */
    public static function ordonnes(): array
    {
        return [self::Veille, self::AvantDepart, self::Embarquement];
    }
}
