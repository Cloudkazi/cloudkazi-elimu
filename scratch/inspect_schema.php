<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== TABLES ===\n";
$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $vals = array_values((array)$t);
    echo $vals[0] . "\n";
}

echo "\n=== SUBJECTS TABLE SCHEMA ===\n";
foreach (DB::select('DESCRIBE subjects') as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Key: {$c->Key} | Default: {$c->Default}\n";
}

echo "\n=== STREAMS TABLE SCHEMA ===\n";
foreach (DB::select('DESCRIBE streams') as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Key: {$c->Key} | Default: {$c->Default}\n";
}

echo "\n=== SCHOOL_CLASSES TABLE SCHEMA ===\n";
foreach (DB::select('DESCRIBE school_classes') as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Key: {$c->Key} | Default: {$c->Default}\n";
}

echo "\n=== STUDENTS TABLE SCHEMA ===\n";
foreach (DB::select('DESCRIBE students') as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Key: {$c->Key} | Default: {$c->Default}\n";
}

echo "\n=== TEACHER_SUBJECT_ALLOCATIONS TABLE SCHEMA ===\n";
foreach (DB::select('DESCRIBE teacher_subject_allocations') as $c) {
    echo "{$c->Field} | {$c->Type} | Null: {$c->Null} | Key: {$c->Key} | Default: {$c->Default}\n";
}
