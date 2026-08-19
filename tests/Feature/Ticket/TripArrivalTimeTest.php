<?php

namespace Tests\Feature\Ticket;

use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'heure d'arrivée doit être le départ PLUS la durée.
 * Carbon 3 rend diffInMinutes() signé : l'ancien calcul retranchait la durée.
 */
class TripArrivalTimeTest extends TestCase
{
    use RefreshDatabase;

    private function instance(string $heure, string $temps, string $date = '+5 days'): VoyageInstance
    {
        $voyage = Voyage::factory()->create(['heure' => $heure, 'temps' => $temps]);

        return VoyageInstance::factory()->create([
            'voyage_id' => $voyage->id,
            'date'      => now()->parse($date)->toDateString(),
            'heure'     => $heure,
        ]);
    }

    public function test_trip_arrival_is_departure_plus_duration(): void
    {
        $instance = $this->instance('07:00:00', '04:00:00');

        $response = $this->getJson("/api/v2/trips/{$instance->id}");

        $response->assertOk();
        $departure = $response->json('departure.time');
        $arrival   = $response->json('arrival.time');

        $this->assertSame($instance->date->toDateString() . ' 07:00:00', $departure);
        $this->assertSame($instance->date->toDateString() . ' 11:00:00', $arrival);
        $this->assertGreaterThan($departure, $arrival, "L'arrivée ne doit jamais précéder le départ.");
    }

    public function test_trip_arrival_handles_duration_with_minutes(): void
    {
        $instance = $this->instance('08:45:00', '04:30:00');

        $arrival = $this->getJson("/api/v2/trips/{$instance->id}")->json('arrival.time');

        $this->assertSame($instance->date->toDateString() . ' 13:15:00', $arrival);
    }

    public function test_trip_arrival_rolls_over_to_next_day_for_night_trips(): void
    {
        $instance = $this->instance('22:00:00', '06:00:00');

        $arrival = $this->getJson("/api/v2/trips/{$instance->id}")->json('arrival.time');

        $this->assertSame($instance->date->copy()->addDay()->toDateString() . ' 04:00:00', $arrival);
    }

    public function test_trip_without_duration_falls_back_to_departure_time(): void
    {
        $instance = $this->instance('09:00:00', '04:00:00');
        $instance->voyage->update(['temps' => null]);

        $response = $this->getJson("/api/v2/trips/{$instance->id}");

        $this->assertSame(
            $response->json('departure.time'),
            $response->json('arrival.time'),
            'Sans durée connue, on ne doit pas inventer un décalage.'
        );
    }

    public function test_model_arrival_helper_matches_departure_plus_duration(): void
    {
        $instance = $this->instance('07:00:00', '04:00:00');

        $this->assertSame('11:00', $instance->getHeureArrive()->format('H:i'));
        $this->assertSame('07:00', $instance->getHeureDepart()->format('H:i'));
    }
}
