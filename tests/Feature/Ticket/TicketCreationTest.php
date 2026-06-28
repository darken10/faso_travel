<?php

namespace Tests\Feature\Ticket;

use App\Enums\StatutTicket;
use App\Enums\StatutVoyageInstance;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCreationTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): array
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $token];
    }

    public function test_user_can_create_ticket_for_available_voyage(): void
    {
        [$user, $token] = $this->authenticatedUser();
        $voyageInstance = VoyageInstance::factory()->create(['statut' => StatutVoyageInstance::DISPONIBLE]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/v2/tickets', [
                'voyage_instance_id' => $voyageInstance->id,
                'type'               => 'one-way',
                'is_for_self'        => true,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tickets', [
            'user_id'            => $user->id,
            'voyage_instance_id' => $voyageInstance->id,
            'statut'             => StatutTicket::Payer->value,
        ]);
    }

    public function test_duplicate_pending_ticket_returns_existing_one(): void
    {
        [$user, $token] = $this->authenticatedUser();
        $voyageInstance = VoyageInstance::factory()->create();

        $headers = ['Authorization' => 'Bearer ' . $token];
        $payload = ['voyage_instance_id' => $voyageInstance->id, 'type' => 'one-way', 'is_for_self' => true];

        // 1re création → 201 ; 2e tentative identique → 200 (ticket existant renvoyé, pas de doublon)
        $this->withHeaders($headers)->postJson('/api/v2/tickets', $payload)->assertStatus(201);
        $this->withHeaders($headers)->postJson('/api/v2/tickets', $payload)->assertStatus(200);

        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_unauthenticated_user_cannot_create_ticket(): void
    {
        $voyageInstance = VoyageInstance::factory()->create();

        $this->postJson('/api/v2/tickets', [
            'voyage_instance_id' => $voyageInstance->id,
        ])->assertStatus(401);
    }

    public function test_ticket_creation_requires_voyage_instance_id(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/v2/tickets', ['type' => 'one-way'])
            ->assertStatus(422);
    }

    public function test_user_can_cancel_pending_ticket(): void
    {
        [$user, $token] = $this->authenticatedUser();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'statut'  => StatutTicket::EnAttente,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patchJson("/api/v2/tickets/{$ticket->id}/cancel")
            ->assertOk();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'statut' => StatutTicket::Annuler->value]);
    }

    public function test_validated_ticket_cannot_be_cancelled(): void
    {
        [$user, $token] = $this->authenticatedUser();
        $ticket = Ticket::factory()->create([
            'user_id' => $user->id,
            'statut'  => StatutTicket::Valider,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patchJson("/api/v2/tickets/{$ticket->id}/cancel")
            ->assertStatus(400);
    }

    public function test_user_can_get_own_tickets(): void
    {
        [$user, $token] = $this->authenticatedUser();
        Ticket::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/v2/tickets');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));
    }
}
