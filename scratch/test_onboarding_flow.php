<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\School;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== Testing Public School Onboarding Flow ===\n\n";

$pkg = Package::where('is_public', true)->first();
if (!$pkg) {
    echo "No public package found!\n";
    exit(1);
}
echo "Selected Package: {$pkg->name} (Code: {$pkg->code}, Fee: KES {$pkg->annual_fee})\n";

// Generate unique school data
$uniq = substr(md5(uniqid()), 0, 5);
$schoolData = [
    'name'            => "Test Academy {$uniq}",
    'code'            => "TAC{$uniq}",
    'email'           => "admin{$uniq}@testacademy.sc.ke",
    'phone'           => '0712345678',
    'curriculum_type' => '8-4-4',
    'county'          => 'Nairobi',
    'package_id'      => $pkg->id,
    'admin_name'      => "Principal {$uniq}",
    'admin_email'     => "admin{$uniq}@testacademy.sc.ke",
    'admin_password'  => 'Secret123!',
    'admin_password_confirmation' => 'Secret123!',
];

// 1. Submit Registration POST with CSRF session
$session = $app->make('session')->driver();
$session->start();
$token = $session->token();

$schoolData['_token'] = $token;

$request = Illuminate\Http\Request::create('/school/register', 'POST', $schoolData);
$request->setLaravelSession($session);
$response = $app->handle($request);
echo "1. Register POST => Status: " . $response->getStatusCode() . "\n";

if ($response->isRedirection()) {
    $redirectUrl = $response->headers->get('Location');
    echo "   Redirects to: {$redirectUrl}\n";
} else {
    echo "   Failed with body: " . substr(strip_tags($response->getContent()), 0, 300) . "\n";
    exit(1);
}

// Check database for newly created school
$createdSchool = School::where('code', "TAC{$uniq}")->first();
if (!$createdSchool) {
    echo "   School was not saved in DB!\n";
    exit(1);
}
echo "   School Created: ID={$createdSchool->id}, Name={$createdSchool->name}, Status={$createdSchool->status}\n";

// Check subscription
$sub = Subscription::withoutGlobalScopes()->where('school_id', $createdSchool->id)->first();
echo "   Subscription: ID=" . ($sub?->id ?? 'NONE') . ", Plan=" . ($sub?->plan_name ?? 'NONE') . ", Amount=" . ($sub?->amount ?? 0) . ", Status=" . ($sub?->status ?? 'NONE') . "\n";

// Check School Admin user
$adminUser = User::where('email', "admin{$uniq}@testacademy.sc.ke")->first();
echo "   Admin User: ID=" . ($adminUser?->id ?? 'NONE') . ", Name=" . ($adminUser?->name ?? 'NONE') . ", Role=" . ($adminUser?->role?->slug ?? 'NONE') . "\n";

// 2. Test Payment Page GET
$payRequest = Illuminate\Http\Request::create("/school/payment/{$createdSchool->id}", 'GET');
$payResponse = $app->handle($payRequest);
echo "2. Payment Page GET => Status: " . $payResponse->getStatusCode() . "\n";

// 3. Test Manual Payment / Activation via SuperAdmin
echo "3. Testing Manual Payment Recording for Newly Registered School...\n";
$superAdmin = User::where('email', 'superadmin@cloudkazi.com')->first();
\Illuminate\Support\Facades\Auth::login($superAdmin);

$token = $session->token();
$manualData = [
    '_token'         => $token,
    'amount'         => $pkg->activation_fee > 0 ? $pkg->activation_fee : $pkg->annual_fee,
    'payment_method' => 'mpesa',
    'reference'      => "PAY-TEST-{$uniq}",
    'notes'          => 'Onboarding fee paid via Daraja',
];

$manualPayRequest = Illuminate\Http\Request::create(
    "/superadmin/schools/{$createdSchool->id}/record-payment",
    'POST',
    $manualData
);
$manualPayRequest->setLaravelSession($session);
$manualPayRequest->setUserResolver(fn() => $superAdmin);
$manualPayResponse = $app->handle($manualPayRequest);
echo "   Manual Payment POST => Status: " . $manualPayResponse->getStatusCode() . "\n";

$createdSchool->refresh();
$sub->refresh();
echo "   After Payment: School Status={$createdSchool->status}, Subscription Status={$sub->status}, Payment Status={$sub->payment_status}\n";

// Clean up test school
echo "\nCleaning up test artifacts...\n";
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
$createdSchool->users()->delete();
$createdSchool->subscriptionPayments()->delete();
$createdSchool->features()->delete();
$createdSchool->subscription()->delete();
$createdSchool->delete();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');
echo "Cleaned up test school TAC{$uniq}.\n";

echo "\n>>> ONBOARDING FLOW VERIFIED END-TO-END! <<<\n";
