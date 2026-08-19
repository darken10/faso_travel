<?php

namespace Tests\Feature\Ticket;

use App\Enums\StatutTicket;
use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\Trajet;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Réactivation d'un ticket en pause : PATCH /api/v2/tickets/{id}/activate
 * et listing des voyages équivalents : GET /api/v2/tickets/{id}/equivalent-trips
 */
class TicketActivationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: string} */
    private function authenticatedUser(): array
    {
        // compagnie_id à null : un client, pas un agent de compagnie.
        $user  = User::factory()->create(['compagnie_id' => null]);
        $token = $user->createToken('test')->plainTextToken;

        return [$user, $token];
    }

    private function instanceOn(Voyage $voyage, string $date = '+7 days', string $heure = '08:00:00'): VoyageInstance
    {
        return VoyageInstance::factory()->create([
            'voyage_id' => $voyage->id,
            'date'      => now()->parse($date)->toDateString(),
            'heure'     => $heure,
            'nb_place'  => 50,
        ]);
    }

    /** Deux instances sur le même trajet et la même compagnie : le cas nominal. */
    private function equivalentPair(): array
    {
        $voyage = Voyage::factory()->create();

        return [$this->instanceOn($voyage, '+3 days'), $this->instanceOn($voyage, '+10 days')];
    }

    public function test_paused_ticket_is_reactivated_on_an_equivalent_trip(): void
    {
        [$user, $token] = $this->authenticatedUser();
        [$origin, $target] = $this->equivalentPair();

        $ticket = Ticket::factory()->create([
            'user_id'            => $user->id,
            'voyage_instance_id' => $origin->id,
            'voyage_id'          => $origin->voyage_id,
            'statut'             => StatutTicket::Pause,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patchJson("/api/v2/tickets/{$ticket->id}/activate", [
                'voyage_instance_id' => $target->id,
                'numero_chaise'      => 12,
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $fresh = $ticket->fresh();
        $this->assertEquals(StatutTicket::Payer, $fresh->statut);
        $this->assertEquals($target->id, $fresh->voyage_instance_id);
        $this->assertEquals($target->voyage_id, $fresh->voyage_id);
        $this->assertEquals(12, $fresh->numero_chaise);
    }

    public function test_ticket_that_is_not_paused_cannot_be_reactivated(): void
    {
        [$user, $token] = $this->authenticatedUser();
        [$origin, $target] = $this->equivalentPair();

        $ticket = Ticket::factory()->create([
            'user_id'            => $user->id,
            'voyage_instance_id' => $origin->id,
            'statut'             => StatutTicket::Payer,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patchJson("/api/v2/tickets/{$ticket->id}/activate", [
                'voyage_instance_id' => $target->id,
                'numero_chaise'      => 5,
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertEquals($origin->id, $ticket->fresh()->voyage_instance_id);
    }

    public function test_ticket_cannot_be_moved_to_a_trip_from_another_route(): void
    {
        [$user, $token] = $this->authenticatedUser();
        [$origin] = $this->equivalentPair();

        // Autre trajet, autre compagnie : le ticket ne doit pas pouvoir y atterrir.
        $foreign = $this->instanceOn(Voyage::factory()->create([
            'trajet_id'    => Trajet::factory(),
            'compagnie_id' => Compagnie::factory(),
        ]));

        $ticket = Ticket::factory()->create([
            'user_id'            => $user->id,
            'voyage_instance_id' => $origin->id,
            'statut'             => StatutTicket::Pause,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patchJson("/api/v2/tickets/{$ticket->id}/activate", [
                'voyage_instance_id' => $foreign->id,
                'numero_chaise'      => 3,
            ])
            ->assertStatus(422);

        $fresh = $ticket->fresh();
        $this->assertEquals($origin->id, $fresh->voyage_instance_id);
        $this->assertEquals(StatutTicket::Pause, $fresh->statut);
    }

    public function test_reactivation_is_rejected_when_the_seat_is_already_taken(): void
    {
        [$user, $token] = $this->authenticatedUser();
        [$origin, $target] = $this->equivalentPair();

        Ticket::factory()->create([
            'voyage_instance_id' => $target->id,
            'numero_chaise'      => 7,
            'statut'             => StatutTicket::Payer,
        ]);

        $ticket = Ticket::factory()->create([
            'user_id'            => $user->id,
            'voyage_instance_id' => $origin->id,
            'statut'             => StatutTicket::Pause,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patchJson("/api/v2/tickets/{$ticket->id}/activate", [
                'voyage_instance_id' => $target->id,
                'numero_chaise'      => 7,
            ])
            ->assertStatus(422);

        $this->assertEquals($origin->id, $ticket->fresh()->voyage_instance_id);
    }

    public function test_reactivation_is_rejected_on_a_trip_that_already_departed(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $voyage = Voyage::factory()->create();
        $origin = $this->instanceOn($voyage, '+3 days');
        $past   = $this->instanceOn($voyage, '-2 days');

        $ticket = Ticket::factory()->create([
            'user_id'            => $user->id,
            'voyage_instance_id' => $origin->id,
            'statut'             => StatutTicket::Pause,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patchJson("/api/v2/tickets/{$ticket->id}/activate", [
                'voyage_instance_id' => $past->id,
                'numero_chaise'      => 4,
            ])
            ->assertStatus(422);

        $this->assertEquals(StatutTicket::Pause, $ticket->fresh()->statut);
    }

    public function test_seat_number_beyond_capacity_is_rejected(): void
    {
        [$user, $token] = $this->authenticatedUser();
        [$origin, $target] = $this->equivalentPair();

        $ticket = Ticket::factory()->create([
            'user_id'            => $user->id,
            'voyage_instance_id' => $origin->id,
            'statut'             => StatutTicket::Pause,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patchJson("/api/v2/tickets/{$ticket->id}/activate", [
                'voyage_instance_id' => $target->id,
                'numero_chaise'      => 999, // nb_place = 50
            ])
            ->assertStatus(422);
    }

    public function test_equivalent_trips_lists_only_future_trips_on_the_same_route_and_company(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $voyage = Voyage::factory()->create();
        $origin = $this->instanceOn($voyage, '+3 days');
        $future = $this->instanceOn($voyage, '+10 days');
        $this->instanceOn($voyage, '-5 days');                       // passé → exclu
        $this->instanceOn(Voyage::factory()->create());              // autre trajet → exclu

        $ticket = Ticket::factory()->create([
            'user_id'            => $user->id,
            'voyage_instance_id' => $origin->id,
            'statut'             => StatutTicket::Pause,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson("/api/v2/tickets/{$ticket->id}/equivalent-trips");

        $response->assertOk();
        $ids = collect($response->json())->pluck('id')->map(fn ($id) => (string) $id);

        $this->assertEquals([(string) $future->id], $ids->all());
    }

    public function test_equivalent_trips_is_rejected_for_a_ticket_that_is_not_paused(): void
    {
        [$user, $token] = $this->authenticatedUser();
        [$origin] = $this->equivalentPair();

        $ticket = Ticket::factory()->create([
            'user_id'            => $user->id,
            'voyage_instance_id' => $origin->id,
            'statut'             => StatutTicket::Payer,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson("/api/v2/tickets/{$ticket->id}/equivalent-trips")
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_a_user_cannot_reactivate_someone_else_ticket(): void
    {
        [, $token] = $this->authenticatedUser();
        [$origin, $target] = $this->equivalentPair();

        $ticket = Ticket::factory()->create([
            'voyage_instance_id' => $origin->id,
            'statut'             => StatutTicket::Pause,
        ]);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->patchJson("/api/v2/tickets/{$ticket->id}/activate", [
                'voyage_instance_id' => $target->id,
                'numero_chaise'      => 9,
            ])
            ->assertStatus(404);
    }
}
