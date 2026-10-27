<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g. 2026
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('academic_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->string('name'); // Term 1, Term 2, Term 3
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g. Grade 7, Grade 8, Grade 9, Grade 10, Form 1, Form 2, Form 3, Form 4
            $table->enum('level', ['junior_school', 'senior_school', 'secondary_844'])->default('junior_school');
            $table->enum('curriculum', ['CBC', '8-4-4'])->default('CBC');
            $table->integer('numeric_level')->default(7);
            $table->timestamps();
        });

        Schema::create('streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->string('name'); // East, West, Alpha, Simba, etc.
            $table->foreignId('class_teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('capacity')->default(45);
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('code'); // e.g. MATH, ENG, KISW, BIO, CHEM, PHY, COMP, BUSS
            $table->enum('curriculum', ['CBC', '8-4-4', 'Both'])->default('Both');
            $table->enum('pathway', ['general', 'STEM', 'Social Sciences', 'Arts & Sports'])->default('general');
            $table->boolean('is_optional')->default(false);
            $table->integer('lessons_per_week')->default(5);
            $table->timestamps();
        });

        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // Room 1, Science Lab A, Computer Lab
            $table->enum('type', ['regular', 'science_lab', 'computer_lab', 'workshop', 'music_room'])->default('regular');
            $table->integer('capacity')->default(50);
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // Sciences, Languages, Humanities, Mathematics, Technical & Applied
            $table->foreignId('hod_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('staff_no')->nullable();
            $table->string('tsc_number')->nullable();
            $table->string('id_number')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->default('Male');
            $table->string('qualification')->nullable(); // B.Ed Science, PGDE, etc.
            $table->string('designation')->default('Teacher');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->string('phone')->nullable();
            $table->string('specialization')->nullable(); // e.g. Mathematics / Physics
            $table->integer('max_weekly_lessons')->default(28);
            $table->timestamps();
        });

        Schema::create('teacher_subject_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('stream_id')->nullable()->constrained('streams')->onDelete('cascade');
            $table->integer('lessons_per_week')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subject_allocations');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('classrooms');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('streams');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('academic_terms');
        Schema::dropIfExists('academic_years');
    }
};
