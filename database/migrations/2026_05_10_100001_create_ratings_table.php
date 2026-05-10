<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(App\Models\Compagnie\Compagnie::class)->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('stars'); // 1 à 5
            $table->text('comment')->nullable();
            $table->timestamps();

            // Un utilisateur ne peut noter une compagnie qu'une seule fois
            $table->unique(['user_id', 'compagnie_id']);
            $table->index('compagnie_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
