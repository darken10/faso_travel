<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identité de l'opération quand le ticket n'a pas pu être résolu.
 *
 * Un agent peut confirmer hors ligne un ticket absent de son cache : le
 * téléphone n'a alors que le QR scanné, pas d'identifiant. Si le serveur ne le
 * retrouve pas non plus, il ne reste aucune trace de ce que l'agent a tenté.
 *
 * On conserve l'empreinte SHA-256 du QR, jamais le QR lui-même : c'est le secret
 * du billet, et ce journal est consultable par l'administration et la finance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_validations', function (Blueprint $table) {
            $table->char('requested_qr_hash', 64)->nullable()->after('requested_ticket_id');
            $table->index('requested_qr_hash');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_validations', function (Blueprint $table) {
            $table->dropIndex(['requested_qr_hash']);
            $table->dropColumn('requested_qr_hash');
        });
    }
};
