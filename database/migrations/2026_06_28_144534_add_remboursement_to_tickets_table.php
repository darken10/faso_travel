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
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('rembourse_at')->nullable();
            $table->unsignedBigInteger('rembourse_par_id')->nullable();
            $table->unsignedBigInteger('rembourse_montant')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['rembourse_at', 'rembourse_par_id', 'rembourse_montant']);
        });
    }
};
