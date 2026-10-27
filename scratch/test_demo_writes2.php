<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = App\Models\Student::first();
echo "Existing student ID: " . ($student ? $student->id : 'none') . "\n";

$app->instance(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, new class {
    public function handle($request, $next) { return $next($request); }
});

$demoUser = App\Models\User::where('email', 'admin@demo.com')->first();
Illuminate\Support\Facades\Auth::login($demoUser);

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

if ($student) {
    $req2 = Illuminate\Http\Request::create("/students/{$student->id}", 'PUT', ['first_name' => 'Hacked']);
    $req2->headers->set('Accept', 'application/json');
    $res2 = $httpKernel->handle($req2);
    echo "PUT /students/{$student->id} => Status: " . $res2->getStatusCode() . " Body: " . substr($res2->getContent(), 0, 150) . "\n";

    $req3 = Illuminate\Http\Request::create("/students/{$student->id}", 'DELETE');
    $req3->headers->set('Accept', 'application/json');
    $res3 = $httpKernel->handle($req3);
    echo "DELETE /students/{$student->id} => Status: " . $res3->getStatusCode() . " Body: " . substr($res3->getContent(), 0, 150) . "\n";
}
