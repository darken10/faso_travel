<?php

namespace Tests\Feature\Rbac;

use App\Enums\UserRole;
use App\Livewire\Compagnie\Compagnie\UserManager;
use App\Models\Compagnie\Compagnie;
use App\Models\Compagnie\Gare;
use App\Models\Finance\Caisse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Voyage\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PorteeGareTest extends TestCase
{
    use RefreshDatabase;

    private Compagnie $compagnie;

    private Gare $ouaga;

    private Gare $bobo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compagnie = Compagnie::factory()->create();
        $this->ouaga = $this->gare('Ouagadougou');
        $this->bobo = $this->gare('Bobo-Dioulasso');
    }

    public function test_un_agent_ne_voit_que_les_voyages_de_sa_gare(): void
    {
        $agent = $this->agentDeGare([$this->ouaga]);

        $depuisOuaga = $this->voyage($this->ouaga, $this->bobo);
        $depuisBobo = $this->voyage($this->bobo, $this->ouaga);

        $this->assertTrue($agent->hasPermission('guichet.ticket.view.gare', $depuisOuaga));
        $this->assertTrue(
            $agent->hasPermission('guichet.ticket.view.gare', $depuisBobo),
            'Un voyage dont la gare d\'arrivée est la sienne le concerne aussi : le retour en part.'
        );
    }

    public function test_un_voyage_entre_deux_gares_etrangeres_est_refuse(): void
    {
        $agent = $this->agentDeGare([$this->ouaga]);
        $koudougou = $this->gare('Koudougou');
        $banfora = $this->gare('Banfora');

        $this->assertFalse(
            $agent->hasPermission('guichet.ticket.view.gare', $this->voyage($koudougou, $banfora))
        );
    }

    public function test_un_agent_affecte_a_deux_gares_voit_les_deux(): void
    {
        $agent = $this->agentDeGare([$this->ouaga, $this->bobo]);
        $koudougou = $this->gare('Koudougou');

        $this->assertTrue($agent->hasPermission('guichet.ticket.view.gare', $this->voyage($this->ouaga, $koudougou)));
        $this->assertTrue($agent->hasPermission('guichet.ticket.view.gare', $this->voyage($this->bobo, $koudougou)));
        $this->assertCount(2, $agent->gareIds());
    }

    public function test_un_agent_sans_gare_ne_voit_rien_en_portee_gare(): void
    {
        $agent = $this->agentDeGare([]);

        $this->assertFalse(
            $agent->hasPermission('guichet.ticket.view.gare', $this->voyage($this->ouaga, $this->bobo)),
            'Un compte de terrain sans gare est une affectation oubliée : rbac:gares-manquantes le signale.'
        );
    }

    public function test_un_voyage_d_une_autre_compagnie_est_refuse(): void
    {
        $agent = $this->agentDeGare([$this->ouaga]);
        $autreCompagnie = Compagnie::factory()->create();

        $voyageEtranger = Voyage::factory()->create([
            'compagnie_id' => $autreCompagnie->id,
            'depart_id' => $this->ouaga->id,
            'arrive_id' => $this->bobo->id,
        ]);

        // La vérification de compagnie passe avant celle de la gare : même si le départ
        // se fait de sa gare, un voyage d'un concurrent ne le concerne pas.
        $this->assertFalse($agent->hasPermission('guichet.ticket.view.gare', $voyageEtranger));
    }

    public function test_une_gare_d_une_autre_compagnie_ne_peut_pas_etre_affectee(): void
    {
        $patron = User::factory()->create([
            'compagnie_id' => $this->compagnie->id,
            'role' => UserRole::CompagnieBosse,
        ]);
        $gareEtrangere = $this->gare('Gare concurrente', Compagnie::factory()->create());

        Livewire::actingAs($patron)
            ->test(UserManager::class)
            ->set('first_name', 'Awa')
            ->set('last_name', 'Kaboré')
            ->set('email', 'awa@example.test')
            ->set('sexe', 'Femme')
            ->set('selectedRoles', [$this->role('guichetier')->id])
            ->set('selectedGares', [$gareEtrangere->id])
            ->call('save')
            ->assertHasErrors(['selectedGares.0']);

        $this->assertDatabaseCount('gare_user', 0);
    }

    public function test_une_gare_de_sa_compagnie_est_affectee(): void
    {
        $patron = User::factory()->create([
            'compagnie_id' => $this->compagnie->id,
            'role' => UserRole::CompagnieBosse,
        ]);

        Livewire::actingAs($patron)
            ->test(UserManager::class)
            ->set('first_name', 'Awa')
            ->set('last_name', 'Kaboré')
            ->set('email', 'awa@example.test')
            ->set('sexe', 'Femme')
            ->set('selectedRoles', [$this->role('guichetier')->id])
            ->set('selectedGares', [$this->bobo->id])
            ->set('garePrincipale', $this->bobo->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('gare_user', [
            'gare_id' => $this->bobo->id,
            'is_principale' => true,
        ]);
    }

    public function test_la_gare_principale_doit_faire_partie_des_affectations(): void
    {
        $patron = User::factory()->create([
            'compagnie_id' => $this->compagnie->id,
            'role' => UserRole::CompagnieBosse,
        ]);

        Livewire::actingAs($patron)
            ->test(UserManager::class)
            ->set('first_name', 'Awa')
            ->set('last_name', 'Kaboré')
            ->set('email', 'awa@example.test')
            ->set('sexe', 'Femme')
            ->set('selectedRoles', [$this->role('guichetier')->id])
            ->set('selectedGares', [$this->bobo->id])
            ->set('garePrincipale', $this->ouaga->id)
            ->call('save')
            ->assertHasErrors(['garePrincipale']);
    }

    public function test_un_sujet_dont_la_gare_est_indeterminable_est_refuse(): void
    {
        $agent = $this->agentDeGare([$this->ouaga]);

        // Un véhicule relève de la compagnie, pas d'une gare : en portée gare, le doute
        // sur la localisation ne doit jamais se résoudre en autorisation.
        $vehicule = new \App\Models\Compagnie\Care(['compagnie_id' => $this->compagnie->id]);

        $this->assertFalse($agent->hasPermission('guichet.ticket.view.gare', $vehicule));
    }

    public function test_une_session_de_caisse_herite_des_gares_de_son_titulaire(): void
    {
        $guichetier = $this->agentDeGare([$this->bobo]);
        $chef = $this->agentDeGare([$this->bobo]);
        $chefAilleurs = $this->agentDeGare([$this->ouaga]);

        $caisse = Caisse::create([
            'user_id' => $guichetier->id,
            'compagnie_id' => $this->compagnie->id,
            'montant_ouverture' => 10000,
            'statut' => \App\Enums\StatutCaisse::Ouverte,
            'opened_at' => now(),
        ]);

        $this->assertTrue($chef->hasPermission('guichet.ticket.view.gare', $caisse));
        $this->assertFalse($chefAilleurs->hasPermission('guichet.ticket.view.gare', $caisse));
    }

    public function test_la_gare_principale_est_retournee_en_priorite(): void
    {
        $agent = $this->agentDeGare([]);
        $agent->syncGares([
            $this->ouaga->id => ['is_principale' => false],
            $this->bobo->id => ['is_principale' => true],
        ]);

        $this->assertSame($this->bobo->id, $agent->garePrincipale()?->id);
    }

    public function test_sync_gares_vide_le_memo(): void
    {
        $agent = $this->agentDeGare([$this->ouaga]);
        $this->assertSame([$this->ouaga->id], $agent->gareIds());

        $agent->syncGares([$this->bobo->id]);

        $this->assertSame([$this->bobo->id], $agent->gareIds());
    }

    public function test_un_compte_non_persiste_n_interroge_pas_le_pivot(): void
    {
        $this->assertSame([], (new User)->gareIds());
    }

    public function test_la_commande_signale_un_agent_sans_gare(): void
    {
        $sansGare = $this->agentDeGare([]);
        $this->agentDeGare([$this->ouaga]);

        $this->artisan('rbac:gares-manquantes')
            ->expectsOutputToContain('1 compte(s) de terrain sans gare')
            ->assertFailed();

        $sansGare->syncGares([$this->ouaga->id]);

        $this->artisan('rbac:gares-manquantes')
            ->expectsOutputToContain('Tous les comptes de terrain portent au moins une gare.')
            ->assertSuccessful();
    }

    public function test_la_commande_ignore_les_roles_de_siege(): void
    {
        $comptable = $this->compte();
        $comptable->roles()->attach($this->role('comptable')->id);

        $this->artisan('rbac:gares-manquantes')
            ->expectsOutputToContain('Tous les comptes de terrain portent au moins une gare.')
            ->assertSuccessful();
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    private function compte(): User
    {
        return User::factory()->create([
            'role' => UserRole::User,
            'compagnie_id' => $this->compagnie->id,
        ]);
    }

    /**
     * @param  list<Gare>  $gares
     */
    private function agentDeGare(array $gares): User
    {
        $user = $this->compte();

        $role = $this->role('guichetier');
        $role->permissions()->syncWithoutDetaching([
            $this->permission('guichet.ticket.view.gare')->id => ['portee' => 'gare'],
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->syncGares(array_map(fn (Gare $gare): int => $gare->id, $gares));
        $user->invaliderCachePermissions();

        return $user;
    }

    private function role(string $nom): Role
    {
        return Role::firstOrCreate(
            ['name' => $nom, 'compagnie_id' => null],
            ['label' => $nom, 'scope' => 'compagnie', 'is_system' => true]
        );
    }

    private function permission(string $nom): Permission
    {
        return Permission::firstOrCreate(
            ['name' => $nom],
            ['domaine' => explode('.', $nom)[0], 'label' => $nom, 'scope' => 'compagnie']
        );
    }

    private function gare(string $nom, ?Compagnie $compagnie = null): Gare
    {
        return Gare::factory()->create([
            'name' => $nom,
            'compagnie_id' => ($compagnie ?? $this->compagnie)->id,
        ]);
    }

    private function voyage(Gare $depart, Gare $arrivee): Voyage
    {
        return Voyage::factory()->create([
            'compagnie_id' => $this->compagnie->id,
            'depart_id' => $depart->id,
            'arrive_id' => $arrivee->id,
        ]);
    }
}
