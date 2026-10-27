<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$timetable = \App\Models\Timetable::first();
if ($timetable) {
    echo 'Timetable ID: ' . $timetable->id . PHP_EOL;
    echo 'Timetable Name: ' . $timetable->name . PHP_EOL;
    echo 'Status: ' . $timetable->status . PHP_EOL;
    echo 'Slots count: ' . $timetable->slots->count() . PHP_EOL;
    echo 'Allocations count: ' . $timetable->allocations()->count() . PHP_EOL;

    $slot = $timetable->slots->first();
    if ($slot) {
        echo 'Sample slot: day=' . $slot->day_of_week . ' period=' . $slot->period_number . ' is_break=' . $slot->is_break . PHP_EOL;
    }

    $alloc = $timetable->allocations()->with(['subject','teacher.user','stream'])->first();
    if ($alloc) {
        echo 'Sample allocation: subject=' . ($alloc->subject->name ?? 'NULL') . ' teacher=' . ($alloc->teacher->user->name ?? 'NULL') . ' timetable_slot_id=' . $alloc->timetable_slot_id . PHP_EOL;

        $matchingSlot = $timetable->slots->where('id', $alloc->timetable_slot_id)->first();
        echo 'Matching slot found: ' . ($matchingSlot ? 'YES day=' . $matchingSlot->day_of_week . ' period=' . $matchingSlot->period_number : 'NO') . PHP_EOL;
    } else {
        echo 'No allocations found' . PHP_EOL;
    }

    // Check the school matrix
    $engine = app(\App\Services\TimetableEngineService::class);
    $matrix = $engine->getSchoolTimetableMatrix($timetable->id);
    $populated = 0;
    foreach ($matrix as $day => $periods) {
        foreach ($periods as $pNum => $entries) {
            if (!empty($entries) && !empty($entries[0]['subject_name'])) {
                $populated++;
                if ($populated <= 3) {
                    echo "Matrix[$day][$pNum][0][subject_name] = " . $entries[0]['subject_name'] . PHP_EOL;
                }
            }
        }
    }
    echo "Total populated cells in school matrix: $populated" . PHP_EOL;
} else {
    echo 'No timetable found' . PHP_EOL;
}
