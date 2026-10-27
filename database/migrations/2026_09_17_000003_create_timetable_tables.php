<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('academic_terms')->onDelete('cascade');
            $table->string('name'); // e.g. "Term 1 Master Timetable"
            $table->enum('type', ['class', 'exam'])->default('class');
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('timetable_id')->constrained('timetables')->onDelete('cascade');
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
            $table->integer('period_number'); // 1 to 9
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_break')->default(false);
            $table->string('break_label')->nullable(); // Assembly, Tea Break, Lunch Break, Games
            $table->timestamps();
        });

        Schema::create('timetable_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('timetable_id')->constrained('timetables')->onDelete('cascade');
            $table->foreignId('timetable_slot_id')->constrained('timetable_slots')->onDelete('cascade');
            $table->foreignId('stream_id')->constrained('streams')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->onDelete('set null');
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_allocations');
        Schema::dropIfExists('timetable_slots');
        Schema::dropIfExists('timetables');
    }
};
