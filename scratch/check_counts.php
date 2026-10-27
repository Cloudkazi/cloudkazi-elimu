<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Students count: " . App\Models\Student::withoutGlobalScopes()->count() . "\n";
echo "Teachers count: " . App\Models\Teacher::withoutGlobalScopes()->count() . "\n";
echo "Classes count: " . App\Models\SchoolClass::withoutGlobalScopes()->count() . "\n";
echo "Schools count: " . App\Models\School::count() . "\n";
foreach (App\Models\School::all() as $s) {
    echo "School {$s->id}: {$s->name} (status: {$s->status}, code: {$s->code})\n";
}
