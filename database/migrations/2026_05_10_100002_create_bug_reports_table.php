<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['bug', 'payment', 'ticket', 'suggestion', 'other']);
            $table->string('title');
            $table->text('description');
            $table->string('screenshot_url')->nullable();
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
