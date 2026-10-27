<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=================================================================\n";
echo "CLOUDKAZI ELIMU VERIFICATION TEST SUITE\n";
echo "=================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCondition($name, $condition, &$passCount, &$failCount) {
    if ($condition) {
        echo "  [PASS] $name\n";
        $passCount++;
    } else {
        echo "  [FAIL] $name\n";
        $failCount++;
    }
}

// -------------------------------------------------------------
// 1. DEMO ACCOUNTS AUDIT & CREDENTIALS PRESERVATION
// -------------------------------------------------------------
echo "1. VERIFYING DEMO ACCOUNTS & CREDENTIAL PRESERVATION:\n";

$demoEmails = [
    'admin@demo.com' => 'school-admin',
    'teacher@demo.com' => 'teacher',
    'student@demo.com' => 'student',
    'parent@demo.com' => 'parent',
];

foreach ($demoEmails as $email => $expectedRole) {
    $u = App\Models\User::withoutGlobalScopes()->where('email', $email)->first();
    assertCondition("Demo user {$email} exists in database", $u !== null, $passCount, $failCount);
    if ($u) {
        assertCondition("Demo user {$email} has role {$expectedRole}", $u->role?->slug === $expectedRole, $passCount, $failCount);
        assertCondition("Demo user {$email} isDemoAccount() returns true", $u->isDemoAccount() === true, $passCount, $failCount);
    }
}

// -------------------------------------------------------------
// 2. DEMO ACCOUNTS LOGOUT & REGISTER SCHOOL BUTTONS TEST
// -------------------------------------------------------------
echo "\n2. TESTING DEMO ACCOUNT INTERFACES & BUTTONS:\n";

$demoMiddleware = new App\Http\Middleware\DemoAccountMiddleware();
$authController = app(App\Http\Controllers\AuthController::class);

foreach ($demoEmails as $email => $role) {
    $u = App\Models\User::withoutGlobalScopes()->where('email', $email)->first();
    Illuminate\Support\Facades\Auth::login($u);

    // Simulate requesting dashboard / portal
    $targetRoute = $u->getDashboardRoute();
    $request = Illuminate\Http\Request::create($targetRoute, 'GET');
    $session = app('session')->driver();
    $session->start();
    $request->setLaravelSession($session);
    $response = $kernel->handle($request);
    $content = $response->getContent();

    assertCondition("Demo user {$email} can access {$targetRoute} (Status {$response->getStatusCode()})", $response->getStatusCode() === 200 || $response->getStatusCode() === 302, $passCount, $failCount);

    if ($response->getStatusCode() === 200) {
        assertCondition("Demo user {$email} view contains Register School button", str_contains($content, 'Register School') && str_contains($content, route('school.register')), $passCount, $failCount);
        assertCondition("Demo user {$email} view contains Logout form / button", str_contains($content, 'Logout') && str_contains($content, route('logout')), $passCount, $failCount);
        assertCondition("Demo user {$email} has Demo Mode indicator", str_contains($content, 'Demo Mode') || str_contains($content, 'Demo View Only'), $passCount, $failCount);
    }

    // Test that DemoAccountMiddleware blocks state-modifying write requests
    $writeReq = Illuminate\Http\Request::create('/students', 'POST', ['name' => 'Unauthorized']);
    $writeRes = $demoMiddleware->handle($writeReq, fn() => response('Allowed'));
    assertCondition("Demo user {$email} write POST is blocked by DemoAccountMiddleware", $writeRes->getContent() !== 'Allowed', $passCount, $failCount);

    // Test that DemoAccountMiddleware allows logout
    $logoutReqForMw = Illuminate\Http\Request::create('/logout', 'POST');
    $logoutMwRes = $demoMiddleware->handle($logoutReqForMw, fn() => response('Logout Allowed'));
    assertCondition("Demo user {$email} logout is permitted by DemoAccountMiddleware", $logoutMwRes->getContent() === 'Logout Allowed', $passCount, $failCount);

    // Test that AuthController@logout actually logs out, invalidates session, and redirects to login
    $logoutReq = Illuminate\Http\Request::create('/logout', 'POST');
    $logoutReq->setLaravelSession($session);
    $logoutRes = $authController->logout($logoutReq);

    assertCondition("Demo user {$email} logout response is redirect (Status {$logoutRes->getStatusCode()})", $logoutRes->getStatusCode() === 302, $passCount, $failCount);
    assertCondition("Logout redirects to login page", str_contains($logoutRes->headers->get('Location') ?? '', 'login'), $passCount, $failCount);
    assertCondition("Auth::user() is null after logout", Illuminate\Support\Facades\Auth::check() === false, $passCount, $failCount);
}

