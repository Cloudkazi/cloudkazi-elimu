<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$engine = app(\App\Services\TimetableEngineService::class);

// Check stream matrix for Grade 7 East stream
$stream = \App\Models\Stream::with('schoolClass')->where('name', 'East')->first();
echo "Stream: " . $stream->name . " (id={$stream->id}) class=" . $stream->schoolClass->name . PHP_EOL;

$matrix = $engine->getStreamTimetableMatrix(1, $stream->id);
echo "Stream matrix populated cells:" . PHP_EOL;
foreach ($matrix as $day => $periods) {
    foreach ($periods as $pNum => $entry) {
        if (!empty($entry['subject_name'])) {
            echo "  $day period $pNum: subject=" . $entry['subject_name'] . " teacher=" . ($entry['teacher_name'] ?? '?') . " is_break=" . ($entry['is_break'] ? 'yes' : 'no') . PHP_EOL;
        }
    }
}

echo PHP_EOL . "Matrix data structure for Monday period 7:" . PHP_EOL;
print_r($matrix['Monday'][7] ?? 'empty');

echo PHP_EOL . "Matrix data structure for Monday period 4 (break):" . PHP_EOL;
print_r($matrix['Monday'][4] ?? 'empty');
