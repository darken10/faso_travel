<?php

namespace Tests\Feature\Api\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le plafond global d'expiration de Sanctum a été désactivé. Les jetons émis avant
 * n'ont pas d'échéance propre : la migration doit leur en donner une, sinon ils
 * deviennent éternels.
 */
class LegacyTokenExpiryTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require database_path('migrations/2026_09_30_140000_expire_legacy_personal_access_tokens.php');
    }

    private function legacyToken(User $user, string $name, \DateTimeInterface $createdAt): int
    {
        return DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => User::class,
            'tokenable_id'   => $user->id,
            'name'           => $name,
            'token'          => hash('sha256', $name . $createdAt->format('c')),
            'abilities'      => '["*"]',
            'expires_at'     => null,
            'created_at'     => $createdAt,
            'updated_at'     => $createdAt,
        ]);
    }

    public function test_un_ancien_jeton_sans_echeance_expire_comme_avant(): void
    {
        $user = User::factory()->create();
        $ancien = $this->legacyToken($user, 'agent_mobile', now()->subDays(3));
        $recent = $this->legacyToken($user, 'recent', now()->subHours(2));

        $this->migration()->up();

        $expireAncien = DB::table('personal_access_tokens')->where('id', $ancien)->value('expires_at');
        $expireRecent = DB::table('personal_access_tokens')->where('id', $recent)->value('expires_at');

        $this->assertNotNull($expireAncien, 'un jeton sans échéance deviendrait éternel');
        $this->assertTrue(now()->greaterThan($expireAncien), 'créé il y a 3 jours : expiré, comme le plafond de 24 h le faisait');
        $this->assertTrue(now()->lessThan($expireRecent), 'créé il y a 2 h : encore valable jusqu\'à ses 24 h');
    }

    public function test_un_jeton_avec_echeance_propre_nest_pas_modifie(): void
    {
        $user = User::factory()->create();
        $echeance = now()->addDays(20)->startOfSecond();
        $id = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => User::class, 'tokenable_id' => $user->id, 'name' => 'refresh',
            'token' => hash('sha256', 'refresh'), 'abilities' => '["refresh"]',
            'expires_at' => $echeance, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->migration()->up();

        $this->assertEquals(
            $echeance->format('Y-m-d H:i:s'),
            \Carbon\Carbon::parse(DB::table('personal_access_tokens')->where('id', $id)->value('expires_at'))->format('Y-m-d H:i:s'),
        );
    }
}
