<?php

namespace Tests\Feature\Rbac;

use App\Enums\UserRole;
use App\Http\Middleware\AuthorizeOrObserve;
use App\Models\Compagnie\Compagnie;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ObservationModeTest extends TestCase
{
    use RefreshDatabase;

    private string $dossierLogs;

    protected function setUp(): void
    {
        parent::setUp();

        // Le canal pointe vers un dossier temporaire : les tests n'écrivent pas dans le
        // vrai journal de l'application.
        $this->dossierLogs = storage_path('framework/testing/rbac-'.uniqid());
        File::ensureDirectoryExists($this->dossierLogs);
        config(['logging.channels.rbac.path' => $this->dossierLogs.'/rbac.log']);

        Route::middleware(['web', 'can.rbac:finance.bilan.view'])
            ->get('/_test/bilan', fn () => response('bilan'));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dossierLogs);
        parent::tearDown();
    }

    public function test_en_mode_observation_un_refus_passe_et_est_journalise(): void
    {
        config(['rbac.enforce' => false]);
        $agent = $this->agentSansPermission();

        $this->actingAs($agent)->get('/_test/bilan')->assertOk()->assertSee('bilan');

        $refus = $this->refusJournalises();

        $this->assertCount(1, $refus);
        $this->assertSame('finance.bilan.view', $refus[0]['permission']);
        $this->assertSame($agent->id, $refus[0]['user_id']);
        $this->assertSame(['guichetier'], $refus[0]['roles']);
        $this->assertSame('GET', $refus[0]['method']);
    }

    public function test_en_mode_blocage_un_refus_renvoie_403(): void
    {
        config(['rbac.enforce' => true]);

        $this->actingAs($this->agentSansPermission())
            ->get('/_test/bilan')
            ->assertForbidden();

        $this->assertSame([], $this->refusJournalises());
    }

    public function test_un_utilisateur_autorise_passe_dans_les_deux_modes(): void
    {
        $comptable = $this->agentAvecPermission();

        foreach ([false, true] as $enforce) {
            config(['rbac.enforce' => $enforce]);

            $this->actingAs($comptable)->get('/_test/bilan')->assertOk();
        }

        $this->assertSame([], $this->refusJournalises());
    }

    public function test_un_compte_root_passe_sans_permission_en_base(): void
    {
        config(['rbac.enforce' => true]);
        $root = User::factory()->create(['role' => UserRole::Root, 'compagnie_id' => null]);

        $this->actingAs($root)->get('/_test/bilan')->assertOk();
    }

    public function test_un_visiteur_anonyme_est_refuse_en_mode_blocage(): void
    {
        config(['rbac.enforce' => true]);

        // Le middleware échoue fermé. En pratique une garde d'authentification redirige
        // bien avant, mais une route mal configurée ne doit pas devenir une porte ouverte.
        $this->get('/_test/bilan')->assertForbidden();
    }

    public function test_le_rapport_agrege_les_refus_par_permission_et_par_role(): void
    {
        config(['rbac.enforce' => false]);

        $guichetier = $this->agentSansPermission();
        $autre = $this->agentSansPermission();

        $this->actingAs($guichetier)->get('/_test/bilan');
        $this->actingAs($guichetier)->get('/_test/bilan');
        $this->actingAs($autre)->get('/_test/bilan');

        $this->artisan('rbac:refusals', ['--days' => 1])
            ->expectsOutputToContain('3 refus observé(s)')
            ->expectsOutputToContain('finance.bilan.view')
            ->assertSuccessful();
    }

    public function test_le_rapport_signale_l_absence_de_journal(): void
    {
        $this->artisan('rbac:refusals')
            ->expectsOutputToContain('Aucun journal RBAC')
            ->assertSuccessful();
    }

    public function test_le_rapport_ignore_les_lignes_illisibles(): void
    {
        config(['rbac.enforce' => false]);
        $this->actingAs($this->agentSansPermission())->get('/_test/bilan');

        $fichier = $this->fichierDuJour();
        File::append($fichier, '[2026-10-03 10:00:00] testing.INFO: '.AuthorizeOrObserve::MARQUEUR." pas du json\n");

        $this->artisan('rbac:refusals', ['--days' => 1])
            ->expectsOutputToContain('1 ligne(s) de journal illisible(s)')
            ->assertSuccessful();
    }

    public function test_le_rapport_filtre_sur_une_permission(): void
    {
        config(['rbac.enforce' => false]);
        $this->actingAs($this->agentSansPermission())->get('/_test/bilan');

        $this->artisan('rbac:refusals', ['--days' => 1, '--permission' => 'autre.chose.view'])
            ->expectsOutputToContain('Aucun refus observé')
            ->assertSuccessful();
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    private function agentSansPermission(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'compagnie_id' => Compagnie::factory()->create()->id,
        ]);

        $user->roles()->syncWithoutDetaching([$this->role('guichetier')->id]);
        $user->invaliderCachePermissions();

        return $user;
    }

    private function agentAvecPermission(): User
    {
        $user = User::factory()->create([
            'role' => UserRole::User,
            'compagnie_id' => Compagnie::factory()->create()->id,
        ]);

        $role = $this->role('comptable');
        $role->permissions()->syncWithoutDetaching([
            Permission::firstOrCreate(
                ['name' => 'finance.bilan.view'],
                ['domaine' => 'finance', 'label' => 'Bilan', 'scope' => 'compagnie']
            )->id => ['portee' => 'compagnie'],
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);
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

    private function fichierDuJour(): string
    {
        return $this->dossierLogs.'/rbac-'.now()->format('Y-m-d').'.log';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function refusJournalises(): array
    {
        $fichier = $this->fichierDuJour();

        if (! File::exists($fichier)) {
            return [];
        }

        $refus = [];

        foreach (preg_split('/\R/', File::get($fichier)) ?: [] as $ligne) {
            if (! str_contains($ligne, AuthorizeOrObserve::MARQUEUR)) {
                continue;
            }

            $debut = strpos($ligne, '{', strpos($ligne, AuthorizeOrObserve::MARQUEUR));
            $contexte = $debut === false ? null : json_decode(substr($ligne, $debut), true);

            if (is_array($contexte) && isset($contexte['permission'])) {
                $refus[] = $contexte;
            }
        }

        return $refus;
    }
}
