<?php

namespace Tests\Feature\Rbac;

use App\Models\Compagnie\Compagnie;
use App\Models\Role;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchemaRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_tables_rbac_existent_avec_leurs_colonnes(): void
    {
        $this->assertTrue(Schema::hasColumns('roles', [
            'compagnie_id',
            'scope',
            'rang',
            'is_system',
            'description',
            'compagnie_key',
        ]));
        $this->assertTrue(Schema::hasColumns('permissions', [
            'name',
            'domaine',
            'label',
            'description',
            'scope',
            'is_sensitive',
        ]));
        $this->assertTrue(Schema::hasColumns('permission_role', ['role_id', 'permission_id', 'portee']));
        $this->assertTrue(Schema::hasColumns('permission_user', ['user_id', 'permission_id', 'accordee', 'expire_at']));
        $this->assertTrue(Schema::hasColumns('role_user', ['compagnie_id', 'compagnie_key']));
    }

    public function test_deux_roles_systeme_homonymes_sont_refuses(): void
    {
        Role::create(['name' => 'superviseur', 'scope' => 'platform']);

        $this->expectException(QueryException::class);

        Role::create(['name' => 'superviseur', 'scope' => 'platform']);
    }

    public function test_un_role_compagnie_peut_porter_le_meme_nom_dans_deux_compagnies(): void
    {
        $premiere = Compagnie::factory()->create();
        $seconde = Compagnie::factory()->create();

        Role::create([
            'name' => 'superviseur',
            'compagnie_id' => $premiere->id,
        ]);
        Role::create([
            'name' => 'superviseur',
            'compagnie_id' => $seconde->id,
        ]);

        $this->assertSame(2, Role::where('name', 'superviseur')->count());
    }

    public function test_rollback_restaure_le_schema_precedent(): void
    {
        // Le nombre de pas est calculé, pas écrit en dur : toute migration ajoutée après
        // celles du RBAC décalerait un compteur figé, et le test échouerait pour une
        // raison qui n'a rien à voir avec le schéma qu'il vérifie.
        $pas = DB::table('migrations')
            ->where('migration', '>=', '2026_10_03_164402_extend_roles_table_for_rbac')
            ->count();

        Artisan::call('migrate:rollback', ['--step' => $pas, '--env' => 'testing']);

        try {
            $this->assertFalse(Schema::hasTable('permissions'));
            $this->assertFalse(Schema::hasColumn('roles', 'compagnie_id'));
            $this->assertFalse(Schema::hasColumn('role_user', 'compagnie_id'));
        } finally {
            Artisan::call('migrate', ['--env' => 'testing']);
        }

        $this->assertTrue(Schema::hasTable('permissions'));
    }
}
