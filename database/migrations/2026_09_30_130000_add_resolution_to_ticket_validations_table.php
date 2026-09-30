<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traitement des validations refusées par l'administration et la finance.
 *
 * Quand une validation faite hors ligne est refusée à la synchronisation, le
 * passager est déjà dans le bus. Le refus doit donc être instruit : constaté,
 * classé ou régularisé, par qui et quand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_validations', function (Blueprint $table) {
            $table->string('resolution', 24)->nullable()->after('error_code');
            $table->text('resolution_note')->nullable()->after('resolution');
            $table->foreignId('resolved_by_id')->nullable()->after('resolution_note')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('resolved_by_id');

            // Alimente la file des conflits ouverts et son compteur.
            $table->index(['result', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::table('ticket_validations', function (Blueprint $table) {
            $table->dropIndex(['result', 'resolved_at']);
            $table->dropConstrainedForeignId('resolved_by_id');
            $table->dropColumn(['resolution', 'resolution_note', 'resolved_at']);
        });
    }
};
