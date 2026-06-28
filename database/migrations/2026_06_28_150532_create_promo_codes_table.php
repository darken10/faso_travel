<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compagnie_id')->constrained('compagnies')->cascadeOnDelete();
            $table->string('code');
            $table->enum('type', ['pourcentage', 'montant'])->default('pourcentage');
            $table->unsignedInteger('valeur'); // % (1-100) ou montant fixe en XOF
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->unsignedInteger('usage_limit')->nullable(); // nombre max d'utilisations
            $table->unsignedInteger('used_count')->default(0);
            $table->unsignedInteger('min_montant')->nullable(); // montant d'achat minimum
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['compagnie_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
