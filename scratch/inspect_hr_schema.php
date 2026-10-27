<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tables = [
    'employees',
    'payroll_records',
    'payrolls',
    'leaves',
    'leave_requests',
    'leave_types',
    'leave_balances',
    'teachers',
    'users',
    'roles',
    'permissions',
    'staff',
];

foreach ($tables as $table) {
    if (Illuminate\Support\Facades\Schema::hasTable($table)) {
        echo "=== Table: $table ===\n";
        $columns = Illuminate\Support\Facades\Schema::getColumnListing($table);
        foreach ($columns as $col) {
            $type = Illuminate\Support\Facades\Schema::getColumnType($table, $col);
            echo "  - $col ($type)\n";
        }
        $count = Illuminate\Support\Facades\DB::table($table)->count();
        echo "  Row count: $count\n\n";
    } else {
        echo "=== Table: $table (DOES NOT EXIST) ===\n\n";
    }
}
