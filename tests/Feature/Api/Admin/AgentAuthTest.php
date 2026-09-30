<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Compagnie\Compagnie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\Auth\PersonalAccessToken;
use Tests\TestCase;

/**
 * Authentification de l'application agent.
 *
 * Deux contraintes de terrain gouvernent ce contrat :
 *   - un agent peut rester plusieurs jours sans réseau, donc revenir avec une
 *     file d'opérations et un jeton d'accès périmé ;
 *   - une compagnie peut équiper un même agent de plusieurs appareils.
 */
class AgentAuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'motdepasse-agent';

    private function agent(): User
    {
        return User::factory()->create([
            'compagnie_id' => Compagnie::factory()->create()->id,
            'password'     => Hash::make(self::PASSWORD),
        ]);
    }

    private function login(User $agent, ?string $deviceId = null): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/admin/auth/login', array_filter([
            'credential' => $agent->email,
            'password'   => self::PASSWORD,
            'device_id'  => $deviceId,
        ]));
    }

    // ── Login ──────────────────────────────────────────────────────────────

    public function test_le_login_renvoie_une_paire_de_jetons(): void
    {
        $response = $this->login($this->agent())->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonStructure(['token', 'refresh_token', 'expires_at', 'refresh_expires_at']);

        $this->assertNotSame($response->json('token'), $response->json('refresh_token'));
    }

    public function test_le_refresh_token_vit_trente_jours(): void
    {
        $response = $this->login($this->agent())->assertOk();

        $refresh = PersonalAccessToken::findToken($response->json('refresh_token'));

        // Le plafond global de Sanctum valait 24h et primait sur expires_at :
        // tout jeton longue durée mourait en réalité au bout d'un jour.
        $this->assertTrue($refresh->expires_at->greaterThan(now()->addDays(29)));
        $this->assertFalse($refresh->isExpired());
    }

    public function test_un_jeton_cree_sans_echeance_nest_pas_eternel(): void
    {
        // Filet de sécurité maintenant que le plafond global est désactivé.
        $token = $this->agent()->createToken('sans_echeance');

        $this->assertNotNull($token->accessToken->expires_at);
    }

    public function test_les_identifiants_incorrects_sont_refuses(): void
    {
        $agent = $this->agent();

        $this->postJson('/api/admin/auth/login', [
            'credential' => $agent->email,
            'password'   => 'mauvais',
        ])->assertUnauthorized();
    }

    public function test_un_utilisateur_sans_compagnie_ne_peut_pas_se_connecter(): void
    {
        $sansCompagnie = User::factory()->create([
            'compagnie_id' => null,
            'password'     => Hash::make(self::PASSWORD),
        ]);

        $this->login($sansCompagnie)->assertUnauthorized();
    }

    // ── Multi-appareils ────────────────────────────────────────────────────

    public function test_se_connecter_sur_un_second_appareil_ne_coupe_pas_le_premier(): void
    {
        $agent = $this->agent();

        $premier = $this->login($agent, 'telephone-a')->assertOk()->json('token');
        $this->login($agent, 'telephone-b')->assertOk();

        // L'ancien code faisait $user->tokens()->delete() : la file hors ligne du
        // premier appareil devenait insynchronisable sans nouvelle connexion.
        $this->withHeader('Authorization', "Bearer $premier")
            ->getJson('/api/admin/sync/pull')
            ->assertOk();
    }

    public function test_se_reconnecter_sur_le_meme_appareil_revoque_son_jeton_precedent(): void
    {
        $agent = $this->agent();

        $ancien = $this->login($agent, 'telephone-a')->assertOk()->json('token');
        $this->login($agent, 'telephone-a')->assertOk();

        $this->withHeader('Authorization', "Bearer $ancien")
            ->getJson('/api/admin/sync/pull')
            ->assertUnauthorized();
    }

    // ── Refresh ────────────────────────────────────────────────────────────

    public function test_le_refresh_renouvelle_la_paire_sans_mot_de_passe(): void
    {
        $agent   = $this->agent();
        $initial = $this->login($agent, 'telephone-a')->assertOk();

        $renouvele = $this->postJson('/api/admin/auth/refresh', [
            'refresh_token' => $initial->json('refresh_token'),
        ])->assertOk();

        $nouveau = $renouvele->json('token');
        $this->assertNotSame($initial->json('token'), $nouveau);

        $this->withHeader('Authorization', "Bearer $nouveau")
            ->getJson('/api/admin/sync/pull')
            ->assertOk();
    }

    public function test_le_refresh_token_est_tourne_a_chaque_usage(): void
    {
        $agent   = $this->agent();
        $initial = $this->login($agent, 'telephone-a')->assertOk();

        $this->postJson('/api/admin/auth/refresh', [
            'refresh_token' => $initial->json('refresh_token'),
        ])->assertOk();

        // Un refresh token rejoué ne doit plus rien valoir.
        $this->postJson('/api/admin/auth/refresh', [
            'refresh_token' => $initial->json('refresh_token'),
        ])->assertUnauthorized();
    }

    public function test_un_jeton_dacces_ne_peut_pas_servir_de_refresh(): void
    {
        $initial = $this->login($this->agent(), 'telephone-a')->assertOk();

        $this->postJson('/api/admin/auth/refresh', [
            'refresh_token' => $initial->json('token'),
        ])->assertUnauthorized();
    }

    public function test_un_refresh_token_inconnu_est_refuse(): void
    {
        $this->postJson('/api/admin/auth/refresh', ['refresh_token' => 'inexistant'])
            ->assertUnauthorized();
    }
}
