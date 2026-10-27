<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AcademicYear;
use App\Models\AcademicTerm;

$years = AcademicYear::withoutGlobalScopes()->with('terms')->get();
foreach ($years as $y) {
    echo "Year {$y->id} (School {$y->school_id}): {$y->name}, Current: " . ($y->is_current ? 'YES' : 'NO') . "\n";
    foreach ($y->terms as $t) {
        echo "   -> Term {$t->id}: {$t->name}, Current: " . ($t->is_current ? 'YES' : 'NO') . "\n";
    }
}
