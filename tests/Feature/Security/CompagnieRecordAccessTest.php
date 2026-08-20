<?php

namespace Tests\Feature\Security;

use App\Enums\StatutUser;
use App\Enums\UserRole;
use App\Livewire\Compagnie\Compagnie\ChauffeurManager;
use App\Livewire\Compagnie\Compagnie\UserManager;
use App\Livewire\Compagnie\Ticket\TicketManager;
use App\Livewire\Compagnie\Voyage\VoyageInstanceManager;
use App\Livewire\Compagnie\Voyage\VoyageManager;
use App\Models\Compagnie\Chauffer;
use App\Models\Compagnie\Compagnie;
use App\Models\User;
use App\Models\Voyage\Voyage;
use App\Models\Voyage\VoyageInstance;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Accès aux enregistrements par identifiant.
 *
 * Les propriétés publiques d'un composant Livewire sont modifiables depuis le
 * navigateur : un identifiant reçu n'est jamais une donnée de confiance. Ces
 * actions acceptaient auparavant n'importe quel identifiant, permettant de
 * consulter — et surtout de supprimer — les données d'une autre compagnie.
 */
class CompagnieRecordAccessTest extends TestCase
{
    use RefreshDatabase;

    private function agentDe(Compagnie $compagnie): User
    {
        return User::factory()->create([
            'compagnie_id' => $compagnie->id,
            'role'         => UserRole::CompagnieBosse,
        ]);
    }

    /** Assure qu'une action refuse un enregistrement d'une autre compagnie. */
    private function assertActionRefusee(User $agent, string $composant, string $action, mixed ...$args): void
    {
        try {
            Livewire::actingAs($agent)->test($composant)->call($action, ...$args);
            $this->fail("L'action « {$action} » aurait dû refuser cet identifiant.");
        } catch (ModelNotFoundException) {
            // Comportement attendu : l'enregistrement est hors périmètre.
            $this->addToAssertionCount(1);
        }
    }

    // ── Voyages ─────────────────────────────────────────────────────────────

    public function test_supprimer_le_voyage_dune_autre_compagnie_est_refuse(): void
    {
        $sienne = Compagnie::factory()->create();
        $voyageConcurrent = Voyage::factory()->create(['compagnie_id' => Compagnie::factory()->create()->id]);

        $this->assertActionRefusee($this->agentDe($sienne), VoyageManager::class, 'delete', $voyageConcurrent->id);

        $this->assertDatabaseHas('voyages', ['id' => $voyageConcurrent->id]);
    }

    public function test_supprimer_son_propre_voyage_reste_possible(): void
    {
        $sienne = Compagnie::factory()->create();
        $voyage = Voyage::factory()->create(['compagnie_id' => $sienne->id]);

        Livewire::actingAs($this->agentDe($sienne))
            ->test(VoyageManager::class)
            ->call('delete', $voyage->id);

        $this->assertDatabaseMissing('voyages', ['id' => $voyage->id]);
    }

    // ── Instances de voyage ─────────────────────────────────────────────────

    public function test_supprimer_linstance_dune_autre_compagnie_est_refuse(): void
    {
        $sienne = Compagnie::factory()->create();
        $instanceConcurrente = $this->instancePour(Compagnie::factory()->create());

        $this->assertActionRefusee(
            $this->agentDe($sienne),
            VoyageInstanceManager::class,
            'delete',
            $instanceConcurrente->id,
        );

        $this->assertDatabaseHas('voyage_instances', ['id' => $instanceConcurrente->id]);
    }

    public function test_ouvrir_linstance_dune_autre_compagnie_est_refuse(): void
    {
        $sienne = Compagnie::factory()->create();
        $instanceConcurrente = $this->instancePour(Compagnie::factory()->create());

        $this->assertActionRefusee(
            $this->agentDe($sienne),
            VoyageInstanceManager::class,
            'openEdit',
            $instanceConcurrente->id,
        );
    }

