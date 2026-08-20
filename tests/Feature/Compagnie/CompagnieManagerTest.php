<?php

namespace Tests\Feature\Compagnie;

use App\Enums\UserRole;
use App\Livewire\Admin\CompagnieManager;
use App\Models\Compagnie\Compagnie;
use App\Models\Statut;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Gestion des compagnies depuis le panel d'administration.
 *
 * Le statut est une colonne surveillée : il n'était pas assignable en masse,
 * ce qui faisait échouer silencieusement tout changement d'état.
 */
class CompagnieManagerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function statut(string $name): Statut
    {
        return Statut::where('name', $name)->firstOrFail();
    }

    public function test_le_changement_de_statut_est_persiste(): void
    {
        $compagnie = Compagnie::factory()->create(['statut_id' => $this->statut('Désactiver')->id]);
        $actif = $this->statut('Activer');

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('changeStatut', $compagnie->id, $actif->id);

        $this->assertSame($actif->id, $compagnie->fresh()->statut_id);
    }

    public function test_le_statut_est_assignable_en_masse(): void
    {
        $compagnie = Compagnie::factory()->create();
        $bloque = $this->statut('Bloquer');

        $compagnie->update(['statut_id' => $bloque->id]);

        $this->assertSame($bloque->id, $compagnie->fresh()->statut_id);
    }

    public function test_un_statut_inexistant_est_refuse(): void
    {
        $compagnie = Compagnie::factory()->create(['statut_id' => $this->statut('Activer')->id]);

        try {
            Livewire::actingAs($this->admin())
                ->test(CompagnieManager::class)
                ->call('changeStatut', $compagnie->id, 9999);

            $this->fail('Un statut inexistant aurait dû être rejeté.');
        } catch (ModelNotFoundException) {
            // Comportement attendu : la requête forgée n'aboutit pas.
        }

        $this->assertSame($this->statut('Activer')->id, $compagnie->fresh()->statut_id);
    }

    public function test_le_formulaire_enregistre_le_statut_choisi(): void
    {
        $pause = $this->statut('Pause');

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('openCreate')
            ->set('name', 'Transport Sahel')
            ->set('sigle', 'TSA')
            ->set('statut_id', $pause->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($pause->id, Compagnie::where('sigle', 'TSA')->firstOrFail()->statut_id);
    }

    // ── Logo ────────────────────────────────────────────────────────────────

    public function test_le_logo_est_stocke_a_la_creation(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('openCreate')
            ->set('name', 'Rakieta Transport')
            ->set('sigle', 'RKT')
            ->set('logo', UploadedFile::fake()->image('logo.png', 400, 400))
            ->call('save')
            ->assertHasNoErrors();

        $compagnie = Compagnie::where('sigle', 'RKT')->firstOrFail();

        $this->assertNotNull($compagnie->logo_uri);
        Storage::disk('public')->assertExists($compagnie->logo_uri);
    }

    public function test_remplacer_le_logo_efface_lancien_fichier(): void
    {
        Storage::fake('public');

        $ancien = UploadedFile::fake()->image('ancien.png')->store('compagnies', 'public');
        $compagnie = Compagnie::factory()->create(['logo_uri' => $ancien]);

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('openEdit', $compagnie->id)
            ->assertSet('existingLogo', $ancien)
            ->set('logo', UploadedFile::fake()->image('nouveau.png'))
            ->call('save')
            ->assertHasNoErrors();

        $nouveau = $compagnie->fresh()->logo_uri;

        $this->assertNotSame($ancien, $nouveau);
        Storage::disk('public')->assertExists($nouveau);
        Storage::disk('public')->assertMissing($ancien);
    }

    public function test_retirer_le_logo_le_supprime_du_disque_et_de_la_base(): void
    {
        Storage::fake('public');

        $chemin = UploadedFile::fake()->image('logo.png')->store('compagnies', 'public');
        $compagnie = Compagnie::factory()->create(['logo_uri' => $chemin]);

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('openEdit', $compagnie->id)
            ->call('removeLogo')
            ->assertSet('existingLogo', null);

        $this->assertNull($compagnie->fresh()->logo_uri);
        Storage::disk('public')->assertMissing($chemin);
    }

    public function test_retirer_annule_dabord_le_fichier_en_attente(): void
    {
        Storage::fake('public');

        $chemin = UploadedFile::fake()->image('logo.png')->store('compagnies', 'public');
        $compagnie = Compagnie::factory()->create(['logo_uri' => $chemin]);

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('openEdit', $compagnie->id)
            ->set('logo', UploadedFile::fake()->image('nouveau.png'))
            ->call('removeLogo')
            ->assertSet('logo', null)
            ->assertSet('existingLogo', $chemin);

        $this->assertSame($chemin, $compagnie->fresh()->logo_uri);
        Storage::disk('public')->assertExists($chemin);
    }

    public function test_le_logo_conserve_sa_valeur_quand_aucun_fichier_nest_envoye(): void
    {
        Storage::fake('public');

        $chemin = UploadedFile::fake()->image('logo.png')->store('compagnies', 'public');
        $compagnie = Compagnie::factory()->create(['logo_uri' => $chemin]);

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('openEdit', $compagnie->id)
            ->set('slogant', 'Nouveau slogan')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($chemin, $compagnie->fresh()->logo_uri);
        Storage::disk('public')->assertExists($chemin);
    }

    public function test_un_fichier_trop_lourd_est_refuse_des_le_depot(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('openCreate')
            ->set('logo', UploadedFile::fake()->image('enorme.png')->size(4096))
            ->assertHasErrors(['logo' => 'max']);
    }

    public function test_un_fichier_non_image_est_refuse(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('openCreate')
            ->set('logo', UploadedFile::fake()->create('contrat.pdf', 100, 'application/pdf'))
            ->assertHasErrors('logo');
    }

    public function test_supprimer_une_compagnie_efface_son_logo(): void
    {
        Storage::fake('public');

        $chemin = UploadedFile::fake()->image('logo.png')->store('compagnies', 'public');
        $compagnie = Compagnie::factory()->create(['logo_uri' => $chemin]);

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('delete', $compagnie->id);

        Storage::disk('public')->assertMissing($chemin);
    }

    public function test_fermer_la_modale_reinitialise_le_formulaire(): void
    {
        $compagnie = Compagnie::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('openEdit', $compagnie->id)
            ->call('closeModal')
            ->assertSet('showModal', false)
            ->assertSet('editingId', null)
            ->assertSet('name', '')
            ->assertSet('existingLogo', null);
    }

    public function test_un_sigle_deja_pris_est_refuse(): void
    {
        Compagnie::factory()->create(['sigle' => 'RKT']);

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('openCreate')
            ->set('name', 'Autre compagnie')
            ->set('sigle', 'RKT')
            ->call('save')
            ->assertHasErrors(['sigle' => 'unique']);
    }

    public function test_une_compagnie_peut_garder_son_propre_sigle_en_edition(): void
    {
        $compagnie = Compagnie::factory()->create(['sigle' => 'RKT']);

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->call('openEdit', $compagnie->id)
            ->set('slogant', 'Toujours à l\'heure')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Toujours à l\'heure', $compagnie->fresh()->slogant);
    }

    public function test_la_liste_filtre_par_statut(): void
    {
        $actif = $this->statut('Activer');
        Compagnie::factory()->create(['name' => 'Compagnie Active', 'statut_id' => $actif->id]);
        Compagnie::factory()->create(['name' => 'Compagnie Bloquée', 'statut_id' => $this->statut('Bloquer')->id]);

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->set('statutFilter', $actif->id)
            ->assertSee('Compagnie Active')
            ->assertDontSee('Compagnie Bloquée');
    }

    public function test_la_recherche_combine_correctement_le_filtre_de_statut(): void
    {
        $actif = $this->statut('Activer');
        Compagnie::factory()->create(['name' => 'Rakieta Transport', 'sigle' => 'RKT', 'statut_id' => $actif->id]);
        Compagnie::factory()->create(['name' => 'Rakieta Express', 'sigle' => 'RKE', 'statut_id' => $this->statut('Bloquer')->id]);

        Livewire::actingAs($this->admin())
            ->test(CompagnieManager::class)
            ->set('search', 'Rakieta')
            ->set('statutFilter', $actif->id)
            ->assertSee('Rakieta Transport')
            ->assertDontSee('Rakieta Express');
    }
}
