<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$timetable = \App\Models\Timetable::first();
$engine = app(\App\Services\TimetableEngineService::class);
$schoolId = $timetable->school_id;

// Replicate exactly what the controller does
$classes = \App\Models\SchoolClass::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->with('streams')->get();
echo "Classes found: " . $classes->count() . PHP_EOL;
foreach ($classes as $c) {
    echo "  Class: " . $c->name . " (id=" . $c->id . ")" . PHP_EOL;
}

$schoolMatrix = $engine->getSchoolTimetableMatrix($timetable->id);
echo "\nSchool matrix days: " . implode(', ', array_keys($schoolMatrix)) . PHP_EOL;

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Replicate schoolClassGroups build
$schoolClassGroups = [];
foreach ($classes as $class) {
    $schoolClassGroups[$class->name] = ['days' => []];
}

echo "\nInitial groups: " . implode(', ', array_keys($schoolClassGroups)) . PHP_EOL;

$periods = [];
$timetableSlots = $timetable->slots->where('day_of_week', 'Monday')->sortBy('period_number');
if ($timetableSlots->isEmpty()) {
    $timetableSlots = $timetable->slots->groupBy('period_number')->map->first()->sortBy('period_number');
}
foreach ($timetableSlots as $slot) {
    $periods[$slot->period_number] = [
        'time' => $slot->start_time . ' - ' . $slot->end_time,
        'name' => $slot->is_break ? ($slot->break_label ?: 'Break') : 'Lesson ' . $slot->period_number,
        'break' => (bool)$slot->is_break,
    ];
}

echo "\nPeriods: " . implode(', ', array_keys($periods)) . PHP_EOL;

foreach ($days as $day) {
    foreach ($periods as $pNum => $pMeta) {
        if (!empty($pMeta['break'])) {
            continue;
        }
        $entries = $schoolMatrix[$day][$pNum] ?? [];
        foreach ($entries as $entry) {
            $className = trim((string)($entry['class_name'] ?? ''));
            echo "Entry: day=$day period=$pNum class_name='$className' subject=" . ($entry['subject_name'] ?? 'NULL') . PHP_EOL;
            if ($className !== '') {
                $schoolClassGroups[$className]['days'][$day][$pNum][] = $entry;
            }
        }
    }
}

echo "\nFinal group lesson counts:" . PHP_EOL;
foreach ($schoolClassGroups as $cName => $cData) {
    $count = 0;
    foreach ($days as $d) {
        foreach ($periods as $pn => $pm) {
            if (!empty($pm['break'])) continue;
            if (!empty($cData['days'][$d][$pn])) $count += count($cData['days'][$d][$pn]);
        }
    }
    echo "  $cName: $count lessons" . PHP_EOL;
}
