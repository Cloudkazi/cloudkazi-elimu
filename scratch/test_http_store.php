<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "--- HTTP End-to-End Test for UserManagementController::store ---\n";

$schoolAdmin = User::whereHas('role', fn($q) => $q->where('slug', 'school-admin'))->first();
Auth::login($schoolAdmin);

$controller = new \App\Http\Controllers\UserManagementController();

// 1. Try to submit Super Admin role_id (39)
$superAdminRole = Role::where('slug', 'super-admin')->first();
$req1 = Request::create('/school/users', 'POST', [
    'name' => 'Malicious Admin',
    'email' => 'hacker@example.com',
    'phone' => '+254700000000',
    'role_id' => $superAdminRole->id,
    'password' => 'Secret123!',
]);
// Session needed for back()
$session = $app->make('session')->driver();
$req1->setLaravelSession($session);

try {
    $resp1 = $controller->store($req1);
    $errors = $session->get('errors');
    $hasRoleError = $errors && $errors->has('role_id');
    echo "Attempt to create Super Admin via Add Staff: " . ($hasRoleError ? "BLOCKED (role_id error)" : "FAILED TO BLOCK") . "\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Attempt to create Super Admin via Add Staff: BLOCKED with ValidationException\n";
}

// 2. Try to submit Student role_id (42)
$studentRole = Role::where('slug', 'student')->first();
$req2 = Request::create('/school/users', 'POST', [
    'name' => 'Fake Student',
    'email' => 'fake_student@example.com',
    'phone' => '+254700000001',
    'role_id' => $studentRole->id,
    'password' => 'Secret123!',
]);
$req2->setLaravelSession($session);

try {
    $resp2 = $controller->store($req2);
    $errors = $session->get('errors');
    $hasRoleError = $errors && $errors->has('role_id');
    echo "Attempt to create Student via Add Staff: " . ($hasRoleError ? "BLOCKED (role_id error)" : "FAILED TO BLOCK") . "\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Attempt to create Student via Add Staff: BLOCKED with ValidationException\n";
}

// 3. Test creating a valid staff member (Examinations Officer)
$examsRole = Role::where('slug', 'examinations-officer')->first();
$req3 = Request::create('/school/users', 'POST', [
    'name' => 'Valid Exam Officer Test',
    'email' => 'exam_officer_test@example.com',
    'phone' => '+254700000002',
    'role_id' => $examsRole->id,
    'password' => 'Staff@2026!',
]);
$req3->setLaravelSession($session);

try {
    $resp3 = $controller->store($req3);
    $createdUser = User::where('email', 'exam_officer_test@example.com')->first();
    if ($createdUser && $createdUser->role_id === $examsRole->id && $createdUser->school_id === $schoolAdmin->school_id) {
        echo "Legitimate staff creation (Examinations Officer): SUCCESS\n";
        // Clean up test user to preserve database state
        $createdUser->delete();
        echo "Cleaned up temporary test user.\n";
    } else {
        echo "Legitimate staff creation: FAILED (User not found or attributes wrong)\n";
    }
} catch (\Exception $e) {
    echo "Legitimate staff creation failed with error: " . $e->getMessage() . "\n";
}

// Clean up
$session->flush();
echo "End-to-end security check complete.\n";
