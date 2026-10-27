<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add academic pathway to streams table if not present
        if (Schema::hasTable('streams') && !Schema::hasColumn('streams', 'pathway')) {
            Schema::table('streams', function (Blueprint $table) {
                $table->enum('pathway', ['general', 'STEM', 'Social Sciences', 'Arts & Sports'])
                    ->default('general')
                    ->after('name');
            });
        }

        // 2. Create subject_offerings table
        if (!Schema::hasTable('subject_offerings')) {
            Schema::create('subject_offerings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('academic_year_id')->nullable();
                $table->unsignedBigInteger('term_id')->nullable();
                $table->unsignedBigInteger('school_class_id');
                $table->unsignedBigInteger('stream_id')->nullable();
                $table->enum('pathway', ['general', 'STEM', 'Social Sciences', 'Arts & Sports'])->default('general');
                $table->unsignedBigInteger('subject_id');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('school_id');
                $table->index('school_class_id');
                $table->index('stream_id');
                $table->index('subject_id');
                $table->index(['school_id', 'school_class_id']);
                $table->index(['school_id', 'academic_year_id', 'term_id']);
            });

            // Virtual generated columns for NULL-safe composite unique constraint
            // In MySQL/MariaDB, multiple NULLs are normally allowed by UNIQUE index.
            // Using virtual columns that treat NULL as 0 ensures strict uniqueness.
            DB::statement("ALTER TABLE `subject_offerings` ADD COLUMN `v_stream_id` INT GENERATED ALWAYS AS (COALESCE(`stream_id`, 0)) VIRTUAL");
            DB::statement("ALTER TABLE `subject_offerings` ADD COLUMN `v_year_id` INT GENERATED ALWAYS AS (COALESCE(`academic_year_id`, 0)) VIRTUAL");
            DB::statement("ALTER TABLE `subject_offerings` ADD COLUMN `v_term_id` INT GENERATED ALWAYS AS (COALESCE(`term_id`, 0)) VIRTUAL");

            DB::statement("ALTER TABLE `subject_offerings` ADD UNIQUE KEY `subject_offering_unique` (
                `school_id`, `v_year_id`, `v_term_id`, `school_class_id`, `v_stream_id`, `subject_id`
            )");
        }

        // 3. Add subject_offering_id to teacher_subject_allocations
        if (Schema::hasTable('teacher_subject_allocations') && !Schema::hasColumn('teacher_subject_allocations', 'subject_offering_id')) {
            Schema::table('teacher_subject_allocations', function (Blueprint $table) {
                $table->unsignedBigInteger('subject_offering_id')->nullable()->after('subject_id');
                $table->index('subject_offering_id');
            });
        }

        // 4. Add subject_offering_id to exam_marks_844 and cbc_assessments
        if (Schema::hasTable('exam_marks_844') && !Schema::hasColumn('exam_marks_844', 'subject_offering_id')) {
            Schema::table('exam_marks_844', function (Blueprint $table) {
                $table->unsignedBigInteger('subject_offering_id')->nullable()->after('subject_id');
                $table->index('subject_offering_id');
            });
        }

        if (Schema::hasTable('cbc_assessments') && !Schema::hasColumn('cbc_assessments', 'subject_offering_id')) {
            Schema::table('cbc_assessments', function (Blueprint $table) {
                $table->unsignedBigInteger('subject_offering_id')->nullable()->after('subject_id');
                $table->index('subject_offering_id');
            });
        }

        // 5. Non-destructively backfill subject_offerings from existing active teacher allocations
        $allocations = DB::table('teacher_subject_allocations')
            ->where('is_active', 1)
            ->get();

        foreach ($allocations as $alloc) {
            // Determine stream pathway if stream exists
            $streamPathway = 'general';
            if ($alloc->stream_id) {
                $st = DB::table('streams')->where('id', $alloc->stream_id)->first();
                if ($st && !empty($st->pathway)) {
                    $streamPathway = $st->pathway;
                }
            }

            // Also check subject's pathway if defined
            $sub = DB::table('subjects')->where('id', $alloc->subject_id)->first();
            $offeringPathway = ($streamPathway !== 'general') ? $streamPathway : ($sub->pathway ?? 'general');

            // Find or insert SubjectOffering
            $existingOffering = DB::table('subject_offerings')
                ->where('school_id', $alloc->school_id)
                ->where('school_class_id', $alloc->school_class_id)
                ->where('subject_id', $alloc->subject_id)
                ->where(function ($q) use ($alloc) {
                    if (is_null($alloc->stream_id)) {
                        $q->whereNull('stream_id');
                    } else {
                        $q->where('stream_id', $alloc->stream_id);
                    }
                })
                ->where(function ($q) use ($alloc) {
                    if (is_null($alloc->academic_year_id)) {
                        $q->whereNull('academic_year_id');
                    } else {
                        $q->where('academic_year_id', $alloc->academic_year_id);
                    }
                })
                ->where(function ($q) use ($alloc) {
                    if (is_null($alloc->term_id)) {
                        $q->whereNull('term_id');
                    } else {
                        $q->where('term_id', $alloc->term_id);
                    }
                })
                ->first();

            if (!$existingOffering) {
                $offeringId = DB::table('subject_offerings')->insertGetId([
                    'school_id'        => $alloc->school_id,
                    'academic_year_id' => $alloc->academic_year_id,
                    'term_id'          => $alloc->term_id,
                    'school_class_id'  => $alloc->school_class_id,
                    'stream_id'        => $alloc->stream_id,
                    'pathway'          => $offeringPathway,
                    'subject_id'       => $alloc->subject_id,
                    'is_active'        => 1,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            } else {
                $offeringId = $existingOffering->id;
            }

            // Link allocation to subject offering
            DB::table('teacher_subject_allocations')
                ->where('id', $alloc->id)
                ->update(['subject_offering_id' => $offeringId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cbc_assessments') && Schema::hasColumn('cbc_assessments', 'subject_offering_id')) {
            Schema::table('cbc_assessments', function (Blueprint $table) {
                $table->dropColumn('subject_offering_id');
            });
        }

        if (Schema::hasTable('exam_marks_844') && Schema::hasColumn('exam_marks_844', 'subject_offering_id')) {
            Schema::table('exam_marks_844', function (Blueprint $table) {
                $table->dropColumn('subject_offering_id');
            });
        }

        if (Schema::hasTable('teacher_subject_allocations') && Schema::hasColumn('teacher_subject_allocations', 'subject_offering_id')) {
            Schema::table('teacher_subject_allocations', function (Blueprint $table) {
                $table->dropColumn('subject_offering_id');
            });
        }

        Schema::dropIfExists('subject_offerings');

        if (Schema::hasTable('streams') && Schema::hasColumn('streams', 'pathway')) {
            Schema::table('streams', function (Blueprint $table) {
                $table->dropColumn('pathway');
            });
        }
    }
};