    public function test_ouvrir_sa_propre_instance_reste_possible(): void
    {
        $sienne = Compagnie::factory()->create();
        $instance = $this->instancePour($sienne);

        Livewire::actingAs($this->agentDe($sienne))
            ->test(VoyageInstanceManager::class)
            ->call('openEdit', $instance->id)
            ->assertSet('editingId', $instance->id);
    }

    // ── Équipe ──────────────────────────────────────────────────────────────

    public function test_bloquer_un_compte_dune_autre_compagnie_est_refuse(): void
    {
        $sienne = Compagnie::factory()->create();
        $collegueConcurrent = $this->agentDe(Compagnie::factory()->create());

        $this->assertActionRefusee($this->agentDe($sienne), UserManager::class, 'bloquer', $collegueConcurrent->id);

        $this->assertNotSame(StatutUser::Bloquer, $collegueConcurrent->fresh()->statut);
    }

    public function test_bloquer_un_client_sans_compagnie_est_refuse(): void
    {
        $sienne = Compagnie::factory()->create();
        $client = User::factory()->create(['role' => UserRole::User]);

        $this->assertActionRefusee($this->agentDe($sienne), UserManager::class, 'bloquer', $client->id);
    }

    public function test_ouvrir_le_compte_dune_autre_compagnie_est_refuse(): void
    {
        $sienne = Compagnie::factory()->create();
        $collegueConcurrent = $this->agentDe(Compagnie::factory()->create());

        $this->assertActionRefusee($this->agentDe($sienne), UserManager::class, 'openEdit', $collegueConcurrent->id);
    }

    public function test_bloquer_un_collegue_de_sa_compagnie_reste_possible(): void
    {
        $sienne = Compagnie::factory()->create();
        $collegue = $this->agentDe($sienne);

        Livewire::actingAs($this->agentDe($sienne))
            ->test(UserManager::class)
            ->call('bloquer', $collegue->id);

        $this->assertSame(StatutUser::Bloquer, $collegue->fresh()->statut);
    }

    // ── Chauffeurs ──────────────────────────────────────────────────────────

    public function test_ouvrir_le_chauffeur_dune_autre_compagnie_est_refuse(): void
    {
        $sienne = Compagnie::factory()->create();
        $chauffeurConcurrent = Chauffer::create([
            'first_name'      => 'Ali',
            'last_name'       => 'Traoré',
            'date_naissance'  => '1985-04-12',
            'genre'           => 'Homme',
            'statut'          => 'Disponible',
            'compagnie_id'    => Compagnie::factory()->create()->id,
        ]);

        $this->assertActionRefusee($this->agentDe($sienne), ChauffeurManager::class, 'openEdit', $chauffeurConcurrent->id);
    }

    // ── Billets ─────────────────────────────────────────────────────────────

    public function test_agir_sur_le_billet_dune_autre_compagnie_est_refuse(): void
    {
        $sienne = Compagnie::factory()->create();
        $instanceConcurrente = $this->instancePour(Compagnie::factory()->create());

        $billet = \App\Models\Ticket\Ticket::factory()->create([
            'voyage_instance_id' => $instanceConcurrente->id,
            'voyage_id'          => $instanceConcurrente->voyage_id,
        ]);

        $this->assertActionRefusee($this->agentDe($sienne), TicketManager::class, 'bloquer', $billet->id);
    }

    /** Crée une instance de voyage opérée par la compagnie donnée. */
    private function instancePour(Compagnie $compagnie): VoyageInstance
    {
        $voyage = Voyage::factory()->create(['compagnie_id' => $compagnie->id, 'temps' => '04:00:00']);

        return VoyageInstance::factory()->create([
            'voyage_id' => $voyage->id,
            'date'      => now()->addDays(3)->toDateString(),
            'heure'     => '08:00:00',
            'nb_place'  => 50,
        ]);
    }
}
