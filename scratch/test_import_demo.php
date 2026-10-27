<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$demoUser = App\Models\User::where('email', 'admin@demo.com')->first();
Illuminate\Support\Facades\Auth::login($demoUser);

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test GET /students/import (demo should see but not write)
$req = Illuminate\Http\Request::create('/students/import', 'GET');
$res = $httpKernel->handle($req);
echo "GET /students/import (demo) => Status: " . $res->getStatusCode() . "\n";

// Test GET /students/import/template
$req2 = Illuminate\Http\Request::create('/students/import/template', 'GET');
$res2 = $httpKernel->handle($req2);
echo "GET /students/import/template (demo) => Status: " . $res2->getStatusCode() . "\n";
