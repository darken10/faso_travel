<?php

namespace App\Enums;

/**
 * Regroupement fonctionnel des paramètres, utilisé pour l'affichage en onglets.
 */
enum CompagnieSettingGroup: string
{
    case General      = 'general';
    case Reservation  = 'reservation';
    case Annulation   = 'annulation';
    case Paiement     = 'paiement';
    case Ticket       = 'ticket';
    case Notification = 'notification';
    case Fidelite     = 'fidelite';
    case Apparence    = 'apparence';
    case Avance       = 'avance';

    public function label(): string
    {
        return match ($this) {
            self::General      => 'Général',
            self::Reservation  => 'Réservation',
            self::Annulation   => 'Annulation & modification',
            self::Paiement     => 'Paiement',
            self::Ticket       => 'Tickets',
            self::Notification => 'Notifications',
            self::Fidelite     => 'Fidélité & promotions',
            self::Apparence    => 'Apparence',
            self::Avance       => 'Avancé',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::General      => 'Identité, devise et coordonnées de la compagnie.',
            self::Reservation  => 'Règles d\'ouverture et de clôture des ventes.',
            self::Annulation   => 'Conditions d\'annulation, de remboursement et de modification.',
            self::Paiement     => 'Canaux de paiement acceptés et frais appliqués.',
            self::Ticket       => 'Format, validation et mentions portées sur les tickets.',
            self::Notification => 'Canaux de communication avec les voyageurs.',
            self::Fidelite     => 'Programme de fidélité et codes promotionnels.',
            self::Apparence    => 'Couleurs et éléments visuels de la compagnie.',
            self::Avance       => 'Paramètres plateforme réservés aux administrateurs.',
        };
    }

    /** Tracé SVG de l'icône (heroicons outline), cohérent avec les sidebars. */
    public function icon(): string
    {
        return match ($this) {
            self::General      => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            self::Reservation  => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            self::Annulation   => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            self::Paiement     => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
            self::Ticket       => 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
            self::Notification => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
            self::Fidelite     => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
            self::Apparence    => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
            self::Avance       => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
        };
    }

    /** Groupe réservé aux administrateurs de la plateforme. */
    public function isAdminOnly(): bool
    {
        return $this === self::Avance;
    }

    /** @return array<int, self> */
    public static function forCompagnie(): array
    {
        return array_values(array_filter(self::cases(), fn (self $g) => ! $g->isAdminOnly()));
    }
}
