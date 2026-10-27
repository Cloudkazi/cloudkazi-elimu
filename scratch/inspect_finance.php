<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['fee_categories', 'fee_structures', 'student_invoices', 'payments', 'mpesa_transactions'] as $t) {
    echo "=== DATA IN: {$t} ===" . PHP_EOL;
    $rows = DB::table($t)->get();
    echo "Total rows: " . $rows->count() . PHP_EOL;
    foreach ($rows as $r) {
        echo "  " . json_encode($r) . PHP_EOL;
    }
    echo PHP_EOL;
}
