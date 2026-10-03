<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('compagnie_id')->nullable();
        });

        DB::statement("UPDATE role_user
            INNER JOIN roles ON roles.id = role_user.role_id
            INNER JOIN users ON users.id = role_user.user_id
            SET role_user.compagnie_id = users.compagnie_id
            WHERE roles.scope = 'compagnie'");

        DB::statement('ALTER TABLE role_user
            ADD COLUMN compagnie_key BIGINT UNSIGNED
            AS (COALESCE(compagnie_id, 0)) STORED');
        DB::statement('ALTER TABLE role_user ADD UNIQUE KEY role_user_user_role_compagnie_unique (user_id, role_id, compagnie_key)');

        Schema::table('role_user', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table) {
            $table->unique(['user_id', 'role_id']);
            $table->dropUnique('role_user_user_role_compagnie_unique');
        });

        DB::statement('ALTER TABLE role_user DROP COLUMN compagnie_key');

        Schema::table('role_user', function (Blueprint $table) {
            $table->dropColumn('compagnie_id');
        });
    }
};
