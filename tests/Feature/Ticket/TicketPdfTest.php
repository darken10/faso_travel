<?php

namespace Tests\Feature\Ticket;

use App\Enums\StatutTicket;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Services\Ticket\PdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le billet doit tenir sur une seule page, quelle que soit la longueur des
 * libellés : un débordement produit une seconde page vide en pièce jointe.
 */
class TicketPdfTest extends TestCase
{
    use RefreshDatabase;

    private function paidTicket(array $attributes = []): Ticket
    {
        $voyage   = Voyage::factory()->create(['temps' => '04:00:00']);
        $instance = VoyageInstance::factory()->create([
            'voyage_id' => $voyage->id,
            'date'      => now()->addDays(5)->toDateString(),
            'heure'     => '07:00:00',
            'nb_place'  => 50,
        ]);

        return Ticket::factory()->create(array_merge([
            'voyage_instance_id' => $instance->id,
            'voyage_id'          => $instance->voyage_id,
            'statut'             => StatutTicket::Payer,
            'numero_chaise'      => 12,
        ], $attributes));
    }

    private function pageCount(string $pdf): int
    {
        preg_match_all('/\/Type\s*\/Page[^s]/', $pdf, $matches);

        return count($matches[0]);
    }

    public function test_ticket_pdf_is_generated_on_a_single_page(): void
    {
        $pdf = app(PdfService::class)->output($this->paidTicket());

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(1, $this->pageCount($pdf), 'Le billet doit tenir sur une seule page.');
    }

    public function test_ticket_pdf_uses_the_boarding_pass_paper_size(): void
    {
        $pdf = app(PdfService::class)->output($this->paidTicket());

        preg_match('/MediaBox\s*\[([^\]]+)\]/', $pdf, $box);
        [, , $width, $height] = preg_split('/\s+/', trim($box[1]));

        // 240 mm × 101 mm — et surtout pas un A4 paysage (842 × 595 pt).
        $this->assertEqualsWithDelta(680.31, (float) $width, 0.5);
        $this->assertEqualsWithDelta(286.30, (float) $height, 0.5);
    }

    public function test_ticket_pdf_still_fits_one_page_with_very_long_labels(): void
    {
        $ticket = $this->paidTicket();
        $ticket->user->update(['name' => 'Jean-Baptiste Ouedraogo Sawadogo Compaore']);

        $pdf = app(PdfService::class)->output($ticket->fresh());

        $this->assertSame(1, $this->pageCount($pdf));
    }

    public function test_ticket_pdf_is_generated_when_the_trip_has_no_duration(): void
    {
        $ticket = $this->paidTicket();
        $ticket->voyageInstance->voyage->update(['temps' => null]);

        $pdf = app(PdfService::class)->output($ticket->fresh());

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(1, $this->pageCount($pdf));
    }
}
