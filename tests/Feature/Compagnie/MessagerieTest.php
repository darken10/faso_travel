<?php

namespace Tests\Feature\Compagnie;

use App\Events\MessageSent;
use App\Livewire\Compagnie\Message\Messagerie;
use App\Models\Compagnie\Compagnie;
use App\Models\Messages\Conversation;
use App\Models\Messages\Message;
use App\Models\User;
use App\Notifications\Channels\ExpoChannel;
use App\Notifications\NouveauMessageNotification;
use App\Services\Messages\CompagnieMessagerieService;
use App\Services\V2\ConversationService;
use Illuminate\Contracts\Broadcasting\Factory as BroadcastFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Boîte de messages de la compagnie : lire les messages des clients et leur répondre.
 */
class MessagerieTest extends TestCase
{
    use RefreshDatabase;

    private Compagnie $compagnie;
    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compagnie = Compagnie::factory()->create();
        $this->agent = User::factory()->create(['compagnie_id' => $this->compagnie->id]);
    }

    private function conversation(array $attributes = [], ?Compagnie $compagnie = null): Conversation
    {
        return Conversation::factory()->create($attributes + [
            'compagnie_id' => ($compagnie ?? $this->compagnie)->id,
        ]);
    }

    private function messageDuClient(Conversation $conversation, string $texte = 'Bonjour'): Message
    {
        return Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $conversation->client_id,
            'message'         => $texte,
        ]);
    }

    private function panneau()
    {
        return Livewire::actingAs($this->agent)->test(Messagerie::class);
    }

    // ── Périmètre ──────────────────────────────────────────────────────────

    public function test_seules_les_conversations_de_la_compagnie_sont_listees(): void
    {
        $mienne = $this->conversation();
        $this->conversation([], Compagnie::factory()->create());

        $this->panneau()->assertViewHas('conversations', fn ($l) => $l->pluck('id')->all() === [$mienne->id]);
    }

    public function test_les_conversations_support_ne_sont_jamais_visibles(): void
    {
        // Conversation avec l'équipe Liptra : pas de compagnie, hors de portée du panneau.
        Conversation::factory()->support()->create();
        $mienne = $this->conversation();

        $this->panneau()->assertViewHas('conversations', fn ($l) => $l->pluck('id')->all() === [$mienne->id]);
    }

    public function test_une_conversation_archivee_nest_pas_listee(): void
    {
        $this->conversation()->delete();

        $this->panneau()->assertViewHas('conversations', fn ($l) => $l->isEmpty());
    }

    public function test_on_ne_peut_pas_ouvrir_la_conversation_dune_autre_compagnie(): void
    {
        $etrangere = $this->conversation([], Compagnie::factory()->create());

        $this->expectException(ModelNotFoundException::class);

        $this->panneau()->call('select', $etrangere->id);
    }

    public function test_un_identifiant_forge_ne_permet_ni_de_lire_ni_de_repondre(): void
    {
        $mienne = $this->conversation();
        $etrangere = $this->conversation([], Compagnie::factory()->create());
        $this->messageDuClient($etrangere, 'Message confidentiel');

        $panneau = $this->panneau()->call('select', $mienne->id)->set('reponse', 'Piratage');

        // Le navigateur remplace l'identifiant par celui d'une autre compagnie.
        $panneau->set('selectedId', $etrangere->id);

        $panneau->assertDontSee('Message confidentiel');
        // Première défense : l'affichage abandonne la conversation hors périmètre, et l'envoi
        // est refusé faute de conversation sélectionnée.
        $panneau->call('send')->assertStatus(422);

        $this->assertSame(0, Message::where('message', 'Piratage')->count());
    }

    public function test_le_service_refuse_de_repondre_hors_du_perimetre_de_la_compagnie(): void
    {
        // Seconde défense, indépendante de l'écran : avec Livewire, le navigateur peut
        // modifier l'identifiant ET appeler l'envoi dans la même requête, sans affichage entre les deux.
        $etrangere = $this->conversation([], Compagnie::factory()->create());

        try {
            app(CompagnieMessagerieService::class)->reply($etrangere->id, $this->compagnie->id, $this->agent, 'Piratage');
            $this->fail('La réponse hors périmètre aurait dû être refusée.');
        } catch (ModelNotFoundException) {
            // Comportement attendu.
        }

        $this->assertSame(0, Message::where('message', 'Piratage')->count());
        $this->assertSame(0, $etrangere->fresh()->unread_count_client, 'aucun effet de bord sur la conversation étrangère');
    }

    public function test_le_service_refuse_de_repondre_a_une_conversation_support(): void
    {
        $support = Conversation::factory()->support()->create();

        $this->expectException(ModelNotFoundException::class);

        app(CompagnieMessagerieService::class)->reply($support->id, $this->compagnie->id, $this->agent, 'Bonjour');
    }

    // ── Lecture et compteurs ───────────────────────────────────────────────

    public function test_ouvrir_une_conversation_la_marque_comme_lue(): void
    {
        $conversation = $this->conversation(['unread_count_agent' => 3]);
        $this->messageDuClient($conversation, 'Mon bus est-il à l\'heure ?');

        $this->panneau()
            ->call('select', $conversation->id)
            ->assertSee('Mon bus est-il à l\'heure ?');

        $this->assertSame(0, $conversation->fresh()->unread_count_agent);
    }

    public function test_un_message_recu_pendant_que_le_fil_est_ouvert_est_deja_lu(): void
    {
        $conversation = $this->conversation();
        $panneau = $this->panneau()->call('select', $conversation->id);

        // Nouveau message du client pendant que l'agent regarde le fil (sondage).
        $this->messageDuClient($conversation, 'Vous êtes là ?');
        $conversation->update(['unread_count_agent' => 1]);

        $panneau->call('$refresh')->assertSee('Vous êtes là ?');

        $this->assertSame(0, $conversation->fresh()->unread_count_agent, 'sinon le menu afficherait un non-lu que personne n\'a à lire');
    }

    public function test_le_compteur_du_menu_ne_compte_que_les_conversations_de_la_compagnie(): void
    {
        $this->conversation(['unread_count_agent' => 2]);
        $this->conversation(['unread_count_agent' => 1]);
        $this->conversation(['unread_count_agent' => 0]);
        $this->conversation(['unread_count_agent' => 9], Compagnie::factory()->create());

        $this->assertSame(2, app(CompagnieMessagerieService::class)->unreadCount($this->compagnie->id));
    }

    // ── Filtres ────────────────────────────────────────────────────────────

    public function test_filtre_non_lues_et_recherche_par_client(): void
    {
        // Le modèle User recompose `name` à la création (PRÉNOM + nom) : on passe par first_name/last_name.
        $aminata = $this->conversation(['client_id' => User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Traoré'])->id, 'unread_count_agent' => 1]);
        $this->conversation(['client_id' => User::factory()->create(['first_name' => 'Boureima', 'last_name' => 'Sawadogo'])->id]);

        $this->panneau()
            ->set('filtre', 'non_lus')
            ->assertViewHas('conversations', fn ($l) => $l->pluck('id')->all() === [$aminata->id])
            ->set('filtre', 'tous')
            ->set('search', 'boureima')   // insensible à la casse
            ->assertViewHas('conversations', fn ($l) => $l->count() === 1 && $l->first()->client->last_name === 'Sawadogo');
    }

    public function test_la_recherche_traite_les_jokers_sql_comme_du_texte(): void
    {
        $this->conversation(['client_id' => User::factory()->create(['first_name' => 'Aminata'])->id]);

        // « % » seul ne doit pas tout renvoyer.
        $this->panneau()->set('search', '%')->assertViewHas('conversations', fn ($l) => $l->isEmpty());
    }

    public function test_les_plus_recentes_sont_en_premier(): void
    {
        $ancienne = $this->conversation(['last_message_at' => now()->subDays(2)]);
        $recente = $this->conversation(['last_message_at' => now()->subMinute()]);
        $vide = $this->conversation(['last_message_at' => null]);

        $this->panneau()->assertViewHas('conversations',
            fn ($l) => $l->pluck('id')->all() === [$recente->id, $ancienne->id, $vide->id]);
    }

    // ── Réponse ────────────────────────────────────────────────────────────

    public function test_la_reponse_est_enregistree_et_compte_pour_le_client(): void
    {
        Event::fake([MessageSent::class]);
        Notification::fake();

        $conversation = $this->conversation(['unread_count_agent' => 2, 'unread_count_client' => 1]);
        $this->messageDuClient($conversation);

        $this->panneau()
            ->call('select', $conversation->id)
            ->set('reponse', '  Oui, le bus part à 8 h.  ')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('reponse', '');

        $message = Message::where('conversation_id', $conversation->id)->where('sender_id', $this->agent->id)->firstOrFail();
        $this->assertSame($this->agent->id, $message->sender_id);
        $this->assertSame('Oui, le bus part à 8 h.', $message->message, 'les espaces de bord sont retirés');

        $conversation->refresh();
        $this->assertSame(2, $conversation->unread_count_client, 'le client a un message de plus à lire');
        $this->assertSame(0, $conversation->unread_count_agent, 'répondre vaut prise en charge');
        $this->assertSame('Oui, le bus part à 8 h.', $conversation->last_message);
        $this->assertNotNull($conversation->last_message_at);
    }

    public function test_le_client_recoit_la_diffusion_temps_reel_et_une_notification_push(): void
    {
        Event::fake([MessageSent::class]);
        Notification::fake();

        $conversation = $this->conversation();
        $this->panneau()->call('select', $conversation->id)->set('reponse', 'Bonjour !')->call('send');

        Event::assertDispatched(MessageSent::class,
            fn ($e) => $e->message->conversation_id === $conversation->id && $e->message->message === 'Bonjour !');

        Notification::assertSentTo($conversation->client, NouveauMessageNotification::class, function ($n) use ($conversation) {
            $expo = $n->toExpo($conversation->client);

            return $expo['title'] === $this->compagnie->name
                && $expo['body'] === 'Bonjour !'
                && $expo['data']['conversation_id'] === $conversation->id;
        });
    }

    public function test_la_notification_tronque_un_long_message(): void
    {
        $message = Message::factory()->create(['message' => str_repeat('a', 500)]);

        $expo = (new NouveauMessageNotification($message, 'Compagnie'))->toExpo(new User());

        $this->assertSame(100, mb_strlen($expo['body']));
    }

    public function test_une_reponse_vide_ou_blanche_est_refusee(): void
    {
        Notification::fake();
        $conversation = $this->conversation();

        $panneau = $this->panneau()->call('select', $conversation->id);

        $panneau->set('reponse', '')->call('send')->assertHasErrors(['reponse']);
        $panneau->set('reponse', "   \n  ")->call('send')->assertHasErrors(['reponse']);

        $this->assertSame(0, Message::count());
        Notification::assertNothingSent();
    }

    public function test_une_reponse_trop_longue_est_refusee(): void
    {
        $conversation = $this->conversation();

        $this->panneau()
            ->call('select', $conversation->id)
            ->set('reponse', str_repeat('x', CompagnieMessagerieService::MAX_LENGTH + 1))
            ->call('send')
            ->assertHasErrors(['reponse' => 'max']);

        $this->assertSame(0, Message::count());
    }

    public function test_le_contenu_est_echappe_a_laffichage(): void
    {
        $conversation = $this->conversation();
        $this->messageDuClient($conversation, '<script>alert("x")</script>');

        $this->panneau()
            ->call('select', $conversation->id)
            ->assertDontSeeHtml('<script>alert("x")</script>')
            ->assertSee('&lt;script&gt;', false);
    }

    // ── E-mail au client ───────────────────────────────────────────────────

    private function clientAvec(array $attributs): Conversation
    {
        return $this->conversation(['client_id' => User::factory()->create($attributs)->id]);
    }

    public function test_la_reponse_est_aussi_envoyee_par_email_au_client(): void
    {
        Event::fake([MessageSent::class]);
        Notification::fake();

        $conversation = $this->clientAvec(['email' => 'client@example.test', 'email_verified_at' => now()]);

        $this->panneau()->call('select', $conversation->id)->set('reponse', 'Votre bus part à 8 h.')->call('send');

        Notification::assertSentTo($conversation->client, NouveauMessageNotification::class,
            fn ($n, $canaux) => in_array('mail', $canaux, true) && in_array(ExpoChannel::class, $canaux, true));
    }

    public function test_un_compte_sans_email_recoit_seulement_le_push(): void
    {
        Event::fake([MessageSent::class]);
        Notification::fake();

        // Compte créé avec le téléphone seul : email NULL (colonne rendue facultative).
        $conversation = $this->clientAvec(['email' => null, 'numero' => 70000001]);

        $this->panneau()->call('select', $conversation->id)->set('reponse', 'Bonjour')->call('send')->assertHasNoErrors();

        Notification::assertSentTo($conversation->client, NouveauMessageNotification::class,
            fn ($n, $canaux) => $canaux === [ExpoChannel::class]);
        $this->assertSame(1, Message::where('message', 'Bonjour')->count(), 'la réponse est enregistrée malgré l\'absence d\'adresse');
    }

    public function test_une_adresse_non_verifiee_ne_recoit_pas_de_mail(): void
    {
        // Une faute de frappe à l'inscription enverrait le message d'une compagnie à un inconnu.
        $client = User::factory()->create(['email' => 'faute@example.test', 'email_verified_at' => null]);
        $message = Message::factory()->create();

        $this->assertSame(
            [ExpoChannel::class],
            (new NouveauMessageNotification($message, 'Compagnie'))->via($client),
        );
    }

    public function test_le_contenu_du_mail_nomme_la_compagnie_et_reprend_le_message(): void
    {
        $client = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Traoré']);
        $message = Message::factory()->create(['message' => "Bonjour,\nle départ est à 8 h."]);

        $mail = (new NouveauMessageNotification($message, 'TSR Transport'))->toMail($client);
        $html = (string) $mail->render();

        $this->assertStringContainsString('TSR Transport', $mail->subject);
        $this->assertStringContainsString('AMINATA Traoré', $html);
        $this->assertStringContainsString('le départ est à 8 h.', $html);
        $this->assertStringContainsString('<br', $html, 'les retours à la ligne sont conservés');
    }

    public function test_le_texte_ecrit_par_la_compagnie_est_echappe_dans_le_mail(): void
    {
        $client = User::factory()->create();
        $message = Message::factory()->create(['message' => '<script>alert(1)</script> <a href="http://pirate.test">cliquez</a>']);

        $html = (string) (new NouveauMessageNotification($message, 'Compagnie'))->toMail($client)->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<a href="http://pirate.test">', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_un_nom_de_compagnie_piege_est_echappe_dans_le_mail(): void
    {
        $client = User::factory()->create();
        $message = Message::factory()->create();

        $html = (string) (new NouveauMessageNotification($message, '<img src=x onerror=alert(1)>'))->toMail($client)->render();

        $this->assertStringNotContainsString('<img src=x', $html);
    }

    // ── Robustesse : la réponse n'est jamais perdue ────────────────────────

    public function test_la_reponse_est_conservee_si_la_diffusion_temps_reel_echoue(): void
    {
        Notification::fake();
        Log::spy();

        // Serveur WebSocket arrêté : la diffusion lève une exception.
        $this->app->instance(BroadcastFactory::class, \Mockery::mock(BroadcastFactory::class, function ($m) {
            $m->shouldReceive('event')->andThrow(new \RuntimeException('Reverb injoignable'));
        }));

        $conversation = $this->conversation();
        $this->panneau()->call('select', $conversation->id)->set('reponse', 'Réponse importante')->call('send')
            ->assertHasNoErrors();

        $this->assertSame(1, Message::where('message', 'Réponse importante')->count(), 'la réponse doit rester enregistrée');
        $this->assertSame(1, $conversation->fresh()->unread_count_client);
        Notification::assertSentTo($conversation->client, NouveauMessageNotification::class);
        Log::shouldHaveReceived('warning')->withArgs(fn ($m) => str_contains($m, 'diffusion temps réel'))->once();
    }

    public function test_la_reponse_est_conservee_si_la_notification_push_echoue(): void
    {
        Event::fake([MessageSent::class]);
        Log::spy();

        $conversation = $this->conversation();
        Notification::shouldReceive('send')->never(); // ne sert pas : on fait échouer le notify du client
        $client = \Mockery::mock($conversation->client)->makePartial();
        $client->shouldReceive('notify')->andThrow(new \RuntimeException('file indisponible'));
        $conversation->setRelation('client', $client);

        // Même chemin que le service, avec un client dont la notification lève.
        $service = app(CompagnieMessagerieService::class);
        $reflexion = new \ReflectionMethod($service, 'notifierLeClient');
        $reflexion->setAccessible(true);
        $message = Message::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $this->agent->id]);

        $reflexion->invoke($service, $conversation, $message);   // ne doit pas lever

        Log::shouldHaveReceived('warning')->withArgs(fn ($m) => str_contains($m, 'notification push'))->once();
        $this->assertTrue(true);
    }

    // ── Compteurs : pas de message compté en moins sous concurrence ────────

    /**
     * Simule un traitement concurrent : à l'instant où notre message est créé (donc APRÈS
     * que la conversation a été lue et AVANT qu'on écrive ses compteurs), une autre requête
     * incrémente le compteur directement en base.
     */
    private function incrementConcurrent(string $conversationId, string $colonne): void
    {
        Message::created(function () use ($conversationId, $colonne) {
            static $fait = false;
            if (! $fait) {
                $fait = true;
                DB::table('conversations')->where('id', $conversationId)->increment($colonne);
            }
        });
    }

    public function test_deux_messages_du_client_simultanes_comptent_pour_deux_non_lus(): void
    {
        Event::fake([MessageSent::class]);

        $conversation = $this->conversation();
        Auth::login($conversation->client);

        // Un autre envoi du client (second appareil) s'intercale pendant le nôtre.
        $this->incrementConcurrent($conversation->id, 'unread_count_agent');
        app(ConversationService::class)->sendMessage($conversation->id, 'Bonjour');

        // Lire la valeur puis la réécrire aurait écrasé l'envoi concurrent : 1 au lieu de 2.
        $this->assertSame(2, $conversation->fresh()->unread_count_agent);
    }

    public function test_deux_reponses_de_collegues_simultanees_comptent_pour_deux_non_lus_cote_client(): void
    {
        Event::fake([MessageSent::class]);
        Notification::fake();

        $conversation = $this->conversation();
        $this->incrementConcurrent($conversation->id, 'unread_count_client');

        app(CompagnieMessagerieService::class)->reply($conversation->id, $this->compagnie->id, $this->agent, 'Bonjour');

        $this->assertSame(2, $conversation->fresh()->unread_count_client);
    }

    // ── Page complète ──────────────────────────────────────────────────────

    public function test_la_page_complete_affiche_le_menu_et_le_compteur(): void
    {
        $this->conversation(['unread_count_agent' => 1]);
        $this->conversation(['unread_count_agent' => 4]);
        $this->conversation(['unread_count_agent' => 7], Compagnie::factory()->create());

        $this->actingAs($this->agent)
            ->get(route('panel.compagnie.messages'))
            ->assertOk()
            ->assertSee('Messages')
            // 2 conversations en attente chez nous ; celle de l'autre compagnie ne compte pas.
            ->assertSee('bg-amber-500 text-white text-xs font-bold">2<', false);
    }

    public function test_la_page_est_interdite_sans_compagnie(): void
    {
        $this->actingAs(User::factory()->create(['compagnie_id' => null]))
            ->get(route('panel.compagnie.messages'))
            ->assertForbidden();
    }
}
