<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

echo "=== Seeding Demo SaaS Data ===" . PHP_EOL;

// ── Packages ─────────────────────────────────────────────────────────────────
$packagesData = [
    [
        'name'              => 'Starter',
        'code'              => 'STARTER',
        'description'       => 'Perfect for small primary schools. Covers all core academic features.',
        'activation_fee'    => 5000,
        'annual_fee'        => 18000,
        'max_students'      => 200,
        'max_teachers'      => 20,
        'max_parents'       => 400,
        'max_admins'        => 3,
        'trial_days'        => 30,
        'grace_period_days' => 14,
        'is_public'         => true,
        'is_active'         => true,
        'sort_order'        => 1,
        'features'          => ['student_management', 'teacher_management', 'finance', 'examinations', 'attendance', 'reports'],
    ],
    [
        'name'              => 'Standard',
        'code'              => 'STANDARD',
        'description'       => 'For secondary schools. Includes M-Pesa, parent & student portals, library.',
        'activation_fee'    => 10000,
        'annual_fee'        => 30000,
        'max_students'      => 800,
        'max_teachers'      => 60,
        'max_parents'       => 1600,
        'max_admins'        => 8,
        'trial_days'        => 30,
        'grace_period_days' => 21,
        'is_public'         => true,
        'is_active'         => true,
        'sort_order'        => 2,
        'features'          => ['student_management', 'teacher_management', 'parent_portal', 'student_portal', 'finance', 'mpesa', 'examinations', 'attendance', 'timetable', 'library', 'reports', 'communication', 'sms'],
    ],
    [
        'name'              => 'Premium',
        'code'              => 'PREMIUM',
        'description'       => 'Full-featured for large schools. Includes transport, hostel, all portals and analytics.',
        'activation_fee'    => 20000,
        'annual_fee'        => 60000,
        'max_students'      => null,
        'max_teachers'      => null,
        'max_parents'       => null,
        'max_admins'        => null,
        'trial_days'        => 14,
        'grace_period_days' => 30,
        'is_public'         => true,
        'is_active'         => true,
        'sort_order'        => 3,
        'features'          => array_keys(Package::allFeatureKeys()),
    ],
    [
        'name'              => 'Enterprise',
        'code'              => 'ENTERPRISE',
        'description'       => 'Custom deployment for school groups, counties, or NGOs. Priority support.',
        'activation_fee'    => 50000,
        'annual_fee'        => 120000,
        'max_students'      => null,
        'max_teachers'      => null,
        'max_parents'       => null,
        'max_admins'        => null,
        'trial_days'        => 0,
        'grace_period_days' => 30,
        'is_public'         => false,
        'is_active'         => true,
        'sort_order'        => 4,
        'features'          => array_keys(Package::allFeatureKeys()),
    ],
];

foreach ($packagesData as $pkgData) {
    $features = $pkgData['features'];
    unset($pkgData['features']);

    $pkg = Package::updateOrCreate(['code' => $pkgData['code']], $pkgData);
    $allKeys = array_keys(Package::allFeatureKeys());
    foreach ($allKeys as $fk) {
        PackageFeature::updateOrCreate(
            ['package_id' => $pkg->id, 'feature_key' => $fk],
            ['enabled' => in_array($fk, $features)]
        );
    }
    echo "  ✓ Package: {$pkg->name}" . PHP_EOL;
}

// ── Schools ───────────────────────────────────────────────────────────────────
$adminRole   = Role::where('slug', 'school-admin')->first() ?? Role::first();
$standardPkg = Package::where('code', 'STANDARD')->first();
$premiumPkg  = Package::where('code', 'PREMIUM')->first();
$starterPkg  = Package::where('code', 'STARTER')->first();

