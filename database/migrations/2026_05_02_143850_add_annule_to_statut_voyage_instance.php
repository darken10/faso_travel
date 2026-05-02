<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE voyage_instances MODIFY statut ENUM('DISPONIBLE','INACTIF','RETARDE','ANNULE') NOT NULL DEFAULT 'DISPONIBLE'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE voyage_instances MODIFY statut ENUM('DISPONIBLE','INACTIF','RETARDE') NOT NULL DEFAULT 'DISPONIBLE'");
    }
};
