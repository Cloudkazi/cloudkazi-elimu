<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$demoUser = App\Models\User::where('email', 'admin@demo.com')->first();
Illuminate\Support\Facades\Auth::login($demoUser);

$routes = [
    '/dashboard',
    '/students',
    '/academics/classes',
    '/timetable',
    '/examinations',
    '/finance',
    '/operations/reports',
    '/superadmin/dashboard',
];

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

foreach ($routes as $uri) {
    $req = Illuminate\Http\Request::create($uri, 'GET');
    $res = $httpKernel->handle($req);
    echo "$uri => Status: " . $res->getStatusCode();
    if ($res->isRedirection()) {
        echo " -> " . $res->headers->get('Location');
    }
    echo "\n";
}
