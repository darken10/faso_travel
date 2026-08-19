<?php

namespace App\DTOs\Compagnie;

use App\Enums\CompagnieSettingKey;

/**
 * Accès typé et immuable aux paramètres résolus d'une compagnie.
 *
 * Les services métier passent par ce sac plutôt que par des chaînes de
 * caractères : la faute de frappe devient une erreur de compilation.
 */
final class CompagnieSettings
{
    /** @param  array<string, mixed>  $values */
    public function __construct(private readonly array $values) {}

    public function get(CompagnieSettingKey $key): mixed
    {
        return $this->values[$key->value] ?? $key->default();
    }

    public function bool(CompagnieSettingKey $key): bool
    {
        return (bool) $this->get($key);
    }

    public function int(CompagnieSettingKey $key): int
    {
        return (int) $this->get($key);
    }

    public function float(CompagnieSettingKey $key): float
    {
        return (float) $this->get($key);
    }

    public function string(CompagnieSettingKey $key): string
    {
        return (string) $this->get($key);
    }

    /** @return array<int, string> */
    public function list(CompagnieSettingKey $key): array
    {
        return (array) $this->get($key);
    }

    // ── Raccourcis métier ───────────────────────────────────────────────────

    public function devise(): string
    {
        return $this->string(CompagnieSettingKey::DEVISE);
    }

    public function devisePosition(): string
    {
        return $this->string(CompagnieSettingKey::DEVISE_POSITION);
    }

    public function fuseauHoraire(): string
    {
        return $this->string(CompagnieSettingKey::FUSEAU_HORAIRE);
    }

    public function reservationEnLigneActive(): bool
    {
        return $this->bool(CompagnieSettingKey::RESERVATION_EN_LIGNE) && ! $this->modeMaintenance();
    }

    public function paiementEnLigneActif(): bool
    {
        return $this->bool(CompagnieSettingKey::PAIEMENT_EN_LIGNE);
    }

    public function modeMaintenance(): bool
    {
        return $this->bool(CompagnieSettingKey::MODE_MAINTENANCE);
    }

    public function delaiAnnulationHeures(): int
    {
        return $this->int(CompagnieSettingKey::DELAI_ANNULATION);
    }

    public function annulationAutorisee(): bool
    {
        return $this->bool(CompagnieSettingKey::ANNULATION_AUTORISEE);
    }

    public function penaliteAnnulationPourcent(): int
    {
        return $this->int(CompagnieSettingKey::PENALITE_ANNULATION);
    }

    public function clotureVenteMinutes(): int
    {
        return $this->int(CompagnieSettingKey::CLOTURE_VENTE_MINUTES);
    }

    public function ouvertureVenteJours(): int
    {
        return $this->int(CompagnieSettingKey::OUVERTURE_VENTE_JOURS);
    }

    public function maxPlacesParReservation(): int
    {
        return $this->int(CompagnieSettingKey::MAX_PLACES_PAR_RESERVATION);
    }

    /** @return array<int, string> */
    public function moyensPaiementActifs(): array
    {
        return $this->list(CompagnieSettingKey::MOYENS_PAIEMENT_ACTIFS);
    }

    public function accepteMoyenPaiement(string $moyen): bool
    {
        return in_array($moyen, $this->moyensPaiementActifs(), true);
    }

    /** Montant des frais de service pour un billet donné. */
    public function fraisServicePour(int $montant): int
    {
        $frais = $this->int(CompagnieSettingKey::FRAIS_SERVICE);

        if ($frais <= 0) {
            return 0;
        }

        return $this->string(CompagnieSettingKey::FRAIS_SERVICE_TYPE) === 'pourcentage'
            ? (int) round($montant * $frais / 100)
            : $frais;
    }

    /** Montant remboursable après application de la pénalité d'annulation. */
    public function montantRembourse(int $montant): int
    {
        return (int) round($montant * (100 - $this->penaliteAnnulationPourcent()) / 100);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->values;
    }
}
