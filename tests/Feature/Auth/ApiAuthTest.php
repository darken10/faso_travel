<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_via_api(): void
    {
        $response = $this->postJson('/api/v2/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@liptra.net',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['success', 'user', 'token']);

        $this->assertDatabaseHas('users', ['email' => 'test@liptra.net']);
    }

    public function test_user_can_login_via_api(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/v2/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()->assertJsonStructure(['success', 'user', 'token']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct_password')]);

        $response = $this->postJson('/api/v2/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
                         ->postJson('/api/v2/auth/logout');

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/v2/user/profile')->assertStatus(401);
    }

    public function test_registration_requires_valid_email(): void
    {
        $this->postJson('/api/v2/auth/register', [
            'name'                  => 'Test',
            'email'                 => 'not-an-email',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $this->postJson('/api/v2/auth/register', [
            'name'                  => 'Test',
            'email'                 => 'test@liptra.net',
            'password'              => 'password123',
            'password_confirmation' => 'different',
        ])->assertStatus(422);
    }

    public function test_otp_send_stores_in_cache_with_user_id_key(): void
    {
        $user = User::factory()->create(['email' => 'otp@liptra.net']);

        $this->postJson('/api/v2/auth/send-otp', ['email' => 'otp@liptra.net'])
             ->assertOk();

        $this->assertNotNull(Cache::get('otp_user_' . $user->id));
    }

    public function test_otp_verify_succeeds_with_correct_code(): void
    {
        $user = User::factory()->create(['email' => 'otp@liptra.net']);
        Cache::put('otp_user_' . $user->id, '123456', now()->addMinutes(10));

        $this->postJson('/api/v2/auth/verify-otp', [
            'email' => 'otp@liptra.net',
            'otp'   => '123456',
        ])->assertOk()->assertJson(['verified' => true]);
    }

    public function test_otp_verify_fails_with_wrong_code(): void
    {
        $user = User::factory()->create(['email' => 'otp@liptra.net']);
        Cache::put('otp_user_' . $user->id, '123456', now()->addMinutes(10));

        $this->postJson('/api/v2/auth/verify-otp', [
            'email' => 'otp@liptra.net',
            'otp'   => '999999',
        ])->assertOk()->assertJson(['verified' => false]);
    }
}
