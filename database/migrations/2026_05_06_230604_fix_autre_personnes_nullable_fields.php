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
        Schema::table('autre_personnes', function (Blueprint $table) {
            // sexe n'est pas toujours connu lors d'un achat pour autrui
            $table->string('sexe')->nullable()->change();

            // last_name peut être vide (nom composé d'un seul mot)
            $table->string('last_name')->nullable()->change();

            // email non obligatoire et non unique (même passager sur plusieurs tickets)
            $table->dropUnique(['email']);
        });
    }

    public function down(): void
    {
        Schema::table('autre_personnes', function (Blueprint $table) {
            $table->string('sexe')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->unique('email');
        });
    }
};
