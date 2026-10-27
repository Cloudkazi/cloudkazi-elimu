<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// Let's test the steps safely on a dummy table
try {
    DB::statement("DROP TABLE IF EXISTS test_migration_mock");
    DB::statement("CREATE TABLE test_migration_mock (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        school_id BIGINT UNSIGNED NOT NULL,
        teacher_id BIGINT UNSIGNED NOT NULL,
        subject_id BIGINT UNSIGNED NOT NULL,
        school_class_id BIGINT UNSIGNED NOT NULL,
        stream_id BIGINT UNSIGNED NULL,
        lessons_per_week INT DEFAULT 5,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    )");

    // Insert dummy duplicates
    DB::statement("INSERT INTO test_migration_mock (id, school_id, teacher_id, subject_id, school_class_id, stream_id) VALUES (8, 37, 12, 40, 28, NULL)");
    DB::statement("INSERT INTO test_migration_mock (id, school_id, teacher_id, subject_id, school_class_id, stream_id) VALUES (14, 37, 12, 40, 28, NULL)");

    // Step 1: Add columns
    Schema::table('test_migration_mock', function (Blueprint $table) {
        $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
        $table->foreignId('term_id')->nullable()->constrained('academic_terms')->nullOnDelete();
        $table->boolean('is_active')->default(true);
    });
    echo "Step 1: Columns added.\n";

    // Step 2: Mark duplicates inactive
    $duplicates = DB::table('test_migration_mock')
        ->select('school_id', 'teacher_id', 'subject_id', 'school_class_id', 'stream_id', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as total'))
        ->groupBy('school_id', 'teacher_id', 'subject_id', 'school_class_id', 'stream_id')
        ->having('total', '>', 1)
        ->get();

    foreach ($duplicates as $dupe) {
        DB::table('test_migration_mock')
            ->where('school_id', $dupe->school_id)
            ->where('teacher_id', $dupe->teacher_id)
            ->where('subject_id', $dupe->subject_id)
            ->where('school_class_id', $dupe->school_class_id)
            ->when($dupe->stream_id, fn($q) => $q->where('stream_id', $dupe->stream_id), fn($q) => $q->whereNull('stream_id'))
            ->where('id', '!=', $dupe->keep_id)
            ->update(['is_active' => false]);
    }
    echo "Step 2: Duplicates marked inactive.\n";

    // Step 3: Add virtual columns and unique key
    DB::statement("ALTER TABLE test_migration_mock ADD COLUMN active_stream_scope INT GENERATED ALWAYS AS (IF(is_active = 1, IFNULL(stream_id, 0), NULL)) VIRTUAL");
    DB::statement("ALTER TABLE test_migration_mock ADD COLUMN scope_year INT GENERATED ALWAYS AS (IFNULL(academic_year_id, 0)) VIRTUAL");
    DB::statement("ALTER TABLE test_migration_mock ADD COLUMN scope_term INT GENERATED ALWAYS AS (IFNULL(term_id, 0)) VIRTUAL");
    DB::statement("ALTER TABLE test_migration_mock ADD UNIQUE KEY uq_test_alloc (school_id, teacher_id, subject_id, school_class_id, active_stream_scope, scope_year, scope_term)");

    echo "Step 3: Virtual columns & unique constraint created successfully!\n";

    // Check rows in test table
    $rows = DB::table('test_migration_mock')->get();
    foreach ($rows as $r) {
        echo "Row {$r->id}: is_active={$r->is_active}, active_stream_scope=" . var_export($r->active_stream_scope, true) . "\n";
    }

    DB::statement("DROP TABLE test_migration_mock");
    echo "Test table dropped cleanly.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    try { DB::statement("DROP TABLE IF EXISTS test_migration_mock"); } catch (\Exception $ignore) {}
}
