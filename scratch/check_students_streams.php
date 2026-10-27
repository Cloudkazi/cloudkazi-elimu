<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;

$students = Student::withoutGlobalScopes()->get();
echo "Total students: " . $students->count() . "\n";

$noStream = $students->whereNull('stream_id');
echo "Students without stream: " . $noStream->count() . "\n";
foreach ($noStream as $s) {
    echo "ID: {$s->id}, School: {$s->school_id}, Name: {$s->first_name} {$s->last_name}, Class: {$s->school_class_id}, Stream: NULL\n";
}

$withStream = $students->whereNotNull('stream_id');
echo "Students with stream: " . $withStream->count() . "\n";
foreach ($withStream as $s) {
    echo "ID: {$s->id}, School: {$s->school_id}, Name: {$s->first_name} {$s->last_name}, Class: {$s->school_class_id}, Stream: {$s->stream_id}\n";
}
