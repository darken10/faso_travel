<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index nécessaires au delta sync de l'application agent.
 *
 * - (voyage_instance_id, updated_at) : le pull incrémental filtre les tickets
 *   d'un voyage modifiés depuis un horodatage, et pagine en keyset sur
 *   updated_at. Sans index, chaque synchronisation d'agent balayait la table.
 *
 * - code_qr : la vérification en ligne d'un ticket scanné fait
 *   where('code_qr', ...) et n'avait aucun index — un scan de table complet à
 *   chaque passager embarqué. L'index n'est pas unique : la colonne est
 *   alimentée par un aléatoire de 192 bits, mais rien n'a jamais garanti
 *   l'unicité en base et d'anciennes lignes pourraient la violer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->index(['voyage_instance_id', 'updated_at'], 'tickets_instance_updated_idx');
            $table->index('code_qr', 'tickets_code_qr_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_instance_updated_idx');
            $table->dropIndex('tickets_code_qr_idx');
        });
    }
};
