<?php

namespace Tests\Feature\Compagnie;

use App\Enums\UserRole;
use App\Livewire\Admin\CompagnieManager;
use App\Models\Compagnie\Compagnie;
use App\Models\Statut;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