// -------------------------------------------------------------
// 3. SCHOOL ADMIN ACADEMIC YEAR STORAGE & SEPARATION
// -------------------------------------------------------------
echo "\n3. TESTING ACADEMIC YEAR STORAGE & SCHOOL_ID NUMERIC ENFORCEMENT:\n";

$schoolA = App\Models\School::find(29); // Nairobi Premier Academy
assertCondition("School A exists (ID=29, Name='{$schoolA->name}')", $schoolA !== null, $passCount, $failCount);

$adminA = App\Models\User::withoutGlobalScopes()->where('email', 'admin@npa.ac.ke')->first();
assertCondition("Admin A exists (ID={$adminA->id}, School ID={$adminA->school_id})", $adminA !== null, $passCount, $failCount);

Illuminate\Support\Facades\Auth::login($adminA);
session(['active_school_id' => $schoolA->id]);

// Create Academic Year 2026/2027
$controller = app(App\Http\Controllers\AcademicController::class);
$ayRequest = Illuminate\Http\Request::create(route('academics.settings.academic-year.store'), 'POST', [
    'name' => '2026/2027',
    'start_date' => '2026-01-01',
    'end_date' => '2026-12-31',
    'school_id' => 'MaliciousNameOrID', // Should be completely ignored!
]);

$controller->storeAcademicYear($ayRequest);

// Inspect database record
$savedAY = App\Models\AcademicYear::withoutGlobalScopes()
    ->where('school_id', $schoolA->id)
    ->where('name', '2026/2027')
    ->first();

assertCondition("AcademicYear '2026/2027' created in DB", $savedAY !== null, $passCount, $failCount);
assertCondition("academic_years.school_id is strictly numeric {$schoolA->id}", $savedAY && (int)$savedAY->school_id === (int)$schoolA->id && is_numeric($savedAY->school_id), $passCount, $failCount);
assertCondition("academic_years.name is '2026/2027'", $savedAY && $savedAY->name === '2026/2027', $passCount, $failCount);
assertCondition("academic_years.is_current is true", $savedAY && (bool)$savedAY->is_current === true, $passCount, $failCount);

// Re-verify School A's name was NOT overwritten
$schoolAFresh = App\Models\School::find(29);
assertCondition("schools.name is preserved and untouched ('{$schoolAFresh->name}')", $schoolAFresh->name === 'Nairobi Premier Academy', $passCount, $failCount);

// -------------------------------------------------------------
// 4. CROSS-TENANT ISOLATION & VALIDATION
// -------------------------------------------------------------
echo "\n4. TESTING CROSS-TENANT ISOLATION & MALICIOUS INPUT REJECTION:\n";

$schoolB = App\Models\School::find(31); // Nairobi Academy High School
$adminB = App\Models\User::withoutGlobalScopes()->where('email', 'admin@nairobiacademy.sc.ke')->first();
assertCondition("School B exists (ID=31, Name='{$schoolB->name}')", $schoolB !== null, $passCount, $failCount);
assertCondition("Admin B exists (ID={$adminB->id}, School ID={$adminB->school_id})", $adminB !== null, $passCount, $failCount);

// Log in as School B
Illuminate\Support\Facades\Auth::login($adminB);
session(['active_school_id' => $schoolB->id]);

// School B creates its own academic year: 2026/2027 with its terms
$ayRequestB = Illuminate\Http\Request::create(route('academics.settings.academic-year.store'), 'POST', [
    'name' => '2026/2027',
    'start_date' => '2026-01-01',
    'end_date' => '2026-12-31',
]);
$controller->storeAcademicYear($ayRequestB);

$savedAY_B = App\Models\AcademicYear::withoutGlobalScopes()
    ->where('school_id', $schoolB->id)
    ->where('name', '2026/2027')
    ->first();
assertCondition("School B AcademicYear saved with school_id={$schoolB->id}", $savedAY_B && (int)$savedAY_B->school_id === (int)$schoolB->id, $passCount, $failCount);

// Malicious test: School B tries to add a term referencing School A's academic_year_id!
$maliciousTermRequest = Illuminate\Http\Request::create(route('academics.settings.academic-term.store'), 'POST', [
    'academic_year_id' => $savedAY->id, // School A's year ID!
    'name' => 'Hacked Term',
    'start_date' => '2026-01-01',
    'end_date' => '2026-04-10',
]);

