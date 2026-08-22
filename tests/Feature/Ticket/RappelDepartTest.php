<?php

namespace Tests\Feature\Ticket;

use App\Enums\CompagnieSettingKey;
use App\Enums\RappelDepart;
use App\Enums\StatutTicket;
use App\Models\Compagnie\Compagnie;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use App\Notifications\Ticket\BonVoyageNotification;
use App\Notifications\Ticket\DepartureReminderNotification;
use App\Services\Compagnie\CompagnieSettingService;
use App\Services\Ticket\RappelDepartService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Rappels de départ et message de bon voyage.
 *
 * Les paliers sont fixes ; chaque compagnie choisit ceux qu'elle active et leur
 * avance de tir. Aucune colonne ne marque les envois : la trace lue est celle
 * que le canal `database` écrit dans la table `notifications`.
 */
class RappelDepartTest extends TestCase
{
    use RefreshDatabase;

    private Compagnie $compagnie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->compagnie = Compagnie::factory()->create();
    }

    private function service(): RappelDepartService
    {
        return app(RappelDepartService::class);
    }

    private function regler(CompagnieSettingKey $cle, mixed $valeur, ?Compagnie $compagnie = null): void
    {
        app(CompagnieSettingService::class)->set($compagnie ?? $this->compagnie, $cle, $valeur);
    }

    /** Crée un départ dans `$minutes` minutes, avec un passager. */
    private function departDans(int $minutes, ?Compagnie $compagnie = null, ?Carbon $achat = null): Ticket
    {
        $depart = Carbon::now()->addMinutes($minutes);

        $voyage = Voyage::factory()->create([
            'compagnie_id' => ($compagnie ?? $this->compagnie)->id,
            'temps'        => '04:00:00',
        ]);

        $instance = VoyageInstance::factory()->create([
            'voyage_id'  => $voyage->id,
            'date'       => $depart->toDateString(),
            'heure'      => $depart->format('H:i:s'),
            'nb_place'   => 50,
            'created_at' => $achat ?? Carbon::now()->subDays(3),
        ]);

        return Ticket::factory()->create([
            'voyage_instance_id' => $instance->id,
            'voyage_id'          => $instance->voyage_id,
            'statut'             => StatutTicket::Payer,
            'user_id'            => User::factory()->create()->id,
        ]);
    }

    // ── Activation par compagnie ────────────────────────────────────────────

    public function test_un_palier_actif_part_a_lheure_reglee(): void
    {
        Notification::fake();
        $this->departDans(55); // réglage par défaut : 60 min avant

        $this->service()->envoyerPalier(RappelDepart::AvantDepart);

        Notification::assertSentTimes(DepartureReminderNotification::class, 1);
    }

    public function test_un_palier_desactive_nenvoie_rien(): void
    {
        Notification::fake();
        $this->regler(CompagnieSettingKey::RAPPEL_AVANT_DEPART_ACTIF, false);
        $this->departDans(55);

        $this->service()->envoyerPalier(RappelDepart::AvantDepart);

        Notification::assertNothingSent();
    }

    public function test_une_compagnie_garde_un_palier_et_en_desactive_un_autre(): void
    {
        Notification::fake();
        $this->regler(CompagnieSettingKey::RAPPEL_AVANT_DEPART_ACTIF, false);
        $this->regler(CompagnieSettingKey::RAPPEL_EMBARQUEMENT_ACTIF, true);

        $this->departDans(0); // l'heure du départ est atteinte

        $this->service()->envoyerPalier(RappelDepart::AvantDepart);
        Notification::assertNothingSent();

        $this->service()->envoyerPalier(RappelDepart::Embarquement);
        Notification::assertSentTimes(DepartureReminderNotification::class, 1);
    }

    public function test_chaque_compagnie_suit_son_propre_reglage(): void
    {
        Notification::fake();
        $autre = Compagnie::factory()->create();

        $this->regler(CompagnieSettingKey::RAPPEL_AVANT_DEPART_ACTIF, false);
        $this->regler(CompagnieSettingKey::RAPPEL_AVANT_DEPART_ACTIF, true, $autre);

        $this->departDans(55);
        $this->departDans(55, $autre);

        $this->service()->envoyerPalier(RappelDepart::AvantDepart);

        Notification::assertSentTimes(DepartureReminderNotification::class, 1);
    }

    public function test_lavance_de_tir_est_respectee(): void
    {
        Notification::fake();
        $this->regler(CompagnieSettingKey::RAPPEL_AVANT_DEPART_MINUTES, 30);

        $this->departDans(50); // trop tôt pour un réglage à 30 min

        $this->service()->envoyerPalier(RappelDepart::AvantDepart);

        Notification::assertNothingSent();
    }

    public function test_une_avance_de_tir_allongee_declenche_plus_tot(): void
    {
        Notification::fake();
        $this->regler(CompagnieSettingKey::RAPPEL_AVANT_DEPART_MINUTES, 180);

        $this->departDans(150);

        $this->service()->envoyerPalier(RappelDepart::AvantDepart);

        Notification::assertSentTimes(DepartureReminderNotification::class, 1);
    }

    // ── Palier de la veille ─────────────────────────────────────────────────

    public function test_le_rappel_de_la_veille_part_a_24h(): void
    {
        Notification::fake();
        $this->departDans(23 * 60);

        $this->service()->envoyerPalier(RappelDepart::Veille);

        Notification::assertSentTimes(DepartureReminderNotification::class, 1);
    }

    public function test_pas_de_rappel_de_veille_pour_un_voyage_du_jour_meme(): void
    {
        Notification::fake();
        // Réservation créée le jour même du départ.
        $this->departDans(120, achat: Carbon::now());

        $this->service()->envoyerPalier(RappelDepart::Veille);

        Notification::assertNothingSent();
    }

    // ── Idempotence, sans colonne dédiée ────────────────────────────────────

    public function test_un_palier_deja_envoye_ne_repart_pas(): void
    {
        $this->departDans(55);

        $this->service()->envoyerPalier(RappelDepart::AvantDepart);
        $premier = \DB::table('notifications')->count();

        $this->service()->envoyerPalier(RappelDepart::AvantDepart);

        $this->assertSame($premier, \DB::table('notifications')->count(), 'Le rappel a été envoyé deux fois.');
        $this->assertSame(1, $premier);
    }

    public function test_deux_paliers_differents_partent_tous_les_deux(): void
    {
        $this->regler(CompagnieSettingKey::RAPPEL_AVANT_DEPART_MINUTES, 5);
        $this->departDans(0);

        $this->service()->envoyerPalier(RappelDepart::AvantDepart);
        $this->service()->envoyerPalier(RappelDepart::Embarquement);

        $this->assertSame(2, \DB::table('notifications')->count());
    }

    // ── Bon voyage ──────────────────────────────────────────────────────────

    public function test_le_bon_voyage_part_apres_le_delai_regle(): void
    {
        Notification::fake();
        $ticket = $this->departDans(-120);
        $ticket->update(['statut' => StatutTicket::Valider, 'valider_at' => now()->subMinutes(90)]);

        $this->service()->envoyerBonVoyage();

        Notification::assertSentTimes(BonVoyageNotification::class, 1);
    }

    public function test_le_bon_voyage_attend_le_delai(): void
    {
        Notification::fake();
        $ticket = $this->departDans(-30);
        $ticket->update(['statut' => StatutTicket::Valider, 'valider_at' => now()->subMinutes(20)]);

        $this->service()->envoyerBonVoyage();

        Notification::assertNothingSent();
    }

    public function test_le_bon_voyage_ignore_les_passagers_non_embarques(): void
    {
        Notification::fake();
        $this->departDans(-120); // resté au statut « Payer »

        $this->service()->envoyerBonVoyage();

        Notification::assertNothingSent();
    }

    public function test_le_bon_voyage_respecte_la_desactivation(): void
    {
        Notification::fake();
        $this->regler(CompagnieSettingKey::BON_VOYAGE_ACTIF, false);

        $ticket = $this->departDans(-120);
        $ticket->update(['statut' => StatutTicket::Valider, 'valider_at' => now()->subMinutes(90)]);

        $this->service()->envoyerBonVoyage();

        Notification::assertNothingSent();
    }

    public function test_le_bon_voyage_ne_part_quune_fois(): void
    {
        $ticket = $this->departDans(-120);
        $ticket->update(['statut' => StatutTicket::Valider, 'valider_at' => now()->subMinutes(90)]);

        $this->service()->envoyerBonVoyage();
        $this->service()->envoyerBonVoyage();

        $this->assertSame(1, \DB::table('notifications')->count());
    }

    public function test_le_delai_de_bon_voyage_est_reglable(): void
    {
        Notification::fake();
        $this->regler(CompagnieSettingKey::BON_VOYAGE_DELAI_MINUTES, 15);

        $ticket = $this->departDans(-60);
        $ticket->update(['statut' => StatutTicket::Valider, 'valider_at' => now()->subMinutes(20)]);

        $this->service()->envoyerBonVoyage();

        Notification::assertSentTimes(BonVoyageNotification::class, 1);
    }

    // ── Rendu des emails ────────────────────────────────────────────────────

    public function test_chaque_palier_rend_un_email_complet(): void
    {
        $ticket = $this->departDans(55);
        $ticket->loadMissing('voyageInstance.voyage.trajet.depart', 'voyageInstance.voyage.trajet.arriver');

        foreach (RappelDepart::ordonnes() as $palier) {
            $mail = (new DepartureReminderNotification($ticket, $ticket->voyageInstance, $palier))
                ->toMail($ticket->user);

            $rendu = $mail->render();

            $this->assertNotEmpty($rendu, "Email vide pour le palier {$palier->value}.");
            $this->assertStringContainsString($ticket->numero_ticket, $rendu);
        }
    }

    public function test_lemail_de_bon_voyage_se_rend(): void
    {
        $ticket = $this->departDans(-120);
        $ticket->update(['statut' => StatutTicket::Valider, 'valider_at' => now()->subHour()]);

        $rendu = (new BonVoyageNotification($ticket->fresh()))->toMail($ticket->user)->render();

        $this->assertNotEmpty($rendu);
        $this->assertStringContainsString($ticket->numero_ticket, $rendu);
    }

    // ── Commandes ───────────────────────────────────────────────────────────

    public function test_la_commande_traite_tous_les_paliers(): void
    {
        $this->departDans(55);

        $this->artisan('notifications:departure-reminders')->assertExitCode(0);
    }

    public function test_la_commande_accepte_un_palier_precis(): void
    {
        $this->artisan('notifications:departure-reminders', ['--palier' => 'veille'])->assertExitCode(0);
    }

    public function test_la_commande_refuse_un_palier_inconnu(): void
    {
        $this->artisan('notifications:departure-reminders', ['--palier' => 'inexistant'])->assertExitCode(1);
    }

    public function test_la_commande_bon_voyage_sexecute(): void
    {
        $this->artisan('notifications:bon-voyage')->assertExitCode(0);
    }
}
