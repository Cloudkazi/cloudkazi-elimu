<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ROLES TABLE ===\n";
$roles = DB::table('roles')->orderBy('id')->get();
foreach ($roles as $r) {
    $cols = (array) $r;
    echo json_encode($cols) . "\n";
}

echo "\n=== USERS PER ROLE ===\n";
$counts = DB::table('users')
    ->selectRaw('role_id, count(*) as total')
    ->groupBy('role_id')
    ->get();
foreach ($counts as $c) {
    $role = DB::table('roles')->find($c->role_id);
    echo "role_id={$c->role_id} slug=" . ($role->slug ?? 'N/A') . " name=" . ($role->name ?? 'N/A') . " users={$c->total}\n";
}

echo "\n=== ALL USERS IN DB ===\n";
$users = DB::table('users')->select('id', 'name', 'email', 'role_id', 'school_id')->get();
echo "Total users: " . count($users) . "\n";
foreach ($users as $u) {
    echo json_encode($u) . "\n";
}

echo "\n=== ROLES TABLE COLUMNS ===\n";
$cols = DB::getSchemaBuilder()->getColumnListing('roles');
echo implode(', ', $cols) . "\n";

echo "\n=== ROLES TABLE STRUCTURE ===\n";
foreach (DB::select('DESCRIBE roles') as $col) {
    echo $col->Field . ' [' . $col->Type . '] null=' . $col->Null . ' default=' . ($col->Default ?? 'NULL') . "\n";
}

echo "\n=== ROLES TABLE INDEXES ===\n";
foreach (DB::select('SHOW INDEXES FROM roles') as $idx) {
    echo $idx->Key_name . ' on ' . $idx->Column_name . ' unique=' . ($idx->Non_unique ? 'no' : 'YES') . "\n";
}

echo "\n=== CHECK PERMISSIONS TABLES ===\n";
$tables = DB::select('SHOW TABLES');
foreach ($tables as $t) {
    $tname = array_values((array)$t)[0];
    if (str_contains($tname, 'perm') || str_contains($tname, 'role')) {
        echo "Table: $tname\n";
    }
}


