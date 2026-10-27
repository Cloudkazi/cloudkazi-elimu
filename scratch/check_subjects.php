<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Subject;

$subjects = Subject::withoutGlobalScopes()->get();
echo "Total subjects: " . $subjects->count() . "\n";
foreach ($subjects as $s) {
    echo "ID: {$s->id}, School: {$s->school_id}, Name: {$s->name}, Code: {$s->code}, Curriculum: {$s->curriculum}\n";
}
