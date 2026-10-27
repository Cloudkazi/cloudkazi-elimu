<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Student;

$s = Student::withoutGlobalScopes()->find(16);
if ($s) {
    echo "Student 16 found:\n";
    echo "Name: {$s->first_name} {$s->last_name}\n";
    echo "School ID: {$s->school_id}\n";
    echo "Class ID: {$s->school_class_id}\n";
    echo "Stream ID: " . var_export($s->stream_id, true) . "\n";
    echo "Status: {$s->status}\n";
} else {
    echo "Student 16 not found.\n";
}
