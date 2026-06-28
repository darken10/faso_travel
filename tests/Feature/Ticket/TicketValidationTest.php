<?php

namespace Tests\Feature\Ticket;

use App\Enums\StatutTicket;
use App\Enums\TypeTicket;
use App\Events\Admin\TicketValiderEvent;
use App\Events\Ticket\TicketActiveEvent;
use App\Events\Ticket\TicketBlockerEvent;
use App\Events\Ticket\TicketPauseEvent;
use App\Helper\TicketValidation;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TicketValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // On teste les transitions d'état du helper, pas les effets de bord
        // (emails/notifications/broadcast déclenchés par ces events).
        Event::fake([
            TicketValiderEvent::class,
            TicketActiveEvent::class,
            TicketPauseEvent::class,
            TicketBlockerEvent::class,
        ]);
    }

    private function loginAgent(): User
    {
        // L'agent doit appartenir à une compagnie : le hook creating de Voyage
        // rattache les voyages créés à compagnie_id de l'utilisateur authentifié.
        $compagnie = \App\Models\Compagnie\Compagnie::factory()->create();
        $agent = User::factory()->create(['compagnie_id' => $compagnie->id]);
        Auth::login($agent);
        return $agent;
    }

    public function test_simple_ticket_is_validated_correctly(): void
    {
        $this->loginAgent();
        $ticket = Ticket::factory()->create([
            'statut' => StatutTicket::Payer,
            'type'   => TypeTicket::AllerSimple,
        ]);

        $result = TicketValidation::valider($ticket);

        $this->assertTrue($result);
        $this->assertEquals(StatutTicket::Valider, $ticket->fresh()->statut);
    }

    public function test_aller_retour_ticket_switches_to_retour_simle_on_first_validation(): void
    {
        $this->loginAgent();
        $ticket = Ticket::factory()->create([
            'statut' => StatutTicket::Payer,
            'type'   => TypeTicket::AllerRetour,
        ]);

        $result = TicketValidation::valider($ticket);

        // Première validation : aller validé → passe en Pause avec type RetourSimple
        $this->assertTrue($result);
        $fresh = $ticket->fresh();
        $this->assertEquals(StatutTicket::Pause, $fresh->statut);
        $this->assertEquals(TypeTicket::RetourSimple, $fresh->type);
    }

    public function test_active_method_sets_ticket_to_payer(): void
    {
        $ticket = Ticket::factory()->create(['statut' => StatutTicket::Pause]);

        $result = TicketValidation::active($ticket);

        $this->assertTrue($result);
        $this->assertEquals(StatutTicket::Payer, $ticket->fresh()->statut);
    }

    public function test_pause_method_sets_ticket_to_pause(): void
    {
        $ticket = Ticket::factory()->create(['statut' => StatutTicket::Payer]);

        $result = TicketValidation::pause($ticket);

        $this->assertTrue($result);
        $this->assertEquals(StatutTicket::Pause, $ticket->fresh()->statut);
    }

    public function test_bloque_method_sets_ticket_to_bloquer(): void
    {
        $ticket = Ticket::factory()->create(['statut' => StatutTicket::Payer]);

        $result = TicketValidation::bloque($ticket);

        $this->assertTrue($result);
        $this->assertEquals(StatutTicket::Bloquer, $ticket->fresh()->statut);
    }
}
