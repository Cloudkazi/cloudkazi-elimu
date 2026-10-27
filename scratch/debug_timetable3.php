<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check all timetables
$timetables = \App\Models\Timetable::with(['academicYear', 'term'])->get();
echo "All timetables:" . PHP_EOL;
foreach ($timetables as $t) {
    echo "  ID={$t->id} name='{$t->name}' status={$t->status} school_id={$t->school_id}" . PHP_EOL;
    echo "    slots=" . $t->slots->count() . " allocations=" . $t->allocations()->count() . PHP_EOL;
}

echo PHP_EOL;

// Check getActivePublished
$schoolId = \App\Models\Timetable::first()->school_id;
$active = \App\Models\Timetable::getActivePublished($schoolId);
echo "Active published timetable: " . ($active ? $active->id . " " . $active->name : "NULL") . PHP_EOL;

// Check the streams & their timetable_slot_id entries
echo PHP_EOL . "Allocation details:" . PHP_EOL;
$allocs = \App\Models\TimetableAllocation::with(['subject','teacher.user','stream.schoolClass','timetableSlot'])->get();
foreach ($allocs as $a) {
    $slot = $a->timetableSlot;
    echo "  Alloc #{$a->id}: subject=" . ($a->subject->name ?? 'NULL') . 
         " teacher=" . ($a->teacher->user->name ?? 'NULL') .
         " slot_id={$a->timetable_slot_id}" .
         " slot_day=" . ($slot->day_of_week ?? 'NULL') .
         " slot_period=" . ($slot->period_number ?? 'NULL') .
         " stream=" . ($a->stream->name ?? 'NULL') .
         " class=" . ($a->stream->schoolClass->name ?? ($a->schoolClass->name ?? 'NULL')) . PHP_EOL;
}
