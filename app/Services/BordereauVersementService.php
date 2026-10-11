<?php

namespace App\Services;

use App\Enums\StatutBordereauVersement;
use App\Enums\StatutCaisse;
use App\Models\Finance\BordereauVersement;
use App\Models\Finance\Caisse;
use App\Models\Finance\Recette;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class BordereauVersementService
{
    public function emettre(Caisse $caisse, User $emetteur, ?string $note = null): BordereauVersement
    {
        if ($caisse->statut !== StatutCaisse::Fermee) {
            throw new DomainException('La caisse doit être clôturée avant l’émission du bordereau.');
        }

        if ((int) $caisse->user_id !== (int) $emetteur->id) {
            throw new DomainException('Seul le titulaire de la caisse peut émettre son bordereau.');
        }

        if ($caisse->montant_fermeture === null) {
            throw new DomainException('Le montant de fermeture doit être renseigné avant l’émission du bordereau.');
        }

        return DB::transaction(function () use ($caisse, $emetteur, $note): BordereauVersement {
            $caisseVerrouillee = Caisse::query()->lockForUpdate()->findOrFail($caisse->id);
            $existant = BordereauVersement::query()
                ->where('caisse_id', $caisseVerrouillee->id)
                ->lockForUpdate()
                ->first();

            if ($existant) {
                return $existant;
            }

            $bordereau = BordereauVersement::create([
                'compagnie_id' => $caisseVerrouillee->compagnie_id,
                'caisse_id' => $caisseVerrouillee->id,
                'montant_attendu' => $caisseVerrouillee->montant_attendu ?? $caisseVerrouillee->calculerMontantAttendu(),
                'montant_declare' => $caisseVerrouillee->montant_fermeture,
                'statut' => StatutBordereauVersement::Emis,
                'emitted_by_id' => $emetteur->id,
                'emitted_at' => now(),
                'note_emission' => $note,
            ]);

            $bordereau->update([
                'numero' => sprintf('BV-%s-%06d', now()->format('Ymd'), $bordereau->id),
            ]);

            return $bordereau->refresh();
        });
    }

    public function prendreEnCharge(BordereauVersement $bordereau, User $finance, int $montantRecu, ?string $note = null): BordereauVersement
    {
        if ($montantRecu < 0) {
            throw new DomainException('Le montant reçu ne peut pas être négatif.');
        }

        return DB::transaction(function () use ($bordereau, $finance, $montantRecu, $note): BordereauVersement {
            $bordereau = BordereauVersement::query()->lockForUpdate()->findOrFail($bordereau->id);
            $this->assertMemeCompagnie($bordereau, $finance);

            if ($bordereau->statut !== StatutBordereauVersement::Emis) {
                throw new DomainException('Seul un bordereau émis peut être pris en charge.');
            }

            if ((int) $bordereau->emitted_by_id === (int) $finance->id) {
                throw new DomainException('Le vendeur ne peut pas réceptionner son propre versement.');
            }

            if ($montantRecu !== $bordereau->montant_declare && blank($note)) {
                throw new DomainException('Une note est obligatoire lorsqu’un écart est constaté à la réception.');
            }

            $bordereau->update([
                'montant_recu' => $montantRecu,
                'received_by_id' => $finance->id,
                'received_at' => now(),
                'note_reception' => $note,
                'statut' => StatutBordereauVersement::RecuFinance,
            ]);

            return $bordereau->refresh();
        });
    }

    public function transmettreComptabilite(BordereauVersement $bordereau, User $finance, ?string $reference, ?string $note = null): BordereauVersement
    {
        return DB::transaction(function () use ($bordereau, $finance, $reference, $note): BordereauVersement {
            $bordereau = BordereauVersement::query()->lockForUpdate()->findOrFail($bordereau->id);
            $this->assertMemeCompagnie($bordereau, $finance);

            if ($bordereau->statut !== StatutBordereauVersement::RecuFinance) {
                throw new DomainException('Seul un bordereau reçu par la finance peut être transmis à la comptabilité.');
            }

            if ((int) $bordereau->received_by_id !== (int) $finance->id) {
                throw new DomainException('Seul le financier qui a reçu le versement peut le transmettre.');
            }

            $bordereau->update([
                'transmitted_by_id' => $finance->id,
                'transmitted_at' => now(),
                'reference_comptable' => $reference,
                'note_transmission' => $note,
                'statut' => StatutBordereauVersement::TransmisComptabilite,
            ]);

            return $bordereau->refresh();
        });
    }

    public function comptabiliser(BordereauVersement $bordereau, User $comptable, int $montantComptabilise, ?string $note = null): BordereauVersement
    {
        if ($montantComptabilise < 0) {
            throw new DomainException('Le montant comptabilisé ne peut pas être négatif.');
        }

        return DB::transaction(function () use ($bordereau, $comptable, $montantComptabilise, $note): BordereauVersement {
            $bordereau = BordereauVersement::query()->lockForUpdate()->findOrFail($bordereau->id);
            $this->assertMemeCompagnie($bordereau, $comptable);

            if ($bordereau->statut !== StatutBordereauVersement::TransmisComptabilite) {
                throw new DomainException('Seul un bordereau transmis à la comptabilité peut être comptabilisé.');
            }

            if ((int) $bordereau->received_by_id === (int) $comptable->id) {
                throw new DomainException('Le financier qui a réceptionné le versement ne peut pas le comptabiliser.');
            }

            if ($montantComptabilise !== $bordereau->montant_recu && blank($note)) {
                throw new DomainException('Une note est obligatoire lorsqu’un écart est constaté en comptabilité.');
            }

            $recette = Recette::create([
                'compagnie_id' => $bordereau->compagnie_id,
                'libelle' => 'Versement de caisse '.$bordereau->numero,
                'montant' => $montantComptabilise,
                'date_recette' => now()->toDateString(),
                'source' => 'Versement de caisse',
                'reference' => $bordereau->reference_comptable ?? $bordereau->numero,
                'note' => $note,
                'user_id' => $comptable->id,
            ]);

            $bordereau->update([
                'montant_comptabilise' => $montantComptabilise,
                'accounted_by_id' => $comptable->id,
                'accounted_at' => now(),
                'note_comptabilite' => $note,
                'recette_id' => $recette->id,
                'statut' => StatutBordereauVersement::Comptabilise,
            ]);

            return $bordereau->refresh();
        });
    }

    private function assertMemeCompagnie(BordereauVersement $bordereau, User $user): void
    {
        if ((int) $bordereau->compagnie_id !== (int) $user->compagnie_id) {
            throw new DomainException('Ce bordereau ne relève pas de votre compagnie.');
        }
    }
}
