<?php

namespace Tests\Feature\Ticket;

use App\Enums\StatutTicket;
use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Services\Report\ReportService;
use App\Mail\RapportMail;
use App\Services\Ticket\TicketCommandService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les rapports d'activité recensent les embarquements et les billets basculés
 * en pause par la tâche planifiée.
 *
 * Ces deux sections sont datées par l'événement (validation, mise en pause) et
 * non par la date de vente : un billet vendu lundi peut n'embarquer que jeudi.
 */
class RapportActiviteTest extends TestCase
{
    use RefreshDatabase;

    private Compagnie $compagnie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compagnie = Compagnie::factory()->create();
    }

    private function billet(StatutTicket $statut = StatutTicket::Payer, ?Compagnie $compagnie = null): Ticket
    {
        $voyage = Voyage::factory()->create([
            'compagnie_id' => ($compagnie ?? $this->compagnie)->id,
            'temps'        => '04:00:00',
        ]);
        $instance = VoyageInstance::factory()->create([
            'voyage_id' => $voyage->id,
            'date'      => now()->subDay()->toDateString(),
            'heure'     => '08:00:00',
            'nb_place'  => 50,
        ]);

        return Ticket::factory()->create([
            'voyage_instance_id' => $instance->id,
            'voyage_id'          => $instance->voyage_id,
            'statut'             => $statut,
        ]);
    }

    private function rapportDuJour(): array
    {
        return app(ReportService::class)->data(
            $this->compagnie->id,
            now()->copy()->startOfDay(),
            now()->copy()->endOfDay(),
        );
    }

    // ── Billets mis en pause automatiquement ────────────────────────────────

    public function test_une_pause_automatique_apparait_dans_le_rapport(): void
    {
        $billet = $this->billet();
        app(TicketCommandService::class)->pause($billet, automatique: true);

        $data = $this->rapportDuJour();

        $this->assertSame(1, $data['pausesAutoCount']);
        $this->assertSame($billet->numero_ticket, $data['pausesAuto'][0]['numero']);
    }

    public function test_une_pause_manuelle_nest_pas_comptee_comme_automatique(): void
    {
        $billet = $this->billet();
        app(TicketCommandService::class)->pause($billet);

        $this->assertSame(0, $this->rapportDuJour()['pausesAutoCount']);
        $this->assertFalse($billet->fresh()->paused_auto);
        $this->assertNotNull($billet->fresh()->paused_at);
    }

    public function test_une_pause_hors_periode_est_exclue(): void
    {
        $billet = $this->billet();
        app(TicketCommandService::class)->pause($billet, automatique: true);
        $billet->update(['paused_at' => Carbon::now()->subMonth()]);

        $this->assertSame(0, $this->rapportDuJour()['pausesAutoCount']);
    }

    public function test_le_montant_immobilise_est_totalise(): void
    {
        foreach ([1, 2] as $_) {
            app(TicketCommandService::class)->pause($this->billet(), automatique: true);
        }

        $data = $this->rapportDuJour();

        $this->assertSame(2, $data['pausesAutoCount']);
        $this->assertIsInt($data['pausesAutoMontant']);
    }

    public function test_les_pauses_dune_autre_compagnie_sont_exclues(): void
    {
        $autre = Compagnie::factory()->create();
        app(TicketCommandService::class)->pause($this->billet(compagnie: $autre), automatique: true);

        $this->assertSame(0, $this->rapportDuJour()['pausesAutoCount']);
    }

    // ── Billets embarqués ───────────────────────────────────────────────────

    public function test_un_embarquement_apparait_dans_le_rapport(): void
    {
        $agent = User::factory()->create(['compagnie_id' => $this->compagnie->id]);
        $billet = $this->billet(StatutTicket::Valider);
        $billet->update(['valider_at' => now(), 'valider_by_id' => $agent->id]);

        $data = $this->rapportDuJour();

        $this->assertSame(1, $data['embarquesCount']);
        $this->assertSame($billet->numero_ticket, $data['embarques'][0]['numero']);
        $this->assertNotSame('—', $data['embarques'][0]['valide_par']);
    }

    public function test_un_billet_paye_non_scanne_nest_pas_compte_comme_embarque(): void
    {
        $this->billet(StatutTicket::Payer);

        $this->assertSame(0, $this->rapportDuJour()['embarquesCount']);
    }

    public function test_un_embarquement_hors_periode_est_exclu(): void
    {
        $billet = $this->billet(StatutTicket::Valider);
        $billet->update(['valider_at' => now()->subWeeks(2)]);

        $this->assertSame(0, $this->rapportDuJour()['embarquesCount']);
    }

    public function test_un_embarquement_est_date_par_la_validation_pas_par_la_vente(): void
    {
        $billet = $this->billet(StatutTicket::Valider);
        // Vendu il y a un mois, embarqué aujourd'hui.
        $billet->update(['created_at' => now()->subMonth(), 'valider_at' => now()]);

        $data = $this->rapportDuJour();

        $this->assertSame(1, $data['embarquesCount'], "L'embarquement doit compter à sa date de validation.");
    }

    // ── Période annuelle ────────────────────────────────────────────────────

    public function test_la_commande_accepte_la_periode_annuelle(): void
    {
        $this->artisan('reports:send yearly')->assertExitCode(0);
    }

    public function test_les_quatre_periodes_sont_acceptees(): void
    {
        foreach (['daily', 'weekly', 'monthly', 'yearly'] as $periode) {
            $this->artisan("reports:send {$periode}")->assertExitCode(0);
        }
    }

    // ── Rendu du document ───────────────────────────────────────────────────

    public function test_le_pdf_se_genere_avec_les_nouvelles_sections(): void
    {
        $agent = User::factory()->create(['compagnie_id' => $this->compagnie->id]);

        $embarque = $this->billet(StatutTicket::Valider);
        $embarque->update(['valider_at' => now(), 'valider_by_id' => $agent->id]);

        app(TicketCommandService::class)->pause($this->billet(), automatique: true);

        // SendReports enveloppe la génération dans un try/catch : une erreur de
        // template y passerait inaperçue. On rend donc la vue directement.
        $pdf = Pdf::loadView('exports.rapport', [
            'data'      => $this->rapportDuJour(),
            'compagnie' => $this->compagnie,
        ])->output();

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_lemail_de_rapport_se_rend_sans_erreur(): void
    {
        app(TicketCommandService::class)->pause($this->billet(), automatique: true);

        $mail = new RapportMail($this->compagnie, $this->rapportDuJour(), 'Journalier — test', 'pdf');

        $this->assertNotEmpty($mail->render());
    }

    public function test_le_rapport_annuel_couvre_lannee_ecoulee(): void
    {
        $anneeEcoulee = now()->copy()->subYear();

        $data = app(ReportService::class)->data(
            $this->compagnie->id,
            $anneeEcoulee->copy()->startOfYear(),
            $anneeEcoulee->copy()->endOfYear(),
        );

        $this->assertSame($anneeEcoulee->year, $data['start']->year);
        $this->assertSame($anneeEcoulee->year, $data['end']->year);
    }
}
