<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SchoolClass;
use App\Models\Stream;

$classes = SchoolClass::withoutGlobalScopes()->with('streams')->get();
foreach ($classes as $c) {
    echo "Class {$c->id} (School {$c->school_id}): {$c->name} ({$c->curriculum})\n";
    foreach ($c->streams as $str) {
        echo "   -> Stream {$str->id}: {$str->name}\n";
    }
}