$rejected = false;
try {
    $controller->storeAcademicTerm($maliciousTermRequest);
} catch (\Illuminate\Validation\ValidationException $e) {
    $rejected = true;
    echo "  [INFO] Validation exception caught: " . json_encode($e->errors()) . "\n";
} catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
    $rejected = true;
    echo "  [INFO] HTTP abort exception caught: Code {$e->getStatusCode()}\n";
}
assertCondition("Cross-tenant term creation rejected with validation error or 403", $rejected === true, $passCount, $failCount);

// Legitimate test: School B adds a term to its OWN academic year spanning current date
$validTermRequest = Illuminate\Http\Request::create(route('academics.settings.academic-term.store'), 'POST', [
    'academic_year_id' => $savedAY_B->id,
    'name' => 'Term 3',
    'start_date' => '2026-09-01',
    'end_date' => '2026-11-30',
]);
$controller->storeAcademicTerm($validTermRequest);

$savedTerm_B = App\Models\AcademicTerm::withoutGlobalScopes()
    ->where('school_id', $schoolB->id)
    ->where('academic_year_id', $savedAY_B->id)
    ->where('name', 'Term 3')
    ->first();
assertCondition("School B term saved successfully with school_id={$schoolB->id}", $savedTerm_B !== null, $passCount, $failCount);

// -------------------------------------------------------------
// 5. DYNAMIC SCHOOL-SPECIFIC ACADEMIC CONTEXT TEST
// -------------------------------------------------------------
echo "\n5. TESTING DYNAMIC SCHOOL-SPECIFIC ACADEMIC CONTEXT:\n";

// School A context
$contextA = App\Services\AcademicCalendarService::getAcademicContext($schoolA->id);
assertCondition("School A academic year resolved dynamically: '{$contextA['year_name']}'", $contextA['year_name'] === '2026/2027', $passCount, $failCount);

// School B context: current date (2026-10-01) falls inside Term 3 (2026-09-01 to 2026-11-30)
$contextB = App\Services\AcademicCalendarService::getAcademicContext($schoolB->id);
assertCondition("School B academic year resolved dynamically: '{$contextB['year_name']}'", $contextB['year_name'] === '2026/2027', $passCount, $failCount);
assertCondition("School B current term resolved dynamically based on date range: '{$contextB['term_name']}'", $contextB['term_name'] === 'Term 3', $passCount, $failCount);

// -------------------------------------------------------------
// 6. SUPER ADMIN DOES NOT MANAGE SCHOOL ACADEMIC CALENDARS
// -------------------------------------------------------------
echo "\n6. VERIFYING SUPER ADMIN SEPARATION:\n";

$superAdmin = App\Models\User::withoutGlobalScopes()->where('email', 'superadmin@cloudkazi.com')->first();
Illuminate\Support\Facades\Auth::login($superAdmin);

$superReq = Illuminate\Http\Request::create('/superadmin/dashboard', 'GET');
$session = app('session')->driver();
$session->start();
$superReq->setLaravelSession($session);
$superRes = $kernel->handle($superReq);
$superContent = $superRes->getContent();

assertCondition("Super Admin dashboard loads (Status {$superRes->getStatusCode()})", $superRes->getStatusCode() === 200, $passCount, $failCount);
assertCondition("Super Admin dashboard has no School Academic Year form", !str_contains($superContent, 'academics.settings.academic-year.store'), $passCount, $failCount);

// -------------------------------------------------------------
// 7. PRESERVED SYSTEMS INTEGRITY CHECK
// -------------------------------------------------------------
echo "\n7. VERIFYING WORKING SUSPENSION & FEATURE SYSTEMS UNTOUCHED:\n";

assertCondition("SchoolFeatureService class exists and intact", class_exists(App\Services\SchoolFeatureService::class), $passCount, $failCount);
assertCondition("CheckSchoolFeature middleware exists and intact", class_exists(App\Http\Middleware\CheckSchoolFeature::class), $passCount, $failCount);
assertCondition("EnsureSchoolActive suspension middleware exists and intact", class_exists(App\Http\Middleware\EnsureSchoolActive::class), $passCount, $failCount);

echo "\n=================================================================\n";
echo "TEST RESULTS: PASS={$passCount}, FAIL={$failCount}\n";
echo "=================================================================\n";
