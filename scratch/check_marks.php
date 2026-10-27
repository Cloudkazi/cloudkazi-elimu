<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExamMark844;
use App\Models\CbcAssessment;
use Illuminate\Support\Facades\DB;

echo "=== 8-4-4 MARKS ===\n";
echo "Total 8-4-4 marks: " . ExamMark844::withoutGlobalScopes()->count() . "\n";
$mDupes = DB::table('exam_marks_844')
    ->select('school_id', 'examination_id', 'student_id', 'subject_id', DB::raw('count(*) as count'))
    ->groupBy('school_id', 'examination_id', 'student_id', 'subject_id')
    ->having('count', '>', 1)
    ->get();
echo "8-4-4 duplicate groups: " . $mDupes->count() . "\n";

echo "\n=== CBC ASSESSMENTS ===\n";
echo "Total CBC assessments: " . CbcAssessment::withoutGlobalScopes()->count() . "\n";
$cDupes = DB::table('cbc_assessments')
    ->select('school_id', 'examination_id', 'student_id', 'subject_id', DB::raw('count(*) as count'))
    ->groupBy('school_id', 'examination_id', 'student_id', 'subject_id')
    ->having('count', '>', 1)
    ->get();
echo "CBC duplicate groups: " . $cDupes->count() . "\n";
foreach ($cDupes as $cd) {
    echo "CBC Dupe: School {$cd->school_id}, Exam {$cd->examination_id}, Student {$cd->student_id}, Subject {$cd->subject_id} -> Count: {$cd->count}\n";
}
