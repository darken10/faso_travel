<?php

namespace App\Features;

use App\DTOs\Compagnie\CompagnieSettings;
use App\Enums\CompagnieSettingKey;
use App\Models\Compagnie\Compagnie;
use Carbon\CarbonInterface;

/**
 * Traduit le paramétrage d'une compagnie en règles métier applicables
 * à la vente, à l'annulation et à la modification des tickets.
 */
class CompagnieRulesService
{
    private readonly CompagnieSettings $parametres;

    public function __construct(protected Compagnie $compagnie)
    {
        $this->parametres = $compagnie->parametres();
    }

    public function parametres(): CompagnieSettings
    {
        return $this->parametres;
    }

    /** La compagnie accepte-t-elle les réservations depuis l'application ? */
    public function reservationEnLigneActive(): bool
    {
        return $this->parametres->reservationEnLigneActive();
    }

    public function paiementEnLigneActif(): bool
    {
        return $this->parametres->paiementEnLigneActif();
    }

    public function paiementEspeceAccepte(): bool
    {
        return $this->parametres->bool(CompagnieSettingKey::PAIEMENT_ESPECE_GUICHET);
    }

    public function delaiAnnulation(): int
    {
        return $this->parametres->delaiAnnulationHeures();
    }

    /** Un départ prévu à cette date est-il encore ouvert à la vente ? */
    public function venteOuvertePour(CarbonInterface $depart): bool
    {
        if (! $this->reservationEnLigneActive()) {
            return false;
        }

        $maintenant = now();

        if ($depart->copy()->subMinutes($this->parametres->clotureVenteMinutes())->isBefore($maintenant)) {
            return false;
        }

        return $depart->copy()->subDays($this->parametres->ouvertureVenteJours())->isBefore($maintenant);
    }

    /** Un ticket dont le départ est prévu à cette date est-il annulable ? */
    public function annulationPossiblePour(CarbonInterface $depart): bool
    {
        return $this->parametres->annulationAutorisee()
            && $depart->copy()->subHours($this->delaiAnnulation())->isAfter(now());
    }

    /** Un ticket dont le départ est prévu à cette date est-il modifiable ? */
    public function modificationPossiblePour(CarbonInterface $depart): bool
    {
        return $this->parametres->bool(CompagnieSettingKey::MODIFICATION_AUTORISEE)
            && $depart->copy()->subHours($this->parametres->int(CompagnieSettingKey::DELAI_MODIFICATION))->isAfter(now());
    }

    /** Montant effectivement remboursé pour une annulation. */
    public function montantRembourse(int $montant): int
    {
        return $this->parametres->montantRembourse($montant);
    }

    /** Frais de service ajoutés au prix du billet. */
    public function fraisService(int $montant): int
    {
        return $this->parametres->fraisServicePour($montant);
    }

    /** Le nombre de places demandé respecte-t-il le plafond par réservation ? */
    public function nombrePlacesAutorise(int $places): bool
    {
        return $places >= 1 && $places <= $this->parametres->maxPlacesParReservation();
    }
}
