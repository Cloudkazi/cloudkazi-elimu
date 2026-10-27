<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$demoUser = App\Models\User::where('email', 'admin@demo.com')->first();
echo "Found demo user: {$demoUser->name} (Role: {$demoUser->role?->slug}, school_id: " . var_export($demoUser->school_id, true) . ")\n";

// Login as demo user
Illuminate\Support\Facades\Auth::login($demoUser);

$request = Illuminate\Http\Request::create('/dashboard', 'GET');
$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $httpKernel->handle($request);
echo "GET /dashboard status: " . $response->getStatusCode() . "\n";
if ($response->isRedirection()) {
    echo "Redirect target: " . $response->headers->get('Location') . "\n";
}
