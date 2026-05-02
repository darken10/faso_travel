<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chauffers', function (Blueprint $table) {
            $table->string('matricule')->nullable()->unique()->after('last_name');
            $table->string('telephone')->nullable()->after('matricule');
            $table->string('photo')->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('chauffers', function (Blueprint $table) {
            $table->dropColumn(['matricule', 'telephone', 'photo']);
        });
    }
};
