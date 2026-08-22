<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trace les mises en pause des billets.
 *
 * Le seul statut ne suffit pas aux rapports : il faut savoir *quand* la pause a
 * eu lieu pour la rattacher à une période, et *si* elle vient de la tâche
 * planifiée ou d'un agent, ces deux cas n'ayant pas la même lecture métier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('paused_at')->nullable()->after('valider_at');
            $table->boolean('paused_auto')->default(false)->after('paused_at');

            // Les rapports filtrent sur la date de pause, par compagnie et période.
            $table->index(['paused_at', 'paused_auto'], 'tickets_pause_index');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_pause_index');
            $table->dropColumn(['paused_at', 'paused_auto']);
        });
    }
};
