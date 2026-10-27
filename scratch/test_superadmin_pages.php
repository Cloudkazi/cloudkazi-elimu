<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;

// Login as SuperAdmin (ID=1)
$superadmin = User::with('role')->find(1);
if (!$superadmin) {
    echo "ERROR: SuperAdmin (ID=1) not found.\n"; exit(1);
}
Auth::login($superadmin);
echo "Logged in as: {$superadmin->name} | Role: {$superadmin->role?->slug}\n\n";

$routes = [
    'superadmin.dashboard'   => ['App\Http\Controllers\SuperAdminController', 'dashboard'],
    'superadmin.schools'     => ['App\Http\Controllers\SuperAdminController', 'schools'],
    'superadmin.packages'    => ['App\Http\Controllers\SuperAdminController', 'packages'],
    'superadmin.subscriptions'=> ['App\Http\Controllers\SuperAdminController', 'subscriptions'],
    'superadmin.billing'     => ['App\Http\Controllers\SuperAdminController', 'billing'],
    'superadmin.usage'       => ['App\Http\Controllers\SuperAdminController', 'usage'],
    'superadmin.audit-logs'  => ['App\Http\Controllers\SuperAdminController', 'auditLogs'],
    'superadmin.settings'    => ['App\Http\Controllers\SuperAdminController', 'platformSettings'],
    'superadmin.users'       => ['App\Http\Controllers\SuperAdminController', 'users'],
    'superadmin.schools.create'=> ['App\Http\Controllers\SuperAdminController', 'createSchool'],
    'superadmin.packages.create'=> ['App\Http\Controllers\SuperAdminController', 'createPackage'],
];

$errors = [];
$ok = [];

foreach ($routes as $name => [$class, $method]) {
    try {
        $controller = app($class);
        $request = \Illuminate\Http\Request::create('/superadmin/' . str_replace('superadmin.', '', $name), 'GET');
        $request->setLaravelSession(session());

        $response = $controller->$method($request);

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            $ok[] = "$name → REDIRECT to " . $response->getTargetUrl();
        } elseif ($response instanceof \Illuminate\View\View || $response instanceof \Illuminate\Contracts\View\View) {
            try {
                $rendered = $response->render();
                $ok[] = "$name → OK (" . number_format(strlen($rendered)) . " bytes)";
            } catch (Exception $re) {
                $errors[] = "$name → RENDER ERROR: " . $re->getMessage() . " in " . basename($re->getFile()) . ":" . $re->getLine();
            }
        } else {
            $ok[] = "$name → OK (type: " . get_class($response) . ")";
        }
    } catch (\Throwable $e) {
        $errors[] = "$name → ERROR: " . $e->getMessage() . " in " . basename($e->getFile()) . ":" . $e->getLine();
    }
}

echo "=== PASSING (" . count($ok) . ") ===\n";
foreach ($ok as $msg) { echo "  ✓ $msg\n"; }

echo "\n=== ERRORS (" . count($errors) . ") ===\n";
foreach ($errors as $msg) { echo "  ✗ $msg\n"; }
