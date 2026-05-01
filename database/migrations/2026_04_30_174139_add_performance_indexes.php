<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['user_id', 'statut'], 'idx_tickets_user_statut');
            $table->index(['voyage_instance_id', 'statut'], 'idx_tickets_instance_statut');
            $table->index('transferer_a_user_id', 'idx_tickets_transfere');
            $table->index('valider_at', 'idx_tickets_valider_at');
        });

        Schema::table('voyage_instances', function (Blueprint $table) {
            $table->index(['date', 'voyage_id'], 'idx_voyage_instances_date_voyage');
        });

        Schema::table('payements', function (Blueprint $table) {
            $table->index(['ticket_id', 'statut'], 'idx_payements_ticket_statut');
        });

        Schema::table('voyages', function (Blueprint $table) {
            $table->index('compagnie_id', 'idx_voyages_compagnie');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_user_statut');
            $table->dropIndex('idx_tickets_instance_statut');
            $table->dropIndex('idx_tickets_transfere');
            $table->dropIndex('idx_tickets_valider_at');
        });

        Schema::table('voyage_instances', function (Blueprint $table) {
            $table->dropIndex('idx_voyage_instances_date_voyage');
        });

        Schema::table('payements', function (Blueprint $table) {
            $table->dropIndex('idx_payements_ticket_statut');
        });

        Schema::table('voyages', function (Blueprint $table) {
            $table->dropIndex('idx_voyages_compagnie');
        });
    }
};
