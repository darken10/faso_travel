<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Support pour conversations avec l'équipe Liptra (compagnie_id nullable)
            $table->string('type')->default('company')->after('status'); // company | support
            $table->timestamp('last_message_at')->nullable()->after('type');
            $table->string('last_message', 100)->nullable()->after('last_message_at');
            $table->unsignedInteger('unread_count_client')->default(0)->after('last_message');
            $table->unsignedInteger('unread_count_agent')->default(0)->after('unread_count_client');

            // Rendre compagnie_id nullable pour les conversations support
            $table->foreignId('compagnie_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['type', 'last_message_at', 'last_message', 'unread_count_client', 'unread_count_agent']);
            $table->foreignId('compagnie_id')->nullable(false)->change();
        });
    }
};
