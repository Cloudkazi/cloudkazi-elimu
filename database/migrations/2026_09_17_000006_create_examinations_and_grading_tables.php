<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('academic_terms')->onDelete('cascade');
            $table->string('name'); // Opener Exam, Mid-Term Exam, End-Term Exam, KCSE Mock, CBC Formative 1
            $table->enum('curriculum_type', ['CBC', '8-4-4', 'Both'])->default('Both');
            $table->enum('exam_type', ['opener', 'midterm', 'endterm', 'mock', 'cbc_assessment'])->default('endterm');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('exam_marks_844', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('examination_id')->constrained('examinations')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->decimal('score', 5, 2); // e.g. 78.50
            $table->decimal('out_of', 5, 2)->default(100.00);
            $table->string('grade', 3)->nullable(); // A, A-, B+, B, B-, C+, C, C-, D+, D, D-, E
            $table->integer('points')->default(0); // 1 to 12
            $table->string('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['school_id', 'examination_id', 'student_id', 'subject_id'], 'exam_marks_unique');
        });

        Schema::create('cbc_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('examination_id')->constrained('examinations')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('strand'); // e.g. Numbers, Geometry, Algebra
            $table->string('sub_strand')->nullable(); // e.g. Fractions, Linear Equations
            $table->enum('achievement_level', ['EE', 'ME', 'AE', 'BE'])->default('ME'); // Exceeding, Meeting, Approaching, Below
            $table->integer('score_points')->default(3); // 4=EE, 3=ME, 2=AE, 1=BE
            $table->text('rubric_feedback')->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('student_report_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('examination_id')->constrained('examinations')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('stream_id')->nullable()->constrained('streams')->onDelete('set null');
            $table->decimal('total_score', 8, 2)->default(0);
            $table->decimal('mean_score', 5, 2)->default(0);
            $table->string('mean_grade', 3)->nullable();
            $table->decimal('mean_points', 4, 2)->default(0);
            $table->integer('stream_rank')->nullable();
            $table->integer('class_rank')->nullable();
            $table->text('class_teacher_remarks')->nullable();
            $table->text('principal_remarks')->nullable();
            $table->string('pathway_recommendation')->nullable(); // For Junior School Grade 9 -> Grade 10
            $table->timestamps();

            $table->unique(['school_id', 'examination_id', 'student_id'], 'report_summary_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_report_summaries');
        Schema::dropIfExists('cbc_assessments');
        Schema::dropIfExists('exam_marks_844');
        Schema::dropIfExists('examinations');
    }
};