$schoolsData = [
    [
        'name'            => 'Nairobi Academy High School',
        'code'            => 'NAHS',
        'email'           => 'admin@nairobiacademy.sc.ke',
        'phone'           => '0722000001',
        'curriculum_type' => '8-4-4',
        'county'          => 'Nairobi',
        'status'          => 'active',
        'package'         => $premiumPkg,
        'sub_status'      => 'active',
        'end_date'        => now()->addYear(),
        'students'        => 1240,
        'teachers'        => 87,
        'payment'         => 60000,
        'payment_status'  => 'successful',
    ],
    [
        'name'            => 'Mombasa Coast Secondary',
        'code'            => 'MCS',
        'email'           => 'admin@mombasacoast.sc.ke',
        'phone'           => '0711000002',
        'curriculum_type' => '8-4-4',
        'county'          => 'Mombasa',
        'status'          => 'active',
        'package'         => $standardPkg,
        'sub_status'      => 'active',
        'end_date'        => now()->addMonths(3),
        'students'        => 543,
        'teachers'        => 38,
        'payment'         => 30000,
        'payment_status'  => 'successful',
    ],
    [
        'name'            => 'Kisumu Lakeside Girls School',
        'code'            => 'KLGS',
        'email'           => 'admin@kisumulakeside.sc.ke',
        'phone'           => '0733000003',
        'curriculum_type' => 'CBC',
        'county'          => 'Kisumu',
        'status'          => 'trial',
        'package'         => $standardPkg,
        'sub_status'      => 'trial',
        'end_date'        => now()->addDays(18),
        'students'        => 312,
        'teachers'        => 25,
        'payment'         => 0,
        'payment_status'  => 'pending',
    ],
    [
        'name'            => 'Nakuru Highlands Primary',
        'code'            => 'NHP',
        'email'           => 'admin@nakuruhighlands.sc.ke',
        'phone'           => '0700000004',
        'curriculum_type' => 'CBC',
        'county'          => 'Nakuru',
        'status'          => 'active',
        'package'         => $starterPkg,
        'sub_status'      => 'active',
        'end_date'        => now()->addDays(45),
        'students'        => 178,
        'teachers'        => 14,
        'payment'         => 18000,
        'payment_status'  => 'successful',
    ],
    [
        'name'            => 'Eldoret Technical School',
        'code'            => 'ETS',
        'email'           => 'admin@eldoretchool.sc.ke',
        'phone'           => '0755000005',
        'curriculum_type' => '8-4-4',
        'county'          => 'Uasin Gishu',
        'status'          => 'grace_period',
        'package'         => $standardPkg,
        'sub_status'      => 'grace_period',
        'end_date'        => now()->subDays(5),
        'students'        => 425,
        'teachers'        => 31,
        'payment'         => 0,
        'payment_status'  => 'pending',
    ],
    [
        'name'            => 'Thika Road Academy',
        'code'            => 'TRA',
        'email'           => 'admin@thikaacademy.sc.ke',
        'phone'           => '0788000006',
        'curriculum_type' => 'CBC',
        'county'          => 'Kiambu',
        'status'          => 'suspended',
        'package'         => $standardPkg,
        'sub_status'      => 'suspended',
        'end_date'        => now()->subMonths(2),
        'students'        => 290,
        'teachers'        => 22,
        'payment'         => 0,
        'payment_status'  => 'pending',
    ],
];

foreach ($schoolsData as $sd) {
    $package = $sd['package'];
    if (!$package) continue;

    $school = School::updateOrCreate(['code' => $sd['code']], [
        'name'            => $sd['name'],
        'code'            => $sd['code'],
        'email'           => $sd['email'],
        'phone'           => $sd['phone'],
        'curriculum_type' => $sd['curriculum_type'],
        'county'          => $sd['county'],
        'status'          => $sd['status'],
        'motto'           => 'Excellence in Education',
        'trial_ends_at'   => $sd['sub_status'] === 'trial' ? $sd['end_date'] : null,
    ]);

    SchoolSetting::firstOrCreate(['school_id' => $school->id], [
        'currency'         => 'KES',
        'timezone'         => 'Africa/Nairobi',
        'daraja_shortcode' => '174379',
        'sms_sender_id'    => 'CLOUDKAZI',
    ]);

    // Create/update subscription
    Subscription::withoutGlobalScopes()->updateOrCreate(
        ['school_id' => $school->id],
        [
            'package_id'      => $package->id,
            'plan_name'       => $package->name,
            'status'          => $sd['sub_status'],
            'payment_status'  => $sd['payment_status'],
            'start_date'      => now()->subYear(),
            'end_date'        => $sd['end_date'],
            'amount'          => $package->annual_fee,
            'activation_fee'  => $package->activation_fee,
            'annual_fee'      => $package->annual_fee,
        ]
    );

    // Record payment if successful
    if ($sd['payment'] > 0) {
        SubscriptionPayment::firstOrCreate(
            ['school_id' => $school->id, 'payment_method' => 'mpesa'],
            [
                'subscription_id' => Subscription::withoutGlobalScopes()->where('school_id', $school->id)->value('id'),
                'package_id'      => $package->id,
                'amount'          => $sd['payment'],
                'payment_method'  => 'mpesa',
                'reference'       => 'SAAS-' . $school->id . '-DEMO',
                'status'          => 'successful',
                'paid_at'         => now()->subMonths(rand(1, 6)),
            ]
        );
    }

    // Create school admin if not exists
    if ($adminRole) {
        User::firstOrCreate(['email' => $sd['email']], [
            'school_id' => $school->id,
            'role_id'   => $adminRole->id,
            'name'      => 'Admin - ' . $sd['name'],
            'phone'     => $sd['phone'],
            'password'  => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    echo "  ✓ School: {$school->name} [{$sd['status']}]" . PHP_EOL;
}

echo PHP_EOL . "=== Seed Complete ===" . PHP_EOL;
echo "Schools: " . School::count() . PHP_EOL;
echo "Packages: " . Package::count() . PHP_EOL;
echo "Subscriptions: " . Subscription::withoutGlobalScopes()->count() . PHP_EOL;
echo "Payments: " . SubscriptionPayment::count() . PHP_EOL;
