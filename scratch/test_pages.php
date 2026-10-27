<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Simulate a logged-in SuperAdmin user (ID=1)
$user = App\Models\User::find(1);
if (!$user) {
    echo "ERROR: User ID=1 (superadmin) not found!" . PHP_EOL;
    exit(1);
}

echo "SuperAdmin: {$user->name} | Role: {$user->role?->slug}" . PHP_EOL . PHP_EOL;

$pages = [
    'Dashboard'         => '/superadmin/dashboard',
    'Schools'           => '/superadmin/schools',
    'Packages'          => '/superadmin/packages',
    'Subscriptions'     => '/superadmin/subscriptions',
    'Billing'           => '/superadmin/billing',
    'Usage'             => '/superadmin/usage',
    'Audit Logs'        => '/superadmin/audit-logs',
    'Platform Settings' => '/superadmin/settings',
    'Users'             => '/superadmin/users',
    'Create School'     => '/superadmin/schools/create',
    'Create Package'    => '/superadmin/packages/create',
];

$results = [];

foreach ($pages as $name => $path) {
    // Create a synthetic request
    $request = Illuminate\Http\Request::create($path, 'GET');

    // Simulate auth
    $guard = $app->make('auth');
    $guard->setUser($user);
    $app->instance('auth', $guard);

    // Try to render via the controller directly
    try {
        // Use artisan to make a quick check of view compilation
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', '8000');

        // Check if view file exists and can be compiled without errors
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $results[$name] = ['status' => $status, 'ok' => $status < 400];

        if ($status >= 400) {
            // Get error from content
            $content = $response->getContent();
            preg_match('/<title>(.*?)<\/title>/s', $content, $m);
            $results[$name]['title'] = $m[1] ?? 'Unknown';
            preg_match('/class="exception-message[^"]*"[^>]*>\s*(.*?)\s*<\/div>/s', $content, $em);
            $results[$name]['error'] = strip_tags($em[1] ?? 'Unknown error');
        }
    } catch (\Throwable $e) {
        $results[$name] = ['status' => 'exception', 'ok' => false, 'error' => $e->getMessage()];
    }
}

echo "=== Page Test Results ===" . PHP_EOL;
foreach ($results as $name => $result) {
    $icon = $result['ok'] ? '✓' : '✗';
    $status = $result['status'];
    echo "  {$icon} [{$status}] {$name}";
    if (!$result['ok']) {
        echo " — " . ($result['error'] ?? 'Error');
    }
    echo PHP_EOL;
}
