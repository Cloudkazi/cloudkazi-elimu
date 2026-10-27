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
        Schema::create('help_articles', function (Blueprint $table) {
            $table->id();
            $table->string('category', 64)->index(); // Admissions, Finance, Exams, Timetable, Teachers, Parents, Students, Reports, System Settings
            $table->string('question');
            $table->text('answer');
            $table->text('keywords')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('view_count')->default(0);
            $table->timestamps();

            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['question', 'answer', 'keywords']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_articles');
    }
};
