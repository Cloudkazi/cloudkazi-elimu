<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== ALLOCATIONS FOR SCHOOL 37 ===\n";
$allocs = DB::table('teacher_subject_allocations')->where('school_id', 37)->get();
foreach ($allocs as $a) {
    echo "Alloc ID: {$a->id} | Teacher: {$a->teacher_id} | Subject: {$a->subject_id} | Class: {$a->school_class_id} | Stream: {$a->stream_id} | Year: {$a->academic_year_id} | Term: {$a->term_id} | Active: {$a->is_active}\n";
}

echo "\n=== EXAMINATIONS FOR SCHOOL 37 ===\n";
$exams = DB::table('examinations')->where('school_id', 37)->get();
foreach ($exams as $e) {
    echo "Exam ID: {$e->id} | Name: {$e->name} | Year: {$e->academic_year_id} | Term: {$e->term_id} | Curric: {$e->curriculum_type} | Type: {$e->exam_type}\n";
}

echo "\n=== EXAM_MARKS_844 FOR SCHOOL 37 ===\n";
$marks844 = DB::table('exam_marks_844')->where('school_id', 37)->get();
foreach ($marks844 as $m) {
    echo "Mark ID: {$m->id} | Exam: {$m->examination_id} | Student: {$m->student_id} | Subject: {$m->subject_id} | Score: {$m->score} | OutOf: {$m->out_of} | Grade: {$m->grade}\n";
}

echo "\n=== CBC_ASSESSMENTS FOR SCHOOL 37 ===\n";
$cbcs = DB::table('cbc_assessments')->where('school_id', 37)->get();
foreach ($cbcs as $c) {
    echo "CBC ID: {$c->id} | Exam: {$c->examination_id} | Student: {$c->student_id} | Subject: {$c->subject_id} | Level: {$c->achievement_level} | Points: {$c->score_points}\n";
}

echo "\n=== STUDENT_REPORT_SUMMARIES FOR SCHOOL 37 ===\n";
$reports = DB::table('student_report_summaries')->where('school_id', 37)->get();
foreach ($reports as $r) {
    echo "Report ID: {$r->id} | Exam: {$r->examination_id} | Student: {$r->student_id} | Class: {$r->school_class_id} | Stream: {$r->stream_id} | Total: {$r->total_score} | Mean: {$r->mean_score} | Grade: {$r->mean_grade}\n";
}

echo "\n=== STUDENTS IN SCHOOL 37 ===\n";
$students = DB::table('students')->where('school_id', 37)->get();
foreach ($students as $st) {
    echo "Student ID: {$st->id} | Name: {$st->first_name} {$st->last_name} | Class: {$st->school_class_id} | Stream: {$st->stream_id} | Curric: {$st->curriculum} | Pathway: {$st->pathway}\n";
}

echo "\n=== ACADEMIC YEARS & TERMS FOR SCHOOL 37 ===\n";
$years = DB::table('academic_years')->where('school_id', 37)->get();
foreach ($years as $y) {
    echo "Year ID: {$y->id} | Name: {$y->name} | Current: {$y->is_current}\n";
    $terms = DB::table('academic_terms')->where('academic_year_id', $y->id)->get();
    foreach ($terms as $t) {
        echo "  Term ID: {$t->id} | Name: {$t->name} | Current: {$t->is_current}\n";
    }
}
