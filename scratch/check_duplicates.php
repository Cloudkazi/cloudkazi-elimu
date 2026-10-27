<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TeacherSubjectAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== TEACHER SUBJECT ALLOCATIONS ===\n";
echo "Total allocations: " . TeacherSubjectAllocation::withoutGlobalScopes()->count() . "\n";

$allocations = TeacherSubjectAllocation::withoutGlobalScopes()->get();
foreach ($allocations as $a) {
    echo "ID: {$a->id}, School: {$a->school_id}, Teacher: {$a->teacher_id}, Subject: {$a->subject_id}, Class: {$a->school_class_id}, Stream: {$a->stream_id}\n";
}

$dupes = DB::table('teacher_subject_allocations')
    ->select('school_id', 'teacher_id', 'subject_id', 'school_class_id', 'stream_id', DB::raw('count(*) as count'), DB::raw('GROUP_CONCAT(id) as ids'))
    ->groupBy('school_id', 'teacher_id', 'subject_id', 'school_class_id', 'stream_id')
    ->having('count', '>', 1)
    ->get();

echo "Duplicate groups count: " . $dupes->count() . "\n";
foreach ($dupes as $d) {
    echo "Dupe group: School {$d->school_id}, Teacher {$d->teacher_id}, Subject {$d->subject_id}, Class {$d->school_class_id}, Stream {$d->stream_id} -> Count: {$d->count}, IDs: {$d->ids}\n";
}

echo "\n=== ATTENDANCES AUDIT ===\n";
echo "Total attendances: " . \App\Models\StudentAttendance::withoutGlobalScopes()->count() . "\n";
$attendances = \App\Models\StudentAttendance::withoutGlobalScopes()->get();
foreach ($attendances as $att) {
    echo "ID: {$att->id}, School: {$att->school_id}, Student: {$att->student_id}, Stream: {$att->stream_id}, Date: {$att->attendance_date}, Status: {$att->status}\n";
}
