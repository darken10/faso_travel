<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\Compagnie\Compagnie;
use App\Models\User;
use App\Rbac\PermissionCatalogue;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use LogicException;
use Mockery;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogger $journal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->journal = app(AuditLogger::class);
    }

    public function test_une_trace_porte_l_acteur_et_sa_compagnie(): void
    {
        $compagnie = Compagnie::factory()->create();
        $acteur = User::factory()->create(['compagnie_id' => $compagnie->id]);
        Auth::login($acteur);

        $trace = $this->journal->log('finance.depense.delete', $acteur, ['montant' => 5000]);

        $this->assertNotNull($trace);
        $this->assertSame($acteur->id, $trace->user_id);
        $this->assertSame($compagnie->id, $trace->compagnie_id);
        $this->assertSame('finance.depense.delete', $trace->action);
        $this->assertSame(['montant' => 5000], $trace->avant);
        $this->assertNull($trace->apres);
    }

    public function test_la_compagnie_est_deduite_de_la_cible_sans_acteur(): void
    {
        $compagnie = Compagnie::factory()->create();
        $cible = User::factory()->create(['compagnie_id' => $compagnie->id]);

        $trace = $this->journal->log('compagnie.role.assign', $cible);

        $this->assertNull($trace->user_id);
        $this->assertSame($compagnie->id, $trace->compagnie_id);
    }

    public function test_une_trace_ne_peut_pas_etre_modifiee(): void
    {
        $trace = $this->journal->log('guichet.ticket.cancel');

        $this->expectException(LogicException::class);
        $trace->update(['action' => 'autre.chose']);
    }

    public function test_une_trace_ne_peut_pas_etre_modifiee_par_save(): void
    {
        $trace = $this->journal->log('guichet.ticket.cancel');
        $trace->action = 'autre.chose';

        $this->expectException(LogicException::class);
        $trace->save();
    }

    public function test_une_trace_ne_peut_pas_etre_supprimee(): void
    {
        $trace = $this->journal->log('guichet.ticket.cancel');

        $this->expectException(LogicException::class);
        $trace->delete();
    }

    public function test_les_attributs_sensibles_sont_masques(): void
    {
        $trace = $this->journal->log('compagnie.role.assign', null, [
            'email' => 'awa@example.test',
            'password' => '$2y$04$hachageenclair',
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
            'remember_token' => 'abc123',
        ]);

        $this->assertSame('awa@example.test', $trace->avant['email']);
        $this->assertSame('[masqué]', $trace->avant['password']);
        $this->assertSame('[masqué]', $trace->avant['two_factor_secret']);
        $this->assertSame('[masqué]', $trace->avant['remember_token']);
    }

    public function test_un_echec_d_ecriture_n_annule_pas_l_operation(): void
    {
        Log::shouldReceive('channel')->once()->with('rbac')->andReturnSelf();
        Log::shouldReceive('error')->once();

        // On force l'échec : `action` est limité à 120 caractères et n'accepte pas null.
        $journal = new class extends AuditLogger
        {
            public function log(string $action, ?\Illuminate\Database\Eloquent\Model $cible = null, array $avant = [], array $apres = [], ?string $motif = null): ?AuditLog
            {
                return parent::log(str_repeat('x', 500), $cible, $avant, $apres, $motif);
            }
        };

        $this->assertNull($journal->log('peu importe'));
        $this->assertSame(0, AuditLog::count());
    }

    public function test_la_modification_ne_retient_que_les_attributs_changes(): void
    {
        $user = User::factory()->create(['first_name' => 'Awa']);
        $avant = $user->attributesToArray();

        $user->first_name = 'Aminata';
        $user->save();

        $trace = $this->journal->logModification('compagnie.user.update', $user, $avant);

        $this->assertSame(['first_name' => 'Awa'], $this->sansHorodatage($trace->avant));
        $this->assertSame(['first_name' => 'Aminata'], $this->sansHorodatage($trace->apres));
    }

    public function test_en_console_l_agent_est_renseigne_et_l_ip_nulle(): void
    {
        $trace = $this->journal->log('guichet.ticket.cancel');

        $this->assertSame('console', $trace->user_agent);
        $this->assertNull($trace->ip);
    }

    public function test_le_scope_filtre_par_compagnie_et_par_action(): void
    {
        $compagnie = Compagnie::factory()->create();
        Auth::login(User::factory()->create(['compagnie_id' => $compagnie->id]));
        $this->journal->log('finance.depense.delete');
        $this->journal->log('guichet.ticket.unblock');

        $this->assertSame(2, AuditLog::ofCompagnie($compagnie->id)->count());
        $this->assertSame(1, AuditLog::action('finance.depense.delete')->count());
    }

    /**
     * Pin des actions sensibles encore sans point d'appel.
     *
     * Ce test n'est pas un contrôle de qualité : c'est un rappel. Chaque tâche qui livre
     * l'écran manquant doit retirer son action de cette liste et ajouter sa journalisation,
     * sinon une action sensible resterait silencieuse sans que personne s'en aperçoive.
     */
    public function test_les_actions_sensibles_sans_point_d_appel_sont_connues(): void
    {
        $sansPointDAppel = [
            // Aucun écran d'usurpation d'identité ni de suspension de compagnie (T09+).
            'platform.user.impersonate',
            'platform.compagnie.suspend',
            // La réimpression et l'annulation n'existent pas comme actions distinctes.
            'guichet.ticket.reprint',
            'guichet.ticket.cancel',
            // Clôture forcée et validation d'écart : livrées par T14.
            'caisse.session.forceClose',
            'caisse.ecart.validate',
            // Approbation de dépense : livrée par T11 (règle des quatre yeux).
            'finance.depense.approve',
            // Ajustement manuel de points : aucun écran, seul l'octroi automatique existe.
            'crm.fidelite.adjust',
        ];

        $journalisees = [
            'compagnie.role.assign',
            'compagnie.parametres.updateAdvanced',
            'guichet.ticket.unblock',
            'embarquement.conflit.resolve',
            'finance.depense.delete',
            'finance.recette.delete',
            'finance.remboursement.approve',
        ];

        $attendu = PermissionCatalogue::sensibles();
        sort($attendu);

        $couvert = array_merge($sansPointDAppel, $journalisees);
        sort($couvert);

        $this->assertSame(
            $attendu,
            $couvert,
            'Une action sensible n\'est ni journalisée ni déclarée comme sans point d\'appel.'
        );
    }

    /** @param  array<string, mixed>|null  $attributs */
    private function sansHorodatage(?array $attributs): array
    {
        return array_diff_key($attributs ?? [], array_flip(['created_at', 'updated_at']));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
