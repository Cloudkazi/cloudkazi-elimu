<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$tables = [
    'teacher_subject_allocations',
    'student_attendances',
    'exam_mark844s',
    'exam_marks_844',
    'cbc_assessments',
    'examinations',
    'subjects',
    'school_classes',
    'streams',
    'students',
    'academic_years',
    'academic_terms'
];

foreach ($tables as $t) {
    if (!Schema::hasTable($t)) {
        echo "Table does NOT exist: $t\n";
        continue;
    }
    echo "=== TABLE: $t ===\n";
    $columns = Schema::getColumnListing($t);
    echo "Columns: " . implode(', ', $columns) . "\n";
    
    // Check indexes
    try {
        $indexes = DB::select("SHOW INDEX FROM `$t`");
        $idxArr = [];
        foreach ($indexes as $idx) {
            $unique = $idx->Non_unique == 0 ? ' [UNIQUE]' : '';
            $idxArr[$idx->Key_name . $unique][] = $idx->Column_name;
        }
        echo "Indexes: " . json_encode($idxArr, JSON_PRETTY_PRINT) . "\n";
    } catch (\Exception $e) {
        echo "Indexes error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
