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
        if (Schema::hasTable('timetable_generation_settings')) {
            Schema::table('timetable_generation_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('timetable_generation_settings', 'school_days')) {
                    $table->json('school_days')->nullable()->after('preferred_scheduling_strategy');
                }
                if (!Schema::hasColumn('timetable_generation_settings', 'lessons_per_day')) {
                    $table->integer('lessons_per_day')->default(8)->after('preferred_scheduling_strategy');
                }
                if (!Schema::hasColumn('timetable_generation_settings', 'lesson_duration_minutes')) {
                    $table->integer('lesson_duration_minutes')->default(45)->after('preferred_scheduling_strategy');
                }
                if (!Schema::hasColumn('timetable_generation_settings', 'school_start_time')) {
                    $table->string('school_start_time', 20)->default('08:00:00')->after('preferred_scheduling_strategy');
                }
                if (!Schema::hasColumn('timetable_generation_settings', 'school_end_time')) {
                    $table->string('school_end_time', 20)->default('16:30:00')->after('preferred_scheduling_strategy');
                }
                if (!Schema::hasColumn('timetable_generation_settings', 'break_duration_minutes')) {
                    $table->integer('break_duration_minutes')->default(30)->after('preferred_scheduling_strategy');
                }
                if (!Schema::hasColumn('timetable_generation_settings', 'activity_blocks')) {
                    $table->json('activity_blocks')->nullable()->after('preferred_scheduling_strategy');
                }
                if (!Schema::hasColumn('timetable_generation_settings', 'strict_validation')) {
                    $table->boolean('strict_validation')->default(true)->after('preferred_scheduling_strategy');
                }
                if (!Schema::hasColumn('timetable_generation_settings', 'allow_incomplete_timetable')) {
                    $table->boolean('allow_incomplete_timetable')->default(false)->after('preferred_scheduling_strategy');
                }
                if (!Schema::hasColumn('timetable_generation_settings', 'generate_draft_only')) {
                    $table->boolean('generate_draft_only')->default(true)->after('preferred_scheduling_strategy');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('timetable_generation_settings')) {
            Schema::table('timetable_generation_settings', function (Blueprint $table) {
                $columns = [
                    'school_days',
                    'lessons_per_day',
                    'lesson_duration_minutes',
                    'school_start_time',
                    'school_end_time',
                    'break_duration_minutes',
                    'activity_blocks',
                    'strict_validation',
                    'allow_incomplete_timetable',
                    'generate_draft_only',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('timetable_generation_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
