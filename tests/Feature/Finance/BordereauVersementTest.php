<?php

namespace Tests\Feature\Finance;

use App\Enums\StatutBordereauVersement;
use App\Enums\StatutCaisse;
use App\Models\Compagnie\Compagnie;
use App\Models\Finance\BordereauVersement;
use App\Models\Finance\Caisse;
use App\Models\Finance\Recette;
use App\Models\User;
use App\Services\BordereauVersementService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BordereauVersementTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_versement_suit_la_chaine_agent_finance_comptabilite(): void
    {
        [$caisse, $agent, $finance, $comptable] = $this->caisseEtActeurs();
        $service = app(BordereauVersementService::class);

        $bordereau = $service->emettre($caisse, $agent);

        $this->assertSame(StatutBordereauVersement::Emis, $bordereau->statut);
        $this->assertSame(14500, $bordereau->montant_declare);
        $this->assertMatchesRegularExpression('/^BV-\d{8}-\d{6}$/', $bordereau->numero);

        $bordereau = $service->prendreEnCharge($bordereau, $finance, 14400, 'Billet de 100 F manquant.');
        $this->assertSame(StatutBordereauVersement::RecuFinance, $bordereau->statut);
        $this->assertSame(-100, $bordereau->ecartReception());

        $bordereau = $service->transmettreComptabilite($bordereau, $finance, 'REC-2026-0001');
        $this->assertSame(StatutBordereauVersement::TransmisComptabilite, $bordereau->statut);

        $bordereau = $service->comptabiliser($bordereau, $comptable, 14400, 'Comptabilisé après vérification.');
        $this->assertSame(StatutBordereauVersement::Comptabilise, $bordereau->statut);
        $this->assertSame($comptable->id, $bordereau->accounted_by_id);
        $this->assertNotNull($bordereau->recette_id);
        $this->assertDatabaseHas('recettes', [
            'id' => $bordereau->recette_id,
            'compagnie_id' => $caisse->compagnie_id,
            'montant' => 14400,
            'user_id' => $comptable->id,
        ]);
    }

    public function test_un_agent_ne_peut_pas_receptionner_son_propre_bordereau(): void
    {
        [$caisse, $agent] = $this->caisseEtActeurs();
        $service = app(BordereauVersementService::class);
        $bordereau = $service->emettre($caisse, $agent);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('vendeur ne peut pas réceptionner');

        $service->prendreEnCharge($bordereau, $agent, 14500);
    }

    public function test_un_ecart_de_reception_doit_etre_justifie(): void
    {
        [$caisse, $agent, $finance] = $this->caisseEtActeurs();
        $service = app(BordereauVersementService::class);
        $bordereau = $service->emettre($caisse, $agent);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('note est obligatoire');

        $service->prendreEnCharge($bordereau, $finance, 14000);
    }

    /** @return array{0: Caisse, 1: User, 2: User, 3: User} */
    private function caisseEtActeurs(): array
    {
        $compagnie = Compagnie::factory()->create();
        $agent = User::factory()->create(['compagnie_id' => $compagnie->id]);
        $finance = User::factory()->create(['compagnie_id' => $compagnie->id]);
        $comptable = User::factory()->create(['compagnie_id' => $compagnie->id]);

        $caisse = Caisse::create([
            'user_id' => $agent->id,
            'compagnie_id' => $compagnie->id,
            'montant_ouverture' => 500,
            'montant_fermeture' => 14500,
            'montant_attendu' => 14500,
            'statut' => StatutCaisse::Fermee,
            'opened_at' => now()->subHours(8),
            'closed_at' => now(),
        ]);

        return [$caisse, $agent, $finance, $comptable];
    }
}
