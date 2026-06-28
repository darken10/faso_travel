<?php

use App\Models\Compagnie\Care;
use App\Models\Compagnie\Chauffer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            // Période de validité : date d'entrée en vigueur (défaut = aujourd'hui),
            // date de fin (null = illimité).
            $table->date('date_debut')->nullable()->after('days');
            $table->date('date_fin')->nullable()->after('date_debut');

            // Véhicule et chauffeur par défaut, utilisés par l'affectation auto.
            $table->foreignIdFor(Care::class)->nullable()->after('date_fin')->constrained()->nullOnDelete();
            $table->foreignIdFor(Chauffer::class)->nullable()->after('care_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Care::class);
            $table->dropConstrainedForeignIdFor(Chauffer::class);
            $table->dropColumn(['date_debut', 'date_fin']);
        });
    }
};
