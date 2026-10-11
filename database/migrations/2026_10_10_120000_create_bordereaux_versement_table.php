<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bordereaux_versement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compagnie_id')->constrained('compagnies')->cascadeOnDelete();
            $table->foreignId('caisse_id')->unique()->constrained('caisses');
            $table->string('numero', 32)->nullable()->unique();
            $table->unsignedBigInteger('montant_attendu');
            $table->unsignedBigInteger('montant_declare');
            $table->unsignedBigInteger('montant_recu')->nullable();
            $table->unsignedBigInteger('montant_comptabilise')->nullable();
            $table->string('statut', 32)->default('emis')->index();
            $table->foreignId('emitted_by_id')->constrained('users');
            $table->foreignId('received_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transmitted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('accounted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recette_id')->nullable()->unique()->constrained('recettes')->nullOnDelete();
            $table->timestamp('emitted_at');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('transmitted_at')->nullable();
            $table->timestamp('accounted_at')->nullable();
            $table->string('reference_comptable', 100)->nullable();
            $table->text('note_emission')->nullable();
            $table->text('note_reception')->nullable();
            $table->text('note_transmission')->nullable();
            $table->text('note_comptabilite')->nullable();
            $table->timestamps();

            $table->index(['compagnie_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bordereaux_versement');
    }
};
