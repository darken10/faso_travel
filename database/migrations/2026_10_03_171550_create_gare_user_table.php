<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Affectation d'un collaborateur à une ou plusieurs gares.
     *
     * Second axe de cloisonnement, à côté de la compagnie : un guichetier de Bobo n'a pas
     * à voir les tickets de Ouaga. Sans cette table, la portée « gare » des matrices
     * d'habilitation n'a rien sur quoi s'appuyer.
     */
    public function up(): void
    {
        Schema::create('gare_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gare_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_principale')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'gare_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gare_user');
    }
};
