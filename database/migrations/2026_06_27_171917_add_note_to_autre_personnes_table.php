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
            $table->text('note')->nullable()->after('lien_relation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('autre_personnes', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
