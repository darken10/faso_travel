<?php

namespace Tests\Feature\Compagnie;

use App\Enums\CompagnieSettingKey;
use App\Enums\UserRole;
use App\Livewire\Admin\SettingsManager;
use App\Livewire\Compagnie\Parametre\ParametreManager;
use App\Models\Compagnie\Compagnie;
use App\Models\User;
use App\Services\Compagnie\CompagnieSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Une compagnie ne configure qu'elle-même ; un administrateur de la plateforme
 * configure n'importe quelle compagnie, groupe « Avancé » compris.
 */
class CompagnieSettingPanelTest extends TestCase
{
    use RefreshDatabase;

    private function service(): CompagnieSettingService
    {
        return app(CompagnieSettingService::class);
    }

    /** Responsable de la compagnie : propriétaire du compte compagnie. */
    private function directeur(Compagnie $compagnie): User
    {
        $user = User::factory()->create([
            'compagnie_id' => $compagnie->id,
            'role'         => UserRole::CompagnieBosse,
        ]);

        $compagnie->forceFill(['user_id' => $user->id])->save();

        return $user;
    }

    private function agent(Compagnie $compagnie): User
    {
        return User::factory()->create([
            'compagnie_id' => $compagnie->id,
            'role'         => UserRole::User,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    // ── Panel compagnie ─────────────────────────────────────────────────────

    public function test_le_responsable_configure_sa_compagnie(): void
    {
        $compagnie = Compagnie::factory()->create();

        Livewire::actingAs($this->directeur($compagnie))
            ->test(ParametreManager::class)
            ->assertSet('compagnieId', $compagnie->id)
            ->set('values.'.CompagnieSettingKey::DELAI_ANNULATION->value, 48)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(48, $this->service()->get($compagnie, CompagnieSettingKey::DELAI_ANNULATION));
    }

    public function test_le_panel_compagnie_nexpose_pas_les_parametres_avances(): void
    {
        $compagnie = Compagnie::factory()->create();

        $component = Livewire::actingAs($this->directeur($compagnie))->test(ParametreManager::class);

        $this->assertFalse($component->instance()->canManageAdvanced());
        $this->assertArrayNotHasKey('avance', $component->viewData('catalogue'));
    }

    public function test_linterrupteur_bascule_puis_persiste_le_parametre(): void
    {
        $compagnie = Compagnie::factory()->create();
        $cle = CompagnieSettingKey::PIECE_IDENTITE_OBLIGATOIRE->value;

        Livewire::actingAs($this->directeur($compagnie))
            ->test(ParametreManager::class)
            ->assertSet("values.{$cle}", false)
            ->call('toggleSetting', $cle)
            ->assertSet("values.{$cle}", true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($this->service()->get($compagnie, CompagnieSettingKey::PIECE_IDENTITE_OBLIGATOIRE));
    }

    public function test_linterrupteur_reste_inerte_en_consultation_seule(): void
    {
        $compagnie = Compagnie::factory()->create();
        $cle = CompagnieSettingKey::PIECE_IDENTITE_OBLIGATOIRE->value;

        Livewire::actingAs($this->agent($compagnie))
            ->test(ParametreManager::class)
            ->call('toggleSetting', $cle)
            ->assertSet("values.{$cle}", false);
    }

    public function test_linterrupteur_ignore_un_parametre_avance_pour_une_compagnie(): void
    {
        $compagnie = Compagnie::factory()->create();
        $cle = CompagnieSettingKey::MODE_MAINTENANCE->value;

        Livewire::actingAs($this->directeur($compagnie))
            ->test(ParametreManager::class)
            ->call('toggleSetting', $cle)
            ->assertSet("values.{$cle}", false);
    }

    public function test_un_agent_sans_droit_est_en_consultation_seule(): void
    {
        $compagnie = Compagnie::factory()->create();

        $component = Livewire::actingAs($this->agent($compagnie))->test(ParametreManager::class);

        $this->assertTrue($component->instance()->isReadOnly());
    }

    public function test_un_agent_sans_droit_ne_peut_pas_enregistrer(): void
    {
        $compagnie = Compagnie::factory()->create();

        Livewire::actingAs($this->agent($compagnie))
            ->test(ParametreManager::class)
            ->set('values.'.CompagnieSettingKey::DELAI_ANNULATION->value, 48)
            ->call('save')
            ->assertForbidden();

        $this->assertSame(24, $this->service()->get($compagnie, CompagnieSettingKey::DELAI_ANNULATION));
    }

    public function test_un_responsable_ne_peut_pas_ecrire_un_parametre_avance(): void
    {
        $compagnie = Compagnie::factory()->create();

        Livewire::actingAs($this->directeur($compagnie))
            ->test(ParametreManager::class)
            ->set('values.'.CompagnieSettingKey::COMMISSION_PLATEFORME->value, 42)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(5.0, $this->service()->get($compagnie, CompagnieSettingKey::COMMISSION_PLATEFORME));
    }

    // ── Panel administrateur ────────────────────────────────────────────────

    public function test_ladministrateur_configure_nimporte_quelle_compagnie(): void
    {
        $premiere = Compagnie::factory()->create();
        $seconde  = Compagnie::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(SettingsManager::class)
            ->set('selectedCompagnieId', $seconde->id)
            ->set('values.'.CompagnieSettingKey::DEVISE->value, 'EUR')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('EUR', $this->service()->get($seconde, CompagnieSettingKey::DEVISE));
        $this->assertSame('XOF', $this->service()->get($premiere, CompagnieSettingKey::DEVISE));
    }

    public function test_ladministrateur_accede_aux_parametres_avances(): void
    {
        $compagnie = Compagnie::factory()->create();

        $component = Livewire::actingAs($this->admin())
            ->test(SettingsManager::class)
            ->set('selectedCompagnieId', $compagnie->id);

        $this->assertArrayHasKey('avance', $component->viewData('catalogue'));

        $component->set('values.'.CompagnieSettingKey::COMMISSION_PLATEFORME->value, 12)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(12.0, $this->service()->get($compagnie, CompagnieSettingKey::COMMISSION_PLATEFORME));
    }

    public function test_ladministrateur_reinitialise_tout_le_parametrage(): void
    {
        $compagnie = Compagnie::factory()->create();
        $this->service()->set($compagnie, CompagnieSettingKey::DEVISE, 'EUR');

        Livewire::actingAs($this->admin())
            ->test(SettingsManager::class)
            ->set('selectedCompagnieId', $compagnie->id)
            ->call('resetAll');

        $this->assertSame([], $this->service()->customizedKeys($compagnie));
    }

    public function test_une_valeur_invalide_est_signalee_dans_le_formulaire(): void
    {
        $compagnie = Compagnie::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(SettingsManager::class)
            ->set('selectedCompagnieId', $compagnie->id)
            ->set('values.'.CompagnieSettingKey::PENALITE_ANNULATION->value, 150)
            ->call('save')
            ->assertHasErrors('values.'.CompagnieSettingKey::PENALITE_ANNULATION->value);

        $this->assertSame([], $this->service()->customizedKeys($compagnie));
    }

    public function test_un_simple_client_na_pas_acces_au_panel_admin(): void
    {
        Livewire::actingAs(User::factory()->create(['role' => UserRole::User]))
            ->test(SettingsManager::class)
            ->assertForbidden();
    }

    // ── API mobile ──────────────────────────────────────────────────────────

    public function test_lapi_publique_expose_les_parametres_visibles_par_le_voyageur(): void
    {
        $compagnie = Compagnie::factory()->create();
        $this->service()->set($compagnie, CompagnieSettingKey::PAIEMENT_EN_LIGNE, false);

        $this->getJson("/api/v2/companies/{$compagnie->id}/settings")
            ->assertOk()
            ->assertJsonPath('data.compagnie_id', $compagnie->id)
            ->assertJsonPath('data.settings.'.CompagnieSettingKey::PAIEMENT_EN_LIGNE->value, false)
            ->assertJsonPath('data.settings.'.CompagnieSettingKey::DEVISE->value, 'XOF')
            ->assertJsonMissingPath('data.settings.'.CompagnieSettingKey::COMMISSION_PLATEFORME->value);
    }

    public function test_lapi_compagnie_renvoie_le_catalogue_sans_les_parametres_avances(): void
    {
        $compagnie = Compagnie::factory()->create();

        $response = $this->actingAs($this->directeur($compagnie), 'sanctum')
            ->getJson('/api/v2/compagnie/settings')
            ->assertOk()
            ->assertJsonPath('data.can_update', true)
            ->assertJsonMissingPath('data.settings.'.CompagnieSettingKey::COMMISSION_PLATEFORME->value);

        $groupes = array_column($response->json('data.catalogue'), 'group');
        $this->assertNotContains('avance', $groupes);
    }

    public function test_lapi_compagnie_met_a_jour_les_parametres(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->actingAs($this->directeur($compagnie), 'sanctum')
            ->putJson('/api/v2/compagnie/settings', [
                'settings' => [CompagnieSettingKey::BAGAGE_GRATUIT_KG->value => 30],
            ])
            ->assertOk()
            ->assertJsonPath('data.settings.'.CompagnieSettingKey::BAGAGE_GRATUIT_KG->value, 30);

        $this->assertSame(30, $this->service()->get($compagnie, CompagnieSettingKey::BAGAGE_GRATUIT_KG));
    }

    public function test_lapi_compagnie_refuse_une_valeur_invalide(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->actingAs($this->directeur($compagnie), 'sanctum')
            ->putJson('/api/v2/compagnie/settings', [
                'settings' => [CompagnieSettingKey::BAGAGE_GRATUIT_KG->value => 5000],
            ])
            ->assertStatus(422);

        $this->assertSame(20, $this->service()->get($compagnie, CompagnieSettingKey::BAGAGE_GRATUIT_KG));
    }

    public function test_lapi_compagnie_est_fermee_a_un_compte_sans_compagnie(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/v2/compagnie/settings')
            ->assertForbidden();
    }
}
