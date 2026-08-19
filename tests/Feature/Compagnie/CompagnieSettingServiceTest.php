<?php

namespace Tests\Feature\Compagnie;

use App\Enums\CompagnieSettingGroup;
use App\Enums\CompagnieSettingKey;
use App\Models\Compagnie\Compagnie;
use App\Models\CompagnieSetting;
use App\Services\Compagnie\CompagnieSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Le paramétrage repose sur un catalogue de défauts : une compagnie n'a de
 * ligne en base que pour les paramètres qu'elle personnalise réellement.
 */
class CompagnieSettingServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompagnieSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CompagnieSettingService::class);
    }

    public function test_les_defauts_du_catalogue_sappliquent_sans_ligne_en_base(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->assertSame(0, CompagnieSetting::forCompagnie($compagnie->id)->count());
        $this->assertSame('XOF', $this->service->get($compagnie, CompagnieSettingKey::DEVISE));
        $this->assertSame(24, $this->service->get($compagnie, CompagnieSettingKey::DELAI_ANNULATION));
        $this->assertTrue($this->service->get($compagnie, CompagnieSettingKey::PAIEMENT_EN_LIGNE));
    }

    public function test_chaque_compagnie_a_ses_propres_valeurs(): void
    {
        $premiere = Compagnie::factory()->create();
        $seconde  = Compagnie::factory()->create();

        $this->service->set($premiere, CompagnieSettingKey::DELAI_ANNULATION, 48);

        $this->assertSame(48, $this->service->get($premiere, CompagnieSettingKey::DELAI_ANNULATION));
        $this->assertSame(24, $this->service->get($seconde, CompagnieSettingKey::DELAI_ANNULATION));
    }

    public function test_sync_ne_persiste_que_les_ecarts_au_defaut(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->service->sync($compagnie, [
            CompagnieSettingKey::DELAI_ANNULATION->value => 48,
            CompagnieSettingKey::DEVISE->value           => 'XOF', // valeur par défaut
        ]);

        $this->assertSame(
            [CompagnieSettingKey::DELAI_ANNULATION->value],
            $this->service->customizedKeys($compagnie),
        );
    }

    public function test_revenir_au_defaut_supprime_la_ligne(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->service->sync($compagnie, [CompagnieSettingKey::DELAI_ANNULATION->value => 48]);
        $this->assertCount(1, $this->service->customizedKeys($compagnie));

        $this->service->sync($compagnie, [CompagnieSettingKey::DELAI_ANNULATION->value => 24]);

        $this->assertSame([], $this->service->customizedKeys($compagnie));
        $this->assertSame(24, $this->service->get($compagnie, CompagnieSettingKey::DELAI_ANNULATION));
    }

    public function test_les_valeurs_sont_converties_dans_leur_type(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->service->sync($compagnie, [
            CompagnieSettingKey::PAIEMENT_EN_LIGNE->value      => '0',
            CompagnieSettingKey::CLOTURE_VENTE_MINUTES->value  => '45',
            CompagnieSettingKey::COMMISSION_PLATEFORME->value  => '7.5',
            CompagnieSettingKey::MOYENS_PAIEMENT_ACTIFS->value => ['Wave', 'Espèce'],
        ], allowAdminOnly: true);

        $parametres = $this->service->bag($compagnie);

        $this->assertFalse($parametres->bool(CompagnieSettingKey::PAIEMENT_EN_LIGNE));
        $this->assertSame(45, $parametres->int(CompagnieSettingKey::CLOTURE_VENTE_MINUTES));
        $this->assertSame(7.5, $parametres->float(CompagnieSettingKey::COMMISSION_PLATEFORME));
        $this->assertSame(['Wave', 'Espèce'], $parametres->moyensPaiementActifs());
    }

    public function test_une_valeur_hors_bornes_est_rejetee(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->set($compagnie, CompagnieSettingKey::PENALITE_ANNULATION, 150);
    }

    public function test_une_option_inconnue_est_rejetee(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->set($compagnie, CompagnieSettingKey::DEVISE, 'BTC');
    }

    public function test_un_parametre_avance_exige_une_autorisation_explicite(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->set($compagnie, CompagnieSettingKey::COMMISSION_PLATEFORME, 12);
    }

    public function test_les_cles_inconnues_sont_ignorees(): void
    {
        $compagnie = Compagnie::factory()->create();

        $ecrites = $this->service->sync($compagnie, [
            'parametre_qui_nexiste_pas'                 => 'valeur',
            CompagnieSettingKey::DELAI_ANNULATION->value => 48,
        ]);

        $this->assertSame([CompagnieSettingKey::DELAI_ANNULATION->value], array_keys($ecrites));
    }

    public function test_le_cache_est_invalide_apres_ecriture(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->assertSame(24, $this->service->get($compagnie, CompagnieSettingKey::DELAI_ANNULATION));

        $this->service->set($compagnie, CompagnieSettingKey::DELAI_ANNULATION, 72);

        $this->assertSame(72, $this->service->get($compagnie, CompagnieSettingKey::DELAI_ANNULATION));
    }

    public function test_reset_group_ne_touche_que_son_groupe(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->service->sync($compagnie, [
            CompagnieSettingKey::DELAI_ANNULATION->value => 48,
            CompagnieSettingKey::DEVISE->value           => 'EUR',
        ]);

        $this->service->resetGroup($compagnie, CompagnieSettingGroup::Annulation);

        $this->assertSame(24, $this->service->get($compagnie, CompagnieSettingKey::DELAI_ANNULATION));
        $this->assertSame('EUR', $this->service->get($compagnie, CompagnieSettingKey::DEVISE));
    }

    public function test_reset_all_efface_tout_le_parametrage(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->service->sync($compagnie, [
            CompagnieSettingKey::DELAI_ANNULATION->value => 48,
            CompagnieSettingKey::DEVISE->value           => 'EUR',
        ]);

        $this->service->resetAll($compagnie);

        $this->assertSame([], $this->service->customizedKeys($compagnie));
        $this->assertSame('XOF', $this->service->get($compagnie, CompagnieSettingKey::DEVISE));
    }

    public function test_les_parametres_publics_excluent_les_parametres_internes(): void
    {
        $compagnie = Compagnie::factory()->create();

        $publics = $this->service->publicSettings($compagnie);

        $this->assertArrayHasKey(CompagnieSettingKey::DEVISE->value, $publics);
        $this->assertArrayNotHasKey(CompagnieSettingKey::COMMISSION_PLATEFORME->value, $publics);
        $this->assertArrayNotHasKey(CompagnieSettingKey::SIGNATURE_SMS->value, $publics);
    }

    public function test_le_catalogue_masque_le_groupe_avance_aux_compagnies(): void
    {
        $this->assertArrayNotHasKey(CompagnieSettingGroup::Avance->value, $this->service->catalogue());
        $this->assertArrayHasKey(CompagnieSettingGroup::Avance->value, $this->service->catalogue(includeAdminOnly: true));
    }

    public function test_chaque_cle_du_catalogue_expose_une_definition_coherente(): void
    {
        foreach (CompagnieSettingKey::cases() as $key) {
            $definition = $key->definition();

            $this->assertSame($key, $definition->key);
            $this->assertNotSame('', $definition->label, "Libellé manquant pour {$key->value}.");
            $this->assertNotNull(
                $definition->type->serialize($definition->default),
                "Valeur par défaut non sérialisable pour {$key->value}.",
            );
        }
    }

    public function test_les_raccourcis_metier_du_sac_reflètent_le_parametrage(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->service->sync($compagnie, [
            CompagnieSettingKey::PENALITE_ANNULATION->value => 20,
            CompagnieSettingKey::FRAIS_SERVICE->value       => 10,
            CompagnieSettingKey::FRAIS_SERVICE_TYPE->value  => 'pourcentage',
        ]);

        $parametres = $compagnie->parametres();

        $this->assertSame(8000, $parametres->montantRembourse(10000));
        $this->assertSame(1000, $parametres->fraisServicePour(10000));
    }

    public function test_le_mode_maintenance_ferme_la_reservation_en_ligne(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->service->set($compagnie, CompagnieSettingKey::MODE_MAINTENANCE, true, allowAdminOnly: true);

        $this->assertFalse($compagnie->parametres()->reservationEnLigneActive());
    }
}
