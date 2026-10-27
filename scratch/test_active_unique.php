<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement("CREATE TABLE test_active_unique (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT NOT NULL,
        teacher_id INT NOT NULL,
        subject_id INT NOT NULL,
        class_id INT NOT NULL,
        stream_id INT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        active_stream_scope INT GENERATED ALWAYS AS (IF(is_active = 1, IFNULL(stream_id, 0), NULL)) VIRTUAL,
        UNIQUE KEY uq_active_alloc (school_id, teacher_id, subject_id, class_id, active_stream_scope)
    )");
    echo "Table with active_stream_scope created successfully!\n";

    // 1. Insert first active allocation (stream null)
    DB::statement("INSERT INTO test_active_unique (school_id, teacher_id, subject_id, class_id, stream_id, is_active) VALUES (1, 2, 3, 4, NULL, 1)");
    echo "1. Active allocation inserted.\n";

    // 2. Insert duplicate active allocation -> MUST FAIL
    try {
        DB::statement("INSERT INTO test_active_unique (school_id, teacher_id, subject_id, class_id, stream_id, is_active) VALUES (1, 2, 3, 4, NULL, 1)");
        echo "2. Duplicate active SUCCEEDED (UNEXPECTED!).\n";
    } catch (\Exception $e) {
        echo "2. Duplicate active BLOCKED as expected: " . $e->getMessage() . "\n";
    }

    // 3. Mark duplicate as inactive (is_active = 0) -> MUST SUCCEED (retaining history!)
    DB::statement("INSERT INTO test_active_unique (school_id, teacher_id, subject_id, class_id, stream_id, is_active) VALUES (1, 2, 3, 4, NULL, 0)");
    echo "3. Inactive duplicate inserted successfully (history preserved!).\n";

    // 4. Another inactive duplicate -> MUST SUCCEED (no unique constraint failure on inactive)
    DB::statement("INSERT INTO test_active_unique (school_id, teacher_id, subject_id, class_id, stream_id, is_active) VALUES (1, 2, 3, 4, NULL, 0)");
    echo "4. Second inactive record preserved successfully.\n";

    // 5. Test with specific stream_id (stream_id = 15)
    DB::statement("INSERT INTO test_active_unique (school_id, teacher_id, subject_id, class_id, stream_id, is_active) VALUES (1, 2, 3, 4, 15, 1)");
    echo "5. Active allocation with stream 15 inserted.\n";

    // 6. Duplicate active with stream 15 -> MUST FAIL
    try {
        DB::statement("INSERT INTO test_active_unique (school_id, teacher_id, subject_id, class_id, stream_id, is_active) VALUES (1, 2, 3, 4, 15, 1)");
        echo "6. Duplicate stream 15 SUCCEEDED (UNEXPECTED!).\n";
    } catch (\Exception $e) {
        echo "6. Duplicate stream 15 BLOCKED as expected: " . $e->getMessage() . "\n";
    }

    DB::statement("DROP TABLE test_active_unique");
    echo "Test table dropped.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    try { DB::statement("DROP TABLE IF EXISTS test_active_unique"); } catch (\Exception $ignore) {}
}
