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
        Schema::create('chatbot_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('question');
            $table->text('answer');
            $table->foreignId('matched_article_id')->nullable()->constrained('help_articles')->nullOnDelete();
            $table->string('source', 50)->default('knowledge_base'); // 'knowledge_base', 'openai', 'fallback'
            $table->boolean('was_helpful')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_logs');
    }
};
