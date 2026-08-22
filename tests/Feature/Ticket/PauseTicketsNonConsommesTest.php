<?php

namespace Tests\Feature\Ticket;

use App\Enums\StatutTicket;
use App\Enums\StatutVoyageInstance;
use App\Enums\CompagnieSettingKey;
use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Services\Compagnie\CompagnieSettingService;
use App\Services\Ticket\TicketExpirationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un billet payé mais jamais scanné correspond à un voyageur absent au départ.
 * Passé un délai de battement, il bascule en « Pause » pour pouvoir être
 * reporté sur un autre voyage.
 */
class PauseTicketsNonConsommesTest extends TestCase
{
    use RefreshDatabase;

    private function service(): TicketExpirationService
    {
        return app(TicketExpirationService::class);
    }

    /** Crée un billet sur un départ situé à `$heuresAvantMaintenant` dans le passé. */
    private function billet(
        float $heuresAvantMaintenant,
        StatutTicket $statut = StatutTicket::Payer,
        StatutVoyageInstance $statutInstance = StatutVoyageInstance::DISPONIBLE,
        ?Compagnie $compagnie = null,
    ): Ticket {
        $depart = Carbon::now()->subMinutes((int) round($heuresAvantMaintenant * 60));

        $voyage = Voyage::factory()->create([
            'temps'        => '04:00:00',
            'compagnie_id' => $compagnie?->id ?? Compagnie::factory()->create()->id,
        ]);
        $instance = VoyageInstance::factory()->create([
            'voyage_id' => $voyage->id,
            'date'      => $depart->toDateString(),
            'heure'     => $depart->format('H:i:s'),
            'nb_place'  => 50,
            'statut'    => $statutInstance,
        ]);

        return Ticket::factory()->create([
            'voyage_instance_id' => $instance->id,
            'voyage_id'          => $instance->voyage_id,
            'statut'             => $statut,
        ]);
    }

    public function test_un_billet_paye_non_scanne_passe_en_pause(): void
    {
        $billet = $this->billet(heuresAvantMaintenant: 5);

        $bilan = $this->service()->pauseNonConsommes(graceHoursOverride: 3);

        $this->assertSame(1, $bilan['paused']);
        $this->assertSame(StatutTicket::Pause, $billet->fresh()->statut);
    }

    public function test_le_delai_de_battement_est_respecte(): void
    {
        $billet = $this->billet(heuresAvantMaintenant: 1);

        $bilan = $this->service()->pauseNonConsommes(graceHoursOverride: 3);

        $this->assertSame(0, $bilan['total']);
        $this->assertSame(StatutTicket::Payer, $billet->fresh()->statut);
    }

    public function test_un_depart_a_venir_nest_pas_touche(): void
    {
        $billet = $this->billet(heuresAvantMaintenant: -48);

        $this->service()->pauseNonConsommes(graceHoursOverride: 3);

        $this->assertSame(StatutTicket::Payer, $billet->fresh()->statut);
    }

    public function test_un_billet_deja_valide_reste_intact(): void
    {
        $billet = $this->billet(heuresAvantMaintenant: 10, statut: StatutTicket::Valider);

        $bilan = $this->service()->pauseNonConsommes(graceHoursOverride: 3);

        $this->assertSame(0, $bilan['total']);
        $this->assertSame(StatutTicket::Valider, $billet->fresh()->statut);
    }

    public function test_un_billet_annule_reste_intact(): void
    {
        $billet = $this->billet(heuresAvantMaintenant: 10, statut: StatutTicket::Annuler);

        $this->service()->pauseNonConsommes(graceHoursOverride: 3);

        $this->assertSame(StatutTicket::Annuler, $billet->fresh()->statut);
    }

    public function test_un_billet_non_paye_reste_intact(): void
    {
        $billet = $this->billet(heuresAvantMaintenant: 10, statut: StatutTicket::EnAttente);

        $this->service()->pauseNonConsommes(graceHoursOverride: 3);

        $this->assertSame(StatutTicket::EnAttente, $billet->fresh()->statut);
    }

    public function test_un_voyage_annule_releve_du_remboursement_pas_de_la_pause(): void
    {
        $billet = $this->billet(
            heuresAvantMaintenant: 10,
            statutInstance: StatutVoyageInstance::ANNULE,
        );

        $bilan = $this->service()->pauseNonConsommes(graceHoursOverride: 3);

        $this->assertSame(0, $bilan['total']);
        $this->assertSame(StatutTicket::Payer, $billet->fresh()->statut);
    }

    public function test_un_billet_deja_en_pause_nest_pas_retraite(): void
    {
        $this->billet(heuresAvantMaintenant: 10, statut: StatutTicket::Pause);

        $bilan = $this->service()->pauseNonConsommes(graceHoursOverride: 3);

        $this->assertSame(0, $bilan['total']);
    }

