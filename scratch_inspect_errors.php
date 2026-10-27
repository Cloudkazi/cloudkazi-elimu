<?php
require __DIR__ . '/vendor/autoload.php';

$failedRoutes = [
    'students.index' => '/students',
    'students.create' => '/students/create',
    'academics.teachers' => '/academics/teachers',
    'attendance.index' => '/attendance',
    'finance.payments' => '/finance/payments',
    'teacher.dashboard' => '/teacher/dashboard',
];

$initApp = require __DIR__ . '/bootstrap/app.php';
$initKernel = $initApp->make(\Illuminate\Contracts\Http\Kernel::class);
$initKernel->bootstrap();
$adminUser = \App\Models\User::where('email', 'admin@npa.ac.ke')->first();

foreach ($failedRoutes as $name => $uri) {
    $app = require __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();

    $request = \Illuminate\Http\Request::create($uri, 'GET');
    $app->instance('request', $request);
    \Illuminate\Support\Facades\Auth::login($adminUser);
    \Illuminate\Support\Facades\Session::put('active_school_id', $adminUser->school_id);

    try {
        $response = $kernel->handle($request);
        if ($response->isServerError()) {
            if (isset($response->exception) && $response->exception) {
                $e = $response->exception;
                echo "FAIL: $name -> " . get_class($e) . ": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
            } else {
                echo "FAIL: $name -> status " . $response->getStatusCode() . "\n";
                // extract title or h1 from content
                if (preg_match('/<title>(.*?)<\/title>/is', $response->getContent(), $m)) {
                    echo "  Title: " . trim($m[1]) . "\n";
                }
                if (preg_match('/class="text-red-500.*?">(.*?)<\/div>/is', $response->getContent(), $m)) {
                    echo "  Detail: " . trim(strip_tags($m[1])) . "\n";
                }
            }
        } else {
            echo "OK: $name -> status " . $response->getStatusCode() . "\n";
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION: $name -> " . get_class($e) . ": " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}
