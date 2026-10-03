<?php

namespace Tests\Feature\Rbac;

use App\Enums\CompanyRole;
use App\Enums\PermissionDomaine;
use App\Models\Permission;
use App\Models\Role;
use App\Rbac\PermissionCatalogue;
use App\Rbac\RoleGabarits;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogueTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verbes qui modifient l'état du système.
     *
     * `export` et `view` n'en font pas partie : extraire des données n'est pas écrire.
     */
    private const VERBES_ECRITURE = [
        'create', 'update', 'delete', 'approve', 'validate', 'resolve',
        'send', 'assign', 'close', 'adjust', 'manage', 'suspend', 'activate',
        'impersonate', 'reset', 'publish', 'moderate', 'triage', 'register',
        'generate', 'open', 'sell', 'scan', 'block', 'unblock', 'cancel',
        'transfer', 'pause', 'print', 'reprint', 'hold', 'reassign', 'justify',
        'forceClose', 'markAbsent', 'deactivate', 'request', 'upload',
        'setDefault', 'setStatut', 'changeDate', 'buy', 'buyForOther',
        'initiate', 'comment', 'like', 'regenerate', 'pull', 'push', 'login',
        'verifyByPhone', 'assignUser', 'assignVehicule', 'assignChauffeur',
        'updateAdvanced', 'manageRappel', 'reply', 'report',
    ];

    public function test_le_catalogue_ne_contient_aucun_doublon(): void
    {
        $noms = PermissionCatalogue::noms();

        $this->assertSame(
            array_values(array_unique($noms)),
            $noms,
            'Le catalogue contient au moins une permission en doublon.'
        );
    }

    public function test_chaque_permission_respecte_la_nomenclature(): void
    {
        foreach (PermissionCatalogue::toutes() as $permission) {
            $this->assertMatchesRegularExpression(
                '/^[a-z]+\.[a-zA-Z]+\.[a-zA-Z]+(\.[a-z]+)?$/',
                $permission['name'],
                "La permission {$permission['name']} ne suit pas <domaine>.<ressource>.<action>."
            );

            $this->assertContains(
                $permission['domaine'],
                PermissionDomaine::values(),
                "Le domaine {$permission['domaine']} n'est pas déclaré dans PermissionDomaine."
            );

            $this->assertSame(
                explode('.', $permission['name'])[0],
                $permission['domaine'],
                "Le domaine de {$permission['name']} ne correspond pas à son premier segment."
            );
        }
    }

    public function test_chaque_permission_de_role_existe_dans_le_catalogue(): void
    {
        $connues = PermissionCatalogue::noms();
        $portees = ['all', 'compagnie', 'gare', 'own'];

        foreach (RoleGabarits::tous() as $gabarit) {
            foreach ($gabarit['permissions'] as $nom => $portee) {
                $this->assertContains(
                    $nom,
                    $connues,
                    "Le rôle {$gabarit['name']} référence la permission inconnue {$nom}."
                );

                $this->assertContains(
                    $portee,
                    $portees,
                    "Le rôle {$gabarit['name']} donne la portée invalide {$portee} à {$nom}."
                );
            }
        }
    }

    public function test_le_seeder_est_idempotent(): void
    {
        $this->seedRbac();

        $permissions = Permission::count();
        $roles = Role::count();
        $pivots = \DB::table('permission_role')->count();

        $this->seedRbac();

        $this->assertSame($permissions, Permission::count());
        $this->assertSame($roles, Role::count());
        $this->assertSame($pivots, \DB::table('permission_role')->count());
    }

    public function test_dix_neuf_roles_gabarits_sont_crees(): void
    {
        $this->seedRbac();

        foreach (RoleGabarits::noms() as $nom) {
            $role = Role::where('name', $nom)->whereNull('compagnie_id')->first();

            $this->assertNotNull($role, "Le rôle gabarit {$nom} est absent.");
            $this->assertTrue($role->is_system, "Le rôle gabarit {$nom} devrait être système.");
        }

        $this->assertCount(19, RoleGabarits::noms());
    }

    public function test_les_roles_historiques_sont_conserves(): void
    {
        $this->seedRbac();

        foreach (array_merge(['user', 'admin'], CompanyRole::values()) as $nom) {
            $this->assertDatabaseHas('roles', ['name' => $nom, 'compagnie_id' => null]);
        }
    }

    public function test_les_permissions_sensibles_sont_marquees(): void
    {
        $attendues = [
            'platform.user.impersonate',
            'platform.compagnie.suspend',
            'compagnie.role.assign',
            'compagnie.parametres.updateAdvanced',
            'guichet.ticket.cancel',
            'guichet.ticket.reprint',
            'guichet.ticket.unblock',
            'caisse.session.forceClose',
            'caisse.ecart.validate',
            'finance.recette.delete',
            'finance.depense.delete',
            'finance.depense.approve',
            'finance.remboursement.approve',
            'crm.fidelite.adjust',
            'embarquement.conflit.resolve',
        ];

        $sensibles = PermissionCatalogue::sensibles();
        sort($attendues);
        sort($sensibles);

        $this->assertSame($attendues, $sensibles);

        $this->seedRbac();

        $this->assertSame(
            count($attendues),
            Permission::where('is_sensitive', true)->count()
        );
    }

    public function test_auditeur_n_a_aucune_permission_d_ecriture(): void
    {
        $auditeur = RoleGabarits::parNom('auditeur');

        $this->assertNotNull($auditeur);
        $this->assertNotEmpty($auditeur['permissions']);

        foreach (array_keys($auditeur['permissions']) as $nom) {
            $segments = explode('.', $nom);
            $action = $segments[2] ?? '';

            $this->assertNotContains(
                $action,
                self::VERBES_ECRITURE,
                "L'auditeur porte la permission d'écriture {$nom} : la lecture scellée n'est plus garantie."
            );
        }
    }

    public function test_les_portees_gare_ne_concernent_que_des_roles_de_terrain(): void
    {
        $terrain = ['chef_gare', 'guichetier', 'agent_embarquement', 'bagagiste'];

        foreach (RoleGabarits::tous() as $gabarit) {
            foreach ($gabarit['permissions'] as $nom => $portee) {
                if ($portee !== 'gare') {
                    continue;
                }

                $this->assertContains(
                    $gabarit['name'],
                    $terrain,
                    "Le rôle de siège {$gabarit['name']} reçoit {$nom} en portée gare : "
                    .'sans affectation de gare, il ne verrait rien.'
                );
            }
        }
    }

    private function seedRbac(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }
}
