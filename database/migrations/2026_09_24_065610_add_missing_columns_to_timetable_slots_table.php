<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing columns referenced by TimetableEngineService:
     *  - is_teaching_period  (bool, default true)
     *  - type                (string: lesson|break, default lesson)
     *  - duration_minutes    (int, default 45)
     */
    public function up(): void
    {
        Schema::table('timetable_slots', function (Blueprint $table) {
            if (!Schema::hasColumn('timetable_slots', 'is_teaching_period')) {
                $table->boolean('is_teaching_period')->default(true)->after('is_break');
            }
            if (!Schema::hasColumn('timetable_slots', 'type')) {
                $table->string('type', 20)->default('lesson')->after('is_teaching_period');
            }
            if (!Schema::hasColumn('timetable_slots', 'duration_minutes')) {
                $table->integer('duration_minutes')->default(45)->after('type');
            }
        });

        // Back-fill existing rows so the engine logic is correct
        // Break slots → is_teaching_period=false, type='break'
        // Teaching slots → is_teaching_period=true, type='lesson'
        if (\DB::getDriverName() === 'sqlite') {
            \DB::statement("UPDATE timetable_slots SET is_teaching_period = 0, `type` = 'break', duration_minutes = 45 WHERE is_break = 1");
            \DB::statement("UPDATE timetable_slots SET is_teaching_period = 1, `type` = 'lesson', duration_minutes = 45 WHERE is_break = 0");
        } else {
            \DB::statement("UPDATE timetable_slots SET is_teaching_period = 0, `type` = 'break', duration_minutes = TIMESTAMPDIFF(MINUTE, start_time, end_time) WHERE is_break = 1");
            \DB::statement("UPDATE timetable_slots SET is_teaching_period = 1, `type` = 'lesson', duration_minutes = TIMESTAMPDIFF(MINUTE, start_time, end_time) WHERE is_break = 0");
        }
    }

    public function down(): void
    {
        Schema::table('timetable_slots', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['is_teaching_period', 'type', 'duration_minutes'],
                fn ($col) => Schema::hasColumn('timetable_slots', $col)
            ));
        });
    }
};
