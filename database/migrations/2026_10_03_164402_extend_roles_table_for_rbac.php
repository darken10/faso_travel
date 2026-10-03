<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedBigInteger('compagnie_id')->nullable()->index();
            $table->enum('scope', ['platform', 'compagnie'])->default('compagnie');
            $table->smallInteger('rang')->default(0);
            $table->boolean('is_system')->default(false);
            $table->text('description')->nullable();
        });

        DB::table('roles')
            ->whereIn('name', ['user', 'admin', 'root', 'company_admin', 'agent', 'bagagiste', 'comptabilite', 'rh', 'caisse'])
            ->update(['is_system' => true]);

        DB::table('roles')
            ->whereIn('name', ['user', 'admin', 'root'])
            ->update(['scope' => 'platform']);

        DB::statement('ALTER TABLE roles
            ADD COLUMN compagnie_key BIGINT UNSIGNED
            AS (COALESCE(compagnie_id, 0)) STORED');
        DB::statement('ALTER TABLE roles DROP INDEX roles_name_unique');
        DB::statement('ALTER TABLE roles ADD UNIQUE KEY roles_name_compagnie_unique (name, compagnie_key)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE roles DROP INDEX roles_name_compagnie_unique');
        DB::statement('ALTER TABLE roles DROP COLUMN compagnie_key');

        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['compagnie_id']);
            $table->dropColumn(['compagnie_id', 'scope', 'rang', 'is_system', 'description']);
            $table->unique('name');
        });
    }
};
