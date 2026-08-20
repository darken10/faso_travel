<?php

namespace Tests\Feature\Security;

use App\Enums\UserRole;
use App\Livewire\Compagnie\Compagnie\CareManager;
use App\Livewire\Compagnie\Compagnie\GareManager;
use App\Livewire\Compagnie\Post\PostForm;
use App\Livewire\Compagnie\Voyage\ClasseManager;
use App\Models\Compagnie\Care;
use App\Models\Compagnie\Compagnie;
use App\Models\Compagnie\Gare;
use App\Models\Post\Post;
use App\Models\User;
use App\Models\Voyage\Classe;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cloisonnement des données entre compagnies.
 *
 * Les cloisonnements reposaient sur des global scopes conditionnés à des
 * chemins d'URL (`request()->is('compagnie/compagnie/cares*')`) devenus faux
 * au passage aux sous-domaines : ils ne s'exécutaient plus, et chaque
 * compagnie voyait les données de toutes les autres.
 */
class CompagnieIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function agentDe(Compagnie $compagnie): User
    {
        return User::factory()->create([
            'compagnie_id' => $compagnie->id,
            'role'         => UserRole::CompagnieBosse,
        ]);
    }

    // ── Véhicules ───────────────────────────────────────────────────────────

    public function test_une_compagnie_ne_voit_que_ses_propres_vehicules(): void
    {
        $sienne = Compagnie::factory()->create();
        $autre  = Compagnie::factory()->create();

        $aMoi   = Care::factory()->create(['compagnie_id' => $sienne->id, 'immatrculation' => 'AA-111-BB']);
        $aEux   = Care::factory()->create(['compagnie_id' => $autre->id,  'immatrculation' => 'ZZ-999-YY']);

        Livewire::actingAs($this->agentDe($sienne))
            ->test(CareManager::class)
            ->assertSee($aMoi->immatrculation)
            ->assertDontSee($aEux->immatrculation);
    }

    public function test_un_vehicule_dune_autre_compagnie_est_introuvable(): void
    {
        $sienne = Compagnie::factory()->create();
        $autre  = Compagnie::factory()->create();
        $aEux   = Care::factory()->create(['compagnie_id' => $autre->id]);

        $this->actingAs($this->agentDe($sienne));

        $this->assertNull(Care::find($aEux->id));
    }

    public function test_un_compte_client_nest_pas_filtre_sur_les_vehicules(): void
    {
        Care::factory()->create();
        Care::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::User]));

        $this->assertSame(2, Care::count());
    }

    // ── Gares ───────────────────────────────────────────────────────────────

    public function test_une_compagnie_ne_voit_que_ses_gares_et_les_gares_communes(): void
    {
        $sienne = Compagnie::factory()->create();
        $autre  = Compagnie::factory()->create();

        $aMoi    = Gare::factory()->create(['compagnie_id' => $sienne->id, 'name' => 'Gare Elitis Ouaga']);
        $commune = Gare::factory()->commune()->create(['name' => 'Gare Routière Commune']);
        $aEux    = Gare::factory()->create(['compagnie_id' => $autre->id, 'name' => 'Gare Saramaya Bobo']);

        $this->actingAs($this->agentDe($sienne));

        $visibles = Gare::pluck('id');

        $this->assertContains($aMoi->id, $visibles);
        $this->assertContains($commune->id, $visibles);
        $this->assertNotContains($aEux->id, $visibles);
    }

    public function test_la_liste_des_gares_masque_celles_des_concurrents(): void
    {
        $sienne = Compagnie::factory()->create();
        $autre  = Compagnie::factory()->create();

        Gare::factory()->create(['compagnie_id' => $sienne->id, 'name' => 'Gare Elitis Ouaga']);
        Gare::factory()->create(['compagnie_id' => $autre->id,  'name' => 'Gare Saramaya Bobo']);

        Livewire::actingAs($this->agentDe($sienne))
            ->test(GareManager::class)
            ->assertSee('Gare Elitis Ouaga')
            ->assertDontSee('Gare Saramaya Bobo');
    }

    public function test_un_compte_client_voit_les_gares_de_toutes_les_compagnies(): void
    {
        // Sans quoi la recherche de voyages ne trouverait plus aucun départ.
        Gare::factory()->create();
        Gare::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::User]));

        $this->assertSame(2, Gare::count());
    }

    // ── Classes ─────────────────────────────────────────────────────────────

    public function test_une_compagnie_ne_voit_que_ses_classes_et_les_classes_communes(): void
    {
        $sienne = Compagnie::factory()->create();
        $autre  = Compagnie::factory()->create();

        $aMoi    = Classe::factory()->create(['compagnie_id' => $sienne->id, 'name' => 'VIP Elitis']);
        $commune = Classe::factory()->create(['compagnie_id' => null, 'is_default' => true, 'name' => 'Économique']);
        $aEux    = Classe::factory()->create(['compagnie_id' => $autre->id, 'name' => 'VIP Saramaya']);

        $this->actingAs($this->agentDe($sienne));

        $visibles = Classe::pluck('id');

        $this->assertContains($aMoi->id, $visibles);
        $this->assertContains($commune->id, $visibles);
        $this->assertNotContains($aEux->id, $visibles);
    }

    public function test_la_liste_des_classes_masque_celles_des_concurrents(): void
    {
        $sienne = Compagnie::factory()->create();
        $autre  = Compagnie::factory()->create();

        Classe::factory()->create(['compagnie_id' => $sienne->id, 'name' => 'VIP Elitis']);
        Classe::factory()->create(['compagnie_id' => $autre->id,  'name' => 'VIP Saramaya']);

        Livewire::actingAs($this->agentDe($sienne))
            ->test(ClasseManager::class)
            ->assertSee('VIP Elitis')
            ->assertDontSee('VIP Saramaya');
    }

    // ── Articles ────────────────────────────────────────────────────────────

    /**
     * Post assigne son auteur depuis l'utilisateur connecté (`creating`),
     * pas depuis les attributs : l'article doit donc être créé en session.
     */
    private function articleRedigePar(User $redacteur, string $titre): Post
    {
        return $this->actingAs($redacteur)->app->call(
            fn () => Post::create(['title' => $titre, 'content' => 'Contenu.'])
        );
    }

    public function test_un_agent_ne_peut_pas_ouvrir_larticle_dune_autre_compagnie(): void
    {
        $sienne = Compagnie::factory()->create();
        $autre  = Compagnie::factory()->create();

        $article = $this->articleRedigePar($this->agentDe($autre), 'Article confidentiel Saramaya');

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($this->agentDe($sienne))
            ->test(PostForm::class, ['postId' => $article->id]);
    }

    public function test_un_agent_ouvre_larticle_de_sa_propre_compagnie(): void
    {
        $redacteur = $this->agentDe(Compagnie::factory()->create());
        $article = $this->articleRedigePar($redacteur, 'Nouvelle desserte Elitis');

        $this->assertSame($redacteur->id, $article->user_id);

        Livewire::actingAs($redacteur)
            ->test(PostForm::class, ['postId' => $article->id])
            ->assertSet('title', 'Nouvelle desserte Elitis');
    }

    public function test_un_collegue_de_la_meme_compagnie_peut_editer_larticle(): void
    {
        $compagnie = Compagnie::factory()->create();
        $article = $this->articleRedigePar($this->agentDe($compagnie), 'Promotion de saison');

        Livewire::actingAs($this->agentDe($compagnie))
            ->test(PostForm::class, ['postId' => $article->id])
            ->assertSet('title', 'Promotion de saison');
    }
}
