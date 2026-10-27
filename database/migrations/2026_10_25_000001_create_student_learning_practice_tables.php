<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sequence')->default(1);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->unique(['school_id', 'school_class_id', 'subject_id', 'sequence'], 'learning_chapters_sequence_unique');
        });

        Schema::create('learning_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_chapter_id')->constrained()->cascadeOnDelete();
            $table->string('topic')->nullable();
            $table->string('section')->nullable();
            $table->text('prompt');
            $table->enum('question_type', ['multiple_choice', 'short_answer'])->default('multiple_choice');
            $table->json('options')->nullable();
            $table->text('correct_answer');
            $table->text('explanation')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->decimal('points', 6, 2)->default(1);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('learning_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_question_id')->constrained()->cascadeOnDelete();
            $table->text('answer');
            $table->boolean('is_correct');
            $table->timestamp('answered_at');
            $table->timestamps();
            $table->index(['student_id', 'learning_question_id', 'answered_at'], 'learning_attempts_student_question_answered_index');
        });

        Schema::create('learning_chapter_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_chapter_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('completed_questions_count')->default(0);
            $table->decimal('mastery_percent', 5, 2)->default(0);
            $table->decimal('completion_percent', 5, 2)->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'learning_chapter_id'], 'learning_chapter_progress_student_unique');
        });

        Schema::create('portal_announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['user_id', 'announcement_id'], 'portal_announcement_reads_user_announcement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_announcement_reads');
        Schema::dropIfExists('learning_chapter_progress');
        Schema::dropIfExists('learning_attempts');
        Schema::dropIfExists('learning_questions');
        Schema::dropIfExists('learning_chapters');
    }
};
