<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_rappels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('delai_valeur');          // ex: 10, 5, 2, 1
            $table->string('delai_unite')->default('jours');  // 'jours' | 'heures'
            $table->json('canaux');                           // ['email','sms','whatsapp','telegram']
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_rappels');
    }
};
