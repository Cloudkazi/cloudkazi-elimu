<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement("CREATE TABLE test_full_alloc_unique (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT NOT NULL,
        teacher_id INT NOT NULL,
        subject_id INT NOT NULL,
        school_class_id INT NOT NULL,
        stream_id INT NULL,
        academic_year_id INT NULL,
        term_id INT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        active_stream_scope INT GENERATED ALWAYS AS (IF(is_active = 1, IFNULL(stream_id, 0), NULL)) VIRTUAL,
        scope_year INT GENERATED ALWAYS AS (IFNULL(academic_year_id, 0)) VIRTUAL,
        scope_term INT GENERATED ALWAYS AS (IFNULL(term_id, 0)) VIRTUAL,
        UNIQUE KEY uq_alloc_full (school_id, teacher_id, subject_id, school_class_id, active_stream_scope, scope_year, scope_term)
    )");
    echo "Full allocation unique table created successfully!\n";

    // 1. Insert allocation with null stream, null year, null term
    DB::statement("INSERT INTO test_full_alloc_unique (school_id, teacher_id, subject_id, school_class_id, stream_id, academic_year_id, term_id) VALUES (1, 10, 20, 30, NULL, NULL, NULL)");
    echo "1. Basic insert OK.\n";

    // 2. Exact duplicate -> MUST FAIL
    try {
        DB::statement("INSERT INTO test_full_alloc_unique (school_id, teacher_id, subject_id, school_class_id, stream_id, academic_year_id, term_id) VALUES (1, 10, 20, 30, NULL, NULL, NULL)");
        echo "2. Duplicate SUCCEEDED (UNEXPECTED!)\n";
    } catch (\Exception $e) {
        echo "2. Duplicate BLOCKED: " . $e->getMessage() . "\n";
    }

    // 3. Different teacher -> MUST SUCCEED
    DB::statement("INSERT INTO test_full_alloc_unique (school_id, teacher_id, subject_id, school_class_id, stream_id, academic_year_id, term_id) VALUES (1, 11, 20, 30, NULL, NULL, NULL)");
    echo "3. Different teacher OK.\n";

    // 4. Same teacher, different stream -> MUST SUCCEED
    DB::statement("INSERT INTO test_full_alloc_unique (school_id, teacher_id, subject_id, school_class_id, stream_id, academic_year_id, term_id) VALUES (1, 10, 20, 30, 5, NULL, NULL)");
    echo "4. Different stream OK.\n";

    // 5. Same teacher, same stream, different year -> MUST SUCCEED
    DB::statement("INSERT INTO test_full_alloc_unique (school_id, teacher_id, subject_id, school_class_id, stream_id, academic_year_id, term_id) VALUES (1, 10, 20, 30, 5, 2027, NULL)");
    echo "5. Different year OK.\n";

    // 6. Inactive record -> MUST NOT CONFLICT
    DB::statement("INSERT INTO test_full_alloc_unique (school_id, teacher_id, subject_id, school_class_id, stream_id, academic_year_id, term_id, is_active) VALUES (1, 10, 20, 30, NULL, NULL, NULL, 0)");
    echo "6. Inactive duplicate inserted OK.\n";

    DB::statement("DROP TABLE test_full_alloc_unique");
    echo "Table dropped.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    try { DB::statement("DROP TABLE IF EXISTS test_full_alloc_unique"); } catch (\Exception $ignore) {}
}
