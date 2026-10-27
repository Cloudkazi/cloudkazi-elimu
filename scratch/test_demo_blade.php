<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$demoUser = App\Models\User::where('email', 'admin@demo.com')->first();
Illuminate\Support\Facades\Auth::login($demoUser);

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$routes = ['/dashboard', '/students', '/academics/classes'];
foreach ($routes as $r) {
    $req = Illuminate\Http\Request::create($r, 'GET');
    $res = $httpKernel->handle($req);
    echo "$r: status " . $res->getStatusCode() . ", bytes " . strlen($res->getContent()) . "\n";
}
