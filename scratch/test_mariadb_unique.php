<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement("CREATE TABLE test_virtual_unique (
        id INT AUTO_INCREMENT PRIMARY KEY,
        school_id INT NOT NULL,
        teacher_id INT NOT NULL,
        subject_id INT NOT NULL,
        class_id INT NOT NULL,
        stream_id INT NULL,
        stream_scope INT GENERATED ALWAYS AS (IFNULL(stream_id, 0)) VIRTUAL,
        UNIQUE KEY uq_alloc (school_id, teacher_id, subject_id, class_id, stream_scope)
    )");
    echo "Virtual column unique index created successfully!\n";
    
    DB::statement("INSERT INTO test_virtual_unique (school_id, teacher_id, subject_id, class_id, stream_id) VALUES (1, 2, 3, 4, NULL)");
    echo "First insert with NULL succeeded.\n";
    
    try {
        DB::statement("INSERT INTO test_virtual_unique (school_id, teacher_id, subject_id, class_id, stream_id) VALUES (1, 2, 3, 4, NULL)");
        echo "Second insert with NULL SUCCEEDED (NOT blocked by unique index).\n";
    } catch (\Exception $e) {
        echo "Second insert with NULL BLOCKED: " . $e->getMessage() . "\n";
    }

    try {
        DB::statement("INSERT INTO test_virtual_unique (school_id, teacher_id, subject_id, class_id, stream_id) VALUES (1, 2, 3, 4, 10)");
        echo "Insert with stream_id=10 succeeded.\n";
    } catch (\Exception $e) {
        echo "Insert stream_id=10 failed: " . $e->getMessage() . "\n";
    }

    try {
        DB::statement("INSERT INTO test_virtual_unique (school_id, teacher_id, subject_id, class_id, stream_id) VALUES (1, 2, 3, 4, 10)");
        echo "Second insert stream_id=10 SUCCEEDED (NOT blocked).\n";
    } catch (\Exception $e) {
        echo "Second insert stream_id=10 BLOCKED: " . $e->getMessage() . "\n";
    }
    
    DB::statement("DROP TABLE test_virtual_unique");
    echo "Test table dropped successfully.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    try { DB::statement("DROP TABLE IF EXISTS test_virtual_unique"); } catch (\Exception $ignore) {}
}
