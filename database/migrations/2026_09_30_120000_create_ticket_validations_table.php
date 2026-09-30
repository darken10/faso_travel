<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des opérations de contrôle, et clé d'idempotence du batch-sync.
 *
 * Avant cette table, tickets.valider_by_id / valider_at étaient les seules
 * traces d'un contrôle : deux colonnes écrasées à chaque écriture, donc ni
 * historique, ni détection de rejeu. Or l'application agent rejoue sa file
 * d'opérations hors ligne, et TicketValidation::valider() fait passer un
 * AllerRetour en Pause + RetourSimple — statut lui-même validable. Rejouer deux
 * fois la même opération consommait donc les deux trajets d'un aller-retour.
 *
 * L'unicité sur operation_id (identifiant généré par le téléphone) rend le
 * rejeu sans effet : une opération déjà enregistrée est reconnue et renvoyée
 * telle quelle, sans réexécution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_validations', function (Blueprint $table) {
            $table->id();

            // Identifiant généré par le client avant l'envoi. C'est la clé
            // d'idempotence : il survit aux retries, timeouts et redémarrages.
            $table->string('operation_id', 64)->unique();

            // Nullable : une opération peut être refusée précisément parce que le
            // ticket est introuvable (supprimé, ou appartenant à une autre
            // compagnie). Ce refus doit rester auditable.
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();

            // Identifiant annoncé par le téléphone, conservé tel quel et sans
            // contrainte : c'est la seule trace de ce que l'agent a tenté quand
            // ticket_id n'a pas pu être résolu.
            $table->unsignedBigInteger('requested_ticket_id')->nullable();

            $table->uuid('voyage_instance_id')->nullable();
            $table->foreignId('agent_id')->constrained('users');

            // Identifie le téléphone, pour distinguer un rejeu du même appareil
            // d'un conflit entre deux agents sur le même voyage.
            $table->string('device_id', 100)->nullable();

            $table->string('action', 32);
            $table->string('method', 16)->nullable();

            /**
             * false quand l'agent a confirmé un ticket absent de son cache local :
             * il a tranché sans pouvoir vérifier. Ces opérations sont à auditer.
             */
            $table->boolean('verified_offline')->default(true);

            // Horodatage réel du scan sur le terrain. Sans lui, une opération
            // faite 3 jours plus tôt serait datée de l'instant de la synchro.
            $table->timestamp('client_created_at');

            $table->string('result', 32);
            $table->string('error_code', 48)->nullable();

            $table->timestamps();

            $table->index(['ticket_id', 'action']);
            $table->index('voyage_instance_id');
            $table->index(['agent_id', 'created_at']);
            // Alimente l'écran de suivi des conflits (admin + finance).
            $table->index(['result', 'error_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_validations');
    }
};
