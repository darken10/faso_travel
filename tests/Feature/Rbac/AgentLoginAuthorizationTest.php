<?php

namespace Tests\Feature\Rbac;

use App\Models\Auth\PersonalAccessToken;
use App\Models\Compagnie\Compagnie;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Habilitation de l'application agent.
 *
 * Le login acceptait tout compte rattaché à une compagnie : un comptable ou un chargé de
 * communication obtenait donc un jeton de quai et pouvait valider des tickets.
 */
class AgentLoginAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'motdepasse-agent';

    public function test_un_comptable_ne_peut_pas_se_connecter_a_l_application_agent(): void
    {
        config(['rbac.enforce' => true]);

        $this->login($this->compte('comptable'))
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_un_agent_d_embarquement_obtient_un_jeton(): void
    {
        config(['rbac.enforce' => true]);

        $this->login($this->compte('agent_embarquement', ['embarquement.app.login']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'refresh_token', 'expires_at']);
    }

    public function test_le_jeton_ne_porte_que_les_abilites_d_embarquement(): void
    {
        config(['rbac.enforce' => true]);

        $agent = $this->compte('agent_embarquement', [
            'embarquement.app.login',
            'embarquement.ticket.validate',
            'finance.bilan.view',
        ]);

        $this->login($agent)->assertOk();

        $jeton = PersonalAccessToken::where('tokenable_id', $agent->id)
            ->where('name', 'like', 'agent_access:%')
            ->sole();

        // Un jeton ['*'] autorise tout ce que l'API expose : si le téléphone est perdu,
        // mieux vaut qu'il ne porte que les opérations de quai.
        $this->assertContains('embarquement.app.login', $jeton->abilities);
        $this->assertContains('embarquement.ticket.validate', $jeton->abilities);
        $this->assertNotContains('finance.bilan.view', $jeton->abilities);
        $this->assertNotContains('*', $jeton->abilities);
    }

    public function test_en_mode_observation_un_compte_sans_role_se_connecte_encore(): void
    {
        config(['rbac.enforce' => false]);

        $this->login($this->compte('comptable'))
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_un_compte_sans_abilite_d_embarquement_garde_un_jeton_complet(): void
    {
        config(['rbac.enforce' => false]);
        $compte = $this->compte('comptable');

        $this->login($compte)->assertOk();

        $jeton = PersonalAccessToken::where('tokenable_id', $compte->id)
            ->where('name', 'like', 'agent_access:%')
            ->sole();

        // Sans abilité, Sanctum refuserait chaque appel protégé : un compte encore sans
        // rôle doit continuer à fonctionner pendant la période d'observation.
        $this->assertSame(['*'], $jeton->abilities);
    }

    public function test_le_renouvellement_reverifie_l_habilitation(): void
    {
        config(['rbac.enforce' => false]);
        $agent = $this->compte('agent_embarquement', ['embarquement.app.login']);

        $refresh = $this->login($agent)->json('refresh_token');

        // L'agent est muté au guichet : son renouvellement ne doit plus passer.
        config(['rbac.enforce' => true]);
        $agent->roles()->detach();
        $agent->invaliderCachePermissions();

        $this->postJson('/api/admin/auth/refresh', ['refresh_token' => $refresh])
            ->assertStatus(403);
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    /**
     * @param  list<string>  $permissions
     */
    private function compte(string $nomRole, array $permissions = []): User
    {
        $user = User::factory()->create([
            'compagnie_id' => Compagnie::factory()->create()->id,
            'password' => Hash::make(self::PASSWORD),
        ]);

        $role = Role::firstOrCreate(
            ['name' => $nomRole, 'compagnie_id' => null],
            ['label' => $nomRole, 'scope' => 'compagnie', 'is_system' => true]
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

    private function login(User $agent): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/admin/auth/login', [
            'credential' => $agent->email,
            'password' => self::PASSWORD,
        ]);
    }
}
