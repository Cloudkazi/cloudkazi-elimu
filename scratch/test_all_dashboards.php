<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$testAccounts = [
    'Super Admin'    => ['email' => 'superadmin@cloudkazi.com', 'route' => '/superadmin/dashboard'],
    'School Admin'   => ['email' => 'admin@npa.ac.ke',          'route' => '/dashboard'],
    'Principal'      => ['email' => 'principal@npa.ac.ke',      'route' => '/dashboard'],
    'Bursar'         => ['email' => 'bursar@npa.ac.ke',         'route' => '/finance'],
    'Teacher'        => ['email' => 'teacher@npa.ac.ke',        'route' => '/portal/teacher'],
    'Student'        => ['email' => 'student@npa.ac.ke',        'route' => '/portal/student'],
    'Parent'         => ['email' => 'parent@npa.ac.ke',         'route' => '/portal/parent'],
];

echo "Testing All User Role Dashboards for Regressions:\n\n";

$allPassed = true;

foreach ($testAccounts as $roleTitle => $info) {
    $user = User::where('email', $info['email'])->first();
    if (!$user) {
        echo sprintf("  %-16s: USER NOT FOUND (%s)\n", $roleTitle, $info['email']);
        $allPassed = false;
        continue;
    }

    Auth::login($user);

    $request = Illuminate\Http\Request::create($info['route'], 'GET');
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    try {
        $response = $app->handle($request);
        $status = $response->getStatusCode();
        echo sprintf("  %-16s (%s) => %s -> Status: %d\n", $roleTitle, $user->email, $info['route'], $status);
        if ($status >= 400) {
            $allPassed = false;
            if (isset($response->exception) && $response->exception) {
                echo "     Error: " . $response->exception->getMessage() . "\n";
            }
        }
    } catch (\Throwable $e) {
        $allPassed = false;
        echo sprintf("  %-16s => EXCEPTION: %s\n", $roleTitle, $e->getMessage());
    }

    Auth::logout();
}

if ($allPassed) {
    echo "\n>>> ALL DASHBOARDS RETURNED SUCCESS! NO REGRESSIONS DETECTED. <<<\n";
} else {
    echo "\n>>> SOME DASHBOARDS FAILED. <<<\n";
}
