<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Super Admin Module Test ===" . PHP_EOL . PHP_EOL;

// Test models
$models = [
    'Package' => App\Models\Package::class,
    'School' => App\Models\School::class,
    'Subscription' => App\Models\Subscription::class,
    'SubscriptionPayment' => App\Models\SubscriptionPayment::class,
    'AuditLog' => App\Models\AuditLog::class,
    'PlatformSetting' => App\Models\PlatformSetting::class,
    'SchoolFeature' => App\Models\SchoolFeature::class,
    'PackageFeature' => App\Models\PackageFeature::class,
    'SchoolPopulationAllocation' => App\Models\SchoolPopulationAllocation::class,
];

echo "--- Models ---" . PHP_EOL;
foreach ($models as $name => $class) {
    try {
        $count = $class::count();
        echo "  ✓ {$name}: {$count} records" . PHP_EOL;
    } catch (Exception $e) {
        echo "  ✗ {$name}: ERROR - " . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL . "--- Services ---" . PHP_EOL;
$services = [
    'AuditLogService' => App\Services\AuditLogService::class,
    'SchoolFeatureService' => App\Services\SchoolFeatureService::class,
    'PopulationAllocationService' => App\Services\PopulationAllocationService::class,
    'SchoolSubscriptionService' => App\Services\SchoolSubscriptionService::class,
];
foreach ($services as $name => $class) {
    echo "  " . (class_exists($class) ? "✓" : "✗") . " {$name}" . PHP_EOL;
}

echo PHP_EOL . "--- Package Features ---" . PHP_EOL;
$features = App\Models\Package::allFeatureKeys();
echo "  " . count($features) . " features defined: " . implode(', ', array_keys($features)) . PHP_EOL;

echo PHP_EOL . "--- Package Data ---" . PHP_EOL;
foreach (App\Models\Package::all() as $pkg) {
    echo "  Package: {$pkg->name} | Annual: KES {$pkg->annual_fee} | Active: " . ($pkg->is_active ? 'Yes' : 'No') . PHP_EOL;
}

echo PHP_EOL . "--- View Files ---" . PHP_EOL;
$views = [
    'superadmin.dashboard' => 'resources/views/superadmin/dashboard.blade.php',
    'superadmin.schools.index' => 'resources/views/superadmin/schools/index.blade.php',
    'superadmin.schools.create' => 'resources/views/superadmin/schools/create.blade.php',
    'superadmin.schools.account' => 'resources/views/superadmin/schools/account.blade.php',
    'superadmin.packages.index' => 'resources/views/superadmin/packages/index.blade.php',
    'superadmin.packages.create' => 'resources/views/superadmin/packages/create.blade.php',
    'superadmin.packages.edit' => 'resources/views/superadmin/packages/edit.blade.php',
    'superadmin.subscriptions' => 'resources/views/superadmin/subscriptions.blade.php',
    'superadmin.billing' => 'resources/views/superadmin/billing.blade.php',
    'superadmin.usage' => 'resources/views/superadmin/usage.blade.php',
    'superadmin.audit_logs' => 'resources/views/superadmin/audit_logs.blade.php',
    'superadmin.platform_settings' => 'resources/views/superadmin/platform_settings.blade.php',
    'superadmin.users' => 'resources/views/superadmin/users.blade.php',
    'school.register' => 'resources/views/school/register.blade.php',
    'school.payment' => 'resources/views/school/payment.blade.php',
];

$baseDir = __DIR__ . '/..';
foreach ($views as $name => $path) {
    $exists = file_exists($baseDir . '/' . $path);
    echo "  " . ($exists ? "✓" : "✗ MISSING") . " {$name}" . PHP_EOL;
}

echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
