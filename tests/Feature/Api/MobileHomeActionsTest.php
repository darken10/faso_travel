<?php

namespace Tests\Feature\Api;

use App\Enums\StatutTicket;
use App\Models\Compagnie\Gare;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Ville\Ville;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Endpoints alimentant les nouvelles actions rapides de l'accueil mobile :
 * l'annuaire des gares et l'historique de voyages.
 */
class MobileHomeActionsTest extends TestCase
{
    use RefreshDatabase;

    // ── Gares ───────────────────────────────────────────────────────────────

    public function test_lannuaire_des_gares_est_public(): void
    {
        $gare = Gare::factory()->create(['name' => 'Gare Routière de Ouagadougou']);

        $this->getJson('/api/v2/stations')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['name' => $gare->name]);
    }

    public function test_lannuaire_expose_les_coordonnees_de_la_gare(): void
    {
        Gare::factory()->create(['name' => 'Gare Test Coord', 'lat' => '12.3714', 'lng' => '-1.5197']);

        $station = collect($this->getJson('/api/v2/stations')->json('data.stations'))
            ->firstWhere('name', 'Gare Test Coord');

        $this->assertSame(12.3714, $station['lat']);
        $this->assertSame(-1.5197, $station['lng']);
        $this->assertTrue($station['has_coords']);
    }

    public function test_lannuaire_filtre_par_recherche(): void
    {
        Gare::factory()->create(['name' => 'Gare de Bobo-Dioulasso']);
        Gare::factory()->create(['name' => 'Gare de Koudougou']);

        $noms = collect($this->getJson('/api/v2/stations?search=Bobo')->json('data.stations'))
            ->pluck('name');

        $this->assertContains('Gare de Bobo-Dioulasso', $noms);
        $this->assertNotContains('Gare de Koudougou', $noms);
    }

    public function test_lannuaire_filtre_par_ville(): void
    {
        $ville = Ville::factory()->create();
        Gare::factory()->create(['name' => 'Gare Ciblée', 'ville_id' => $ville->id]);
        Gare::factory()->create(['name' => 'Gare Ailleurs']);

        $noms = collect($this->getJson("/api/v2/stations?ville_id={$ville->id}")->json('data.stations'))
            ->pluck('name');

        $this->assertContains('Gare Ciblée', $noms);
        $this->assertNotContains('Gare Ailleurs', $noms);
    }

    public function test_lannuaire_peut_exclure_les_gares_sans_coordonnees(): void
    {
        Gare::factory()->create(['name' => 'Gare Localisée', 'lat' => '12.37', 'lng' => '-1.52']);
        Gare::factory()->create(['name' => 'Gare Sans GPS', 'lat' => '0', 'lng' => '0']);

        $noms = collect($this->getJson('/api/v2/stations?with_coords=1')->json('data.stations'))
            ->pluck('name');

        $this->assertContains('Gare Localisée', $noms);
        $this->assertNotContains('Gare Sans GPS', $noms);
    }

    public function test_une_ville_sans_gare_nest_pas_proposee_en_filtre(): void
    {
        $villeSansGare = Ville::factory()->create();
        Gare::factory()->create();

        $villes = collect($this->getJson('/api/v2/stations')->json('data.cities'))->pluck('id');

        $this->assertNotContains($villeSansGare->id, $villes);
    }

    // ── Historique de voyages ───────────────────────────────────────────────

    public function test_lhistorique_exige_une_authentification(): void
    {
        $this->getJson('/api/v2/user/travel-history')->assertUnauthorized();
    }

    public function test_lhistorique_renvoie_les_voyages_du_client(): void
    {
        $user = User::factory()->create();
        $this->ticketPour($user, now()->subDays(10), StatutTicket::Valider);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/user/travel-history')
            ->assertOk();

        $this->assertSame(1, $response->json('count'));

        $trip = $response->json('trips.0');
        $this->assertNotNull($trip['ticket_number']);
        $this->assertNotNull($trip['departure']['city']);
        $this->assertNotNull($trip['arrival']['city']);
        $this->assertNotNull($trip['company']['name']);
        $this->assertTrue($trip['is_past']);
    }

    public function test_lhistorique_ne_montre_que_ses_propres_voyages(): void
    {
        $user  = User::factory()->create();
        $autre = User::factory()->create();

        $this->ticketPour($autre, now()->subDays(3), StatutTicket::Valider);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/user/travel-history')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_un_voyage_passe_et_valide_peut_etre_note(): void
    {
        $user = User::factory()->create();
        $this->ticketPour($user, now()->subDays(5), StatutTicket::Valider);

        $trip = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/user/travel-history')
            ->json('trips.0');

        $this->assertTrue($trip['can_rate']);
    }

    public function test_un_voyage_a_venir_nest_pas_notable(): void
    {
        $user = User::factory()->create();
        $this->ticketPour($user, now()->addDays(5), StatutTicket::Payer);

        $trip = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/user/travel-history')
            ->json('trips.0');

        $this->assertFalse($trip['is_past']);
        $this->assertFalse($trip['can_rate']);
    }

    public function test_un_voyage_annule_nest_pas_notable(): void
    {
        $user = User::factory()->create();
        $this->ticketPour($user, now()->subDays(5), StatutTicket::Annuler);

        $trip = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/user/travel-history')
            ->json('trips.0');

        $this->assertFalse($trip['can_rate']);
    }

    public function test_les_statistiques_ne_comptent_que_les_voyages_realises(): void
    {
        $user = User::factory()->create();
        $this->ticketPour($user, now()->subDays(5), StatutTicket::Valider);
        $this->ticketPour($user, now()->subDays(2), StatutTicket::Annuler);
        $this->ticketPour($user, now()->addDays(4), StatutTicket::Payer);

        $stats = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/user/travel-history')
            ->json('stats');

        $this->assertSame(3, $stats['total_trips']);
        $this->assertSame(1, $stats['completed_trips']);
        $this->assertGreaterThan(0, $stats['cities_visited']);
    }

    /** Crée un ticket rattaché à un voyage daté, pour le client donné. */
    private function ticketPour(User $user, \Carbon\CarbonInterface $date, StatutTicket $statut): Ticket
    {
        $voyage   = Voyage::factory()->create(['temps' => '04:00:00']);
        $instance = VoyageInstance::factory()->create([
            'voyage_id' => $voyage->id,
            'date'      => $date->toDateString(),
            'heure'     => $date->format('H:i:s'),
            'nb_place'  => 50,
        ]);

        return Ticket::factory()->create([
            'user_id'            => $user->id,
            'voyage_instance_id' => $instance->id,
            'voyage_id'          => $instance->voyage_id,
            'statut'             => $statut,
            'numero_chaise'      => 5,
        ]);
    }
}
