<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cares', function (Blueprint $table) {
            $table->string('numero', 20)->nullable()->after('immatrculation');
        });
    }

    public function down(): void
    {
        Schema::table('cares', function (Blueprint $table) {
            $table->dropColumn('numero');
        });
    }
};
