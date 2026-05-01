<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_payment_requires_authentication(): void
    {
        $this->postJson('/api/process-payment/orange')->assertStatus(401);
    }

    public function test_test_routes_no_longer_exist(): void
    {
        $this->get('/test')->assertStatus(404);
    }

    public function test_create_all_voyages_requires_authentication(): void
    {
        $this->get('/create-all-voyages-instances')->assertRedirect('/login');
    }

    public function test_compagnie_endpoint_requires_compagnie_association(): void
    {
        $user  = User::factory()->create(['compagnie_id' => null]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/ticket')
            ->assertStatus(403);
    }

    public function test_role_middleware_correctly_blocks_unauthorized_role(): void
    {
        $user  = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        // La route create-all-voyages-instances requiert le rôle admin
        $this->actingAs($user)->get('/create-all-voyages-instances')->assertStatus(403);
    }

    public function test_api_auth_routes_are_rate_limited(): void
    {
        for ($i = 0; $i < 11; $i++) {
            $response = $this->postJson('/api/v2/auth/login', [
                'email'    => 'test@test.com',
                'password' => 'wrong',
            ]);
        }

        $response->assertStatus(429);
    }
}
