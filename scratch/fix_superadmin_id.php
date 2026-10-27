<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

DB::statement('SET FOREIGN_KEY_CHECKS=0;');

// Update super-admin role to ID 1
$superRole = DB::table('roles')->where('slug', 'super-admin')->first();
if ($superRole) {
    DB::table('roles')->where('id', $superRole->id)->update(['id' => 1]);
}

// Update superadmin user to ID 1 and role_id 1
$superUser = DB::table('users')->where('email', 'superadmin@cloudkazi.com')->first();
if ($superUser) {
    DB::table('users')->where('id', $superUser->id)->update(['id' => 1, 'role_id' => 1]);
}

DB::statement('SET FOREIGN_KEY_CHECKS=1;');

echo "Updated Super Admin User:\n";
$u = DB::table('users')->where('id', 1)->first();
print_r($u);

echo "\nUpdated Super Admin Role:\n";
$r = DB::table('roles')->where('id', 1)->first();
print_r($r);
