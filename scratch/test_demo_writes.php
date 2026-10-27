<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Disable CSRF verification for test request
$app->instance(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, new class {
    public function handle($request, $next) { return $next($request); }
});

$demoUser = App\Models\User::where('email', 'admin@demo.com')->first();
Illuminate\Support\Facades\Auth::login($demoUser);

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$req1 = Illuminate\Http\Request::create('/students', 'POST', [
    'admission_number' => 'DEMO-HACK-001',
    'first_name' => 'Demo',
    'last_name' => 'Hacker',
]);
$req1->headers->set('Accept', 'application/json');
$res1 = $httpKernel->handle($req1);
echo "POST /students => Status: " . $res1->getStatusCode() . " Body: " . substr($res1->getContent(), 0, 150) . "\n";

$req2 = Illuminate\Http\Request::create('/students/1', 'PUT', ['first_name' => 'Hacked']);
$req2->headers->set('Accept', 'application/json');
$res2 = $httpKernel->handle($req2);
echo "PUT /students/1 => Status: " . $res2->getStatusCode() . " Body: " . substr($res2->getContent(), 0, 150) . "\n";

$req3 = Illuminate\Http\Request::create('/students/1', 'DELETE');
$req3->headers->set('Accept', 'application/json');
$res3 = $httpKernel->handle($req3);
echo "DELETE /students/1 => Status: " . $res3->getStatusCode() . " Body: " . substr($res3->getContent(), 0, 150) . "\n";
