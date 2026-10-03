<?php

namespace Tests\Feature\Rbac;

use App\Enums\UserRole;
use App\Livewire\Compagnie\Caisse\GestionCaisse;
use App\Livewire\Compagnie\Finance\DepenseManager;
use App\Livewire\Compagnie\Ticket\TicketManager;
use App\Models\Compagnie\Compagnie;
use App\Models\Finance\CategorieDepense;
use App\Models\Finance\Depense;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Contrôle des actions des composants, et non seulement de l'accès aux écrans.
 *
 * Une mise à jour Livewire ne repasse pas par la route d'origine : sans contrôle dans la
 * méthode, ouvrir l'écran des dépenses suffirait à en supprimer une.
 */
class ActionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Compagnie $compagnie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compagnie = Compagnie::factory()->create();
    }

    public function test_voir_les_depenses_ne_suffit_pas_pour_en_supprimer_une(): void
    {
        config(['rbac.enforce' => true]);

        $lecteur = $this->membre(['finance.depense.view']);
        $depense = $this->depense();

        Livewire::actingAs($lecteur)
            ->test(DepenseManager::class)
            ->call('delete', $depense->id)
            ->assertForbidden();

        $this->assertDatabaseHas('depenses', ['id' => $depense->id]);
    }

    public function test_la_suppression_passe_avec_la_bonne_permission(): void
    {
        config(['rbac.enforce' => true]);

        $comptable = $this->membre(['finance.depense.view', 'finance.depense.delete']);
        $depense = $this->depense();

        Livewire::actingAs($comptable)
            ->test(DepenseManager::class)
            ->call('delete', $depense->id)
            ->assertOk();

        $this->assertDatabaseMissing('depenses', ['id' => $depense->id]);
    }

    public function test_creer_et_modifier_sont_deux_permissions_distinctes(): void
    {
        config(['rbac.enforce' => true]);

        $createur = $this->membre(['finance.depense.view', 'finance.depense.create']);
        $depense = $this->depense();

        // Création autorisée.
        Livewire::actingAs($createur)
            ->test(DepenseManager::class)
            ->call('openCreate')
            ->set('libelle', 'Péage Boromo')
            ->set('montant', 2000)
            ->set('date_depense', now()->toDateString())
            ->set('categorie_depense_id', $this->categorie()->id)
            ->call('save')
            ->assertOk();

        // Modification refusée : le droit de créer n'est pas celui de corriger.
        Livewire::actingAs($createur)
            ->test(DepenseManager::class)
            ->call('openEdit', $depense->id)
            ->call('save')
            ->assertForbidden();
    }

    public function test_un_guichetier_ne_peut_pas_rembourser_un_ticket(): void
    {
        config(['rbac.enforce' => true]);

        Livewire::actingAs($this->membre(['guichet.ticket.view.gare']))
            ->test(TicketManager::class)
            ->call('rembourser', 1)
            ->assertForbidden();
    }

    public function test_ouvrir_une_caisse_demande_sa_propre_permission(): void
    {
        config(['rbac.enforce' => true]);

        Livewire::actingAs($this->membre(['caisse.session.view.own']))
            ->test(GestionCaisse::class)
            ->call('ouvrirCaisse')
            ->assertForbidden();
    }

    public function test_en_mode_observation_les_actions_passent(): void
    {
        config(['rbac.enforce' => false]);

        $lecteur = $this->membre(['finance.depense.view']);
        $depense = $this->depense();

        Livewire::actingAs($lecteur)
            ->test(DepenseManager::class)
            ->call('delete', $depense->id)
            ->assertOk();

        $this->assertDatabaseMissing('depenses', ['id' => $depense->id]);
    }

    public function test_un_compte_root_passe_toutes_les_actions(): void
    {
        config(['rbac.enforce' => true]);
        $root = User::factory()->create(['role' => UserRole::Root, 'compagnie_id' => $this->compagnie->id]);
        $depense = $this->depense();

        Livewire::actingAs($root)
            ->test(DepenseManager::class)
            ->call('delete', $depense->id)
            ->assertOk();
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    /**
     * @param  list<string>  $permissions
     */
    private function membre(array $permissions): User
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'compagnie_id' => $this->compagnie->id,
        ]);

        $role = Role::firstOrCreate(
            ['name' => 'role_'.md5(implode('|', $permissions)), 'compagnie_id' => null],
            ['label' => 'Rôle de test', 'scope' => 'compagnie', 'is_system' => false]
        );

        foreach ($permissions as $nom) {
            $role->permissions()->syncWithoutDetaching([
                Permission::firstOrCreate(
                    ['name' => $nom],
                    ['domaine' => explode('.', $nom)[0], 'label' => $nom, 'scope' => 'compagnie']
                )->id => ['portee' => 'compagnie'],
            ]);
        }

        $user->roles()->syncWithoutDetaching([$role->id]);
        $user->invaliderCachePermissions();

        return $user;
    }

    private function depense(): Depense
    {
        return Depense::create([
            'compagnie_id' => $this->compagnie->id,
            'categorie_depense_id' => $this->categorie()->id,
            'libelle' => 'Carburant',
            'montant' => 30000,
            'date_depense' => now()->toDateString(),
            'user_id' => User::factory()->create(['compagnie_id' => $this->compagnie->id])->id,
        ]);
    }

    private function categorie(): CategorieDepense
    {
        return CategorieDepense::firstOrCreate([
            'nom' => 'Carburant',
            'compagnie_id' => $this->compagnie->id,
        ]);
    }
}
