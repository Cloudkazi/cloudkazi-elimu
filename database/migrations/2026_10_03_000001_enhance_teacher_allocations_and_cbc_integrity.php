<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance teacher_subject_allocations
        if (Schema::hasTable('teacher_subject_allocations')) {
            Schema::table('teacher_subject_allocations', function (Blueprint $table) {
                if (!Schema::hasColumn('teacher_subject_allocations', 'academic_year_id')) {
                    $table->foreignId('academic_year_id')->nullable()->after('stream_id')->constrained('academic_years')->nullOnDelete();
                }
                if (!Schema::hasColumn('teacher_subject_allocations', 'term_id')) {
                    $table->foreignId('term_id')->nullable()->after('academic_year_id')->constrained('academic_terms')->nullOnDelete();
                }
                if (!Schema::hasColumn('teacher_subject_allocations', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('lessons_per_week');
                }
            });

            // Reconcile existing duplicates safely (retaining history, marking duplicate records inactive)
            $duplicates = DB::table('teacher_subject_allocations')
                ->select(
                    'school_id',
                    'teacher_id',
                    'subject_id',
                    'school_class_id',
                    'stream_id',
                    DB::raw('MIN(id) as keep_id'),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('school_id', 'teacher_id', 'subject_id', 'school_class_id', 'stream_id')
                ->having('total', '>', 1)
                ->get();

            foreach ($duplicates as $dupe) {
                DB::table('teacher_subject_allocations')
                    ->where('school_id', $dupe->school_id)
                    ->where('teacher_id', $dupe->teacher_id)
                    ->where('subject_id', $dupe->subject_id)
                    ->where('school_class_id', $dupe->school_class_id)
                    ->when(
                        $dupe->stream_id,
                        fn($q) => $q->where('stream_id', $dupe->stream_id),
                        fn($q) => $q->whereNull('stream_id')
                    )
                    ->where('id', '!=', $dupe->keep_id)
                    ->update(['is_active' => false]);
            }

            // Virtual generated columns for composite unique constraint (handling NULL stream, year, term, and active state)
            DB::statement("ALTER TABLE `teacher_subject_allocations` ADD COLUMN `active_stream_scope` INT GENERATED ALWAYS AS (IF(`is_active` = 1, IFNULL(`stream_id`, 0), NULL)) VIRTUAL");
            DB::statement("ALTER TABLE `teacher_subject_allocations` ADD COLUMN `scope_year` INT GENERATED ALWAYS AS (IFNULL(`academic_year_id`, 0)) VIRTUAL");
            DB::statement("ALTER TABLE `teacher_subject_allocations` ADD COLUMN `scope_term` INT GENERATED ALWAYS AS (IFNULL(`term_id`, 0)) VIRTUAL");
            DB::statement("ALTER TABLE `teacher_subject_allocations` ADD UNIQUE KEY `teacher_alloc_composite_unique` (`school_id`, `teacher_id`, `subject_id`, `school_class_id`, `active_stream_scope`, `scope_year`, `scope_term`)");
        }

        // 2. Add composite unique constraint on cbc_assessments if not already present
        if (Schema::hasTable('cbc_assessments')) {
            $hasIndex = collect(DB::select("SHOW INDEX FROM `cbc_assessments`"))
                ->pluck('Key_name')
                ->contains('cbc_assessments_unique');

            if (!$hasIndex) {
                Schema::table('cbc_assessments', function (Blueprint $table) {
                    $table->unique(
                        ['school_id', 'examination_id', 'student_id', 'subject_id'],
                        'cbc_assessments_unique'
                    );
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teacher_subject_allocations')) {
            try {
                DB::statement("ALTER TABLE `teacher_subject_allocations` DROP INDEX `teacher_alloc_composite_unique`");
            } catch (\Exception $e) {}

            try {
                DB::statement("ALTER TABLE `teacher_subject_allocations` DROP COLUMN `active_stream_scope`");
            } catch (\Exception $e) {}

            try {
                DB::statement("ALTER TABLE `teacher_subject_allocations` DROP COLUMN `scope_year`");
            } catch (\Exception $e) {}

            try {
                DB::statement("ALTER TABLE `teacher_subject_allocations` DROP COLUMN `scope_term`");
            } catch (\Exception $e) {}

            Schema::table('teacher_subject_allocations', function (Blueprint $table) {
                if (Schema::hasColumn('teacher_subject_allocations', 'term_id')) {
                    $table->dropForeign(['term_id']);
                    $table->dropColumn('term_id');
                }
                if (Schema::hasColumn('teacher_subject_allocations', 'academic_year_id')) {
                    $table->dropForeign(['academic_year_id']);
                    $table->dropColumn('academic_year_id');
                }
                if (Schema::hasColumn('teacher_subject_allocations', 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }

        if (Schema::hasTable('cbc_assessments')) {
            try {
                Schema::table('cbc_assessments', function (Blueprint $table) {
                    $table->dropUnique('cbc_assessments_unique');
                });
            } catch (\Exception $e) {}
        }
    }
};
