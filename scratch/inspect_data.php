<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== EXAMINATIONS TABLE SCHEMA ===\n";
foreach (DB::select('DESCRIBE examinations') as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Key: {$c->Key} | Default: {$c->Default}\n";
}

echo "\n=== EXAM_MARKS_844 TABLE SCHEMA ===\n";
foreach (DB::select('DESCRIBE exam_marks_844') as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Key: {$c->Key} | Default: {$c->Default}\n";
}

echo "\n=== CBC_ASSESSMENTS TABLE SCHEMA ===\n";
foreach (DB::select('DESCRIBE cbc_assessments') as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Key: {$c->Key} | Default: {$c->Default}\n";
}

echo "\n=== STUDENT_REPORT_SUMMARIES TABLE SCHEMA ===\n";
foreach (DB::select('DESCRIBE student_report_summaries') as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Key: {$c->Key} | Default: {$c->Default}\n";
}

echo "\n=== SUBJECT_RULES TABLE SCHEMA ===\n";
foreach (DB::select('DESCRIBE subject_rules') as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Key: {$c->Key} | Default: {$c->Default}\n";
}

echo "\n=== CURRENT SUBJECTS DATA ===\n";
$subs = DB::table('subjects')->get();
foreach ($subs as $s) {
    echo "ID: {$s->id} | School: {$s->school_id} | Name: {$s->name} | Code: {$s->code} | Curric: {$s->curriculum} | Pathway: {$s->pathway}\n";
}

echo "\n=== CURRENT CLASSES AND STREAMS DATA ===\n";
$classes = DB::table('school_classes')->get();
foreach ($classes as $cl) {
    echo "Class ID: {$cl->id} | School: {$cl->school_id} | Name: {$cl->name} | Level: {$cl->level} | Curric: {$cl->curriculum} | NumLevel: {$cl->numeric_level}\n";
    $streams = DB::table('streams')->where('school_class_id', $cl->id)->get();
    foreach ($streams as $st) {
        echo "  Stream ID: {$st->id} | Name: {$st->name}\n";
    }
}
