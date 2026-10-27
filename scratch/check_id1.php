<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "Role 1: " . json_encode(Role::find(1)) . "\n";
echo "User 1: " . json_encode(User::find(1)) . "\n";
