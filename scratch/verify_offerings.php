<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SUBJECT OFFERINGS COUNT ===\n";
echo DB::table('subject_offerings')->count() . " records created.\n";

echo "\n=== SUBJECT OFFERINGS SAMPLE ===\n";
foreach (DB::table('subject_offerings')->get() as $so) {
    echo "ID: {$so->id} | School: {$so->school_id} | Class: {$so->school_class_id} | Stream: {$so->stream_id} | Pathway: {$so->pathway} | Subject: {$so->subject_id}\n";
}

echo "\n=== ALLOCATIONS WITH OFFERING ID ===\n";
foreach (DB::table('teacher_subject_allocations')->where('is_active', 1)->get() as $a) {
    echo "Alloc ID: {$a->id} | Offering ID: {$a->subject_offering_id} | Subject ID: {$a->subject_id} | Teacher ID: {$a->teacher_id}\n";
}
