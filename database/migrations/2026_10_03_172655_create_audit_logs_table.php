<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Piste d'audit des actions sensibles.
     *
     * Aucune action du panneau ne laissait jusqu'ici de trace nominative : ni l'annulation
     * d'un ticket, ni la suppression d'une dépense, ni l'arbitrage d'un conflit. Sans ces
     * lignes, il n'existe aucun moyen de reconstituer qui a fait quoi.
     *
     * Volontairement sans `updated_at` ni suppression douce : une ligne d'audit ne se
     * modifie pas.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('compagnie_id')->nullable()->index();
            $table->string('action', 120)->index();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('avant')->nullable();
            $table->json('apres')->nullable();
            $table->text('motif')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['compagnie_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