    public function test_plusieurs_billets_sont_traites_en_un_passage(): void
    {
        $this->billet(heuresAvantMaintenant: 4);
        $this->billet(heuresAvantMaintenant: 9);
        $this->billet(heuresAvantMaintenant: 1);   // dans le battement
        $this->billet(heuresAvantMaintenant: -5);  // à venir

        $bilan = $this->service()->pauseNonConsommes(graceHoursOverride: 3);

        $this->assertSame(2, $bilan['paused']);
        $this->assertSame(0, $bilan['failed']);
    }

    // ── Commande ────────────────────────────────────────────────────────────

    public function test_la_commande_met_les_billets_en_pause(): void
    {
        $billet = $this->billet(heuresAvantMaintenant: 6);

        $this->artisan('tickets:pause-non-consommes', ['--hours' => 3])
            ->assertExitCode(0);

        $this->assertSame(StatutTicket::Pause, $billet->fresh()->statut);
    }

    public function test_le_mode_dry_run_ne_modifie_rien(): void
    {
        $billet = $this->billet(heuresAvantMaintenant: 6);

        $this->artisan('tickets:pause-non-consommes', ['--hours' => 3, '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame(StatutTicket::Payer, $billet->fresh()->statut);
    }

    public function test_sans_option_la_commande_applique_le_reglage_de_la_compagnie(): void
    {
        $recent = $this->billet(heuresAvantMaintenant: 1);
        $ancien = $this->billet(heuresAvantMaintenant: 8);

        $this->artisan('tickets:pause-non-consommes')->assertExitCode(0);

        $this->assertSame(StatutTicket::Payer, $recent->fresh()->statut);
        $this->assertSame(StatutTicket::Pause, $ancien->fresh()->statut);
    }

    // ── Battement réglé par l'administrateur ────────────────────────────────

    public function test_le_battement_suit_le_reglage_de_la_compagnie(): void
    {
        $compagnie = Compagnie::factory()->create();
        app(CompagnieSettingService::class)->set(
            $compagnie,
            CompagnieSettingKey::DELAI_PAUSE_NON_CONSOMME,
            12,
            allowAdminOnly: true,
        );

        $dansLeBattement = $this->billet(heuresAvantMaintenant: 6, compagnie: $compagnie);
        $horsBattement   = $this->billet(heuresAvantMaintenant: 15, compagnie: $compagnie);

        $bilan = $this->service()->pauseNonConsommes();

        $this->assertSame(1, $bilan['paused']);
        $this->assertSame(StatutTicket::Payer, $dansLeBattement->fresh()->statut);
        $this->assertSame(StatutTicket::Pause, $horsBattement->fresh()->statut);
    }

    public function test_chaque_compagnie_applique_son_propre_battement(): void
    {
        $rapide = Compagnie::factory()->create();
        $lente  = Compagnie::factory()->create();

        $settings = app(CompagnieSettingService::class);
        $settings->set($rapide, CompagnieSettingKey::DELAI_PAUSE_NON_CONSOMME, 1, allowAdminOnly: true);
        $settings->set($lente, CompagnieSettingKey::DELAI_PAUSE_NON_CONSOMME, 24, allowAdminOnly: true);

        $billetRapide = $this->billet(heuresAvantMaintenant: 5, compagnie: $rapide);
        $billetLent   = $this->billet(heuresAvantMaintenant: 5, compagnie: $lente);

        $this->service()->pauseNonConsommes();

        $this->assertSame(StatutTicket::Pause, $billetRapide->fresh()->statut);
        $this->assertSame(StatutTicket::Payer, $billetLent->fresh()->statut);
    }

    public function test_loption_hours_force_un_battement_commun(): void
    {
        $lente = Compagnie::factory()->create();
        app(CompagnieSettingService::class)->set(
            $lente,
            CompagnieSettingKey::DELAI_PAUSE_NON_CONSOMME,
            48,
            allowAdminOnly: true,
        );

        $billet = $this->billet(heuresAvantMaintenant: 5, compagnie: $lente);

        $this->artisan('tickets:pause-non-consommes', ['--hours' => 2])->assertExitCode(0);

        $this->assertSame(StatutTicket::Pause, $billet->fresh()->statut);
    }

    public function test_sans_reglage_la_compagnie_suit_la_valeur_par_defaut(): void
    {
        $compagnie = Compagnie::factory()->create();

        $this->assertSame(3, CompagnieSettingKey::DELAI_PAUSE_NON_CONSOMME->default());

        $recent = $this->billet(heuresAvantMaintenant: 2, compagnie: $compagnie);
        $ancien = $this->billet(heuresAvantMaintenant: 4, compagnie: $compagnie);

        $this->service()->pauseNonConsommes();

        $this->assertSame(StatutTicket::Payer, $recent->fresh()->statut);
        $this->assertSame(StatutTicket::Pause, $ancien->fresh()->statut);
    }

    public function test_un_delai_negatif_est_refuse(): void
    {
        $this->artisan('tickets:pause-non-consommes', ['--hours' => -1])
            ->assertExitCode(1);
    }
}
