<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teacher_availabilities')) {
            Schema::create('teacher_availabilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
                $table->json('available_days')->nullable();
                $table->json('available_periods')->nullable();
                $table->json('blocked_periods')->nullable();
                $table->integer('max_workload_per_day')->default(6);
                $table->integer('max_workload_per_week')->default(30);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('room_types')) {
            Schema::create('room_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('room_type_id')->nullable()->constrained('room_types')->onDelete('set null');
                $table->string('name');
                $table->integer('capacity')->default(40);
                $table->boolean('is_available')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('subject_rules')) {
            Schema::create('subject_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
                $table->integer('lessons_per_week')->default(4);
                $table->boolean('requires_double_lesson')->default(false);
                $table->json('preferred_periods')->nullable();
                $table->boolean('avoid_last_lesson')->default(false);
                $table->string('room_requirement')->nullable();
                $table->integer('priority_level')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('timetable_generation_settings')) {
            Schema::create('timetable_generation_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('timetable_id')->nullable()->constrained('timetables')->onDelete('cascade');
                $table->integer('max_lessons_per_teacher_per_day')->default(6);
                $table->integer('max_consecutive_lessons_per_teacher')->default(2);
                $table->integer('max_lessons_per_stream_per_day')->default(6);
                $table->boolean('allow_double_lessons')->default(true);
                $table->boolean('practical_subjects_consecutive')->default(false);
                $table->boolean('core_subjects_distributed')->default(true);
                $table->boolean('enforce_teacher_availability')->default(true);
                $table->boolean('enforce_room_availability')->default(true);
                $table->boolean('conflict_free_generation')->default(true);
                $table->string('preferred_scheduling_strategy')->default('Balanced');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('timetable_conflicts')) {
            Schema::create('timetable_conflicts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('timetable_id')->constrained('timetables')->onDelete('cascade');
                $table->string('type');
                $table->string('entity_type');
                $table->unsignedBigInteger('entity_id');
                $table->string('day_of_week')->nullable();
                $table->integer('period_number')->nullable();
                $table->text('message')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('timetable_versions')) {
            Schema::create('timetable_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('timetable_id')->constrained('timetables')->onDelete('cascade');
                $table->string('version_label')->default('Draft');
                $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_versions');
        Schema::dropIfExists('timetable_conflicts');
        Schema::dropIfExists('timetable_generation_settings');
        Schema::dropIfExists('subject_rules');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('teacher_availabilities');
    }
};
