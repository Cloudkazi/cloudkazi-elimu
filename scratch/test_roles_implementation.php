<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

echo "========================================================\n";
echo "   CLOUDKAZI ELIMU — STAFF ROLES VERIFICATION TEST\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $description): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo " [PASS] $description\n";
    } else {
        $failCount++;
        echo " [FAIL] $description\n";
    }
}

// ── TEST 1: DATABASE ROLES VERIFICATION ──────────────────────────────────────
echo "--- 1. Database Roles & is_staff Verification ---\n";

$expectedStaffRoles = [
    'principal'            => 'Principal / Head Teacher',
    'deputy-principal'     => 'Deputy Principal',
    'teacher'              => 'Teacher',
    'bursar'               => 'Bursar / Accountant',
    'librarian'            => 'Librarian',
    'examinations-officer' => 'Examinations Officer',
    'secretary'            => 'Secretary / Front Office',
    'ict-admin'            => 'ICT Administrator',
    'discipline-officer'   => 'Discipline / Welfare Officer',
    'hostel-warden'        => 'Dorm Master',
];

$expectedNonStaffRoles = [
    'super-admin',
    'school-admin',
    'student',
    'parent',
];

foreach ($expectedStaffRoles as $slug => $displayName) {
    $role = Role::where('slug', $slug)->first();
    assertTest($role !== null, "Role '$slug' exists in DB");
    if ($role) {
        assertTest($role->name === $displayName, "Role '$slug' display name is '$displayName' (actual: '{$role->name}')");
        assertTest((bool)$role->is_staff === true, "Role '$slug' has is_staff = 1");
    }
}

foreach ($expectedNonStaffRoles as $slug) {
    $role = Role::where('slug', $slug)->first();
    assertTest($role !== null, "Non-staff role '$slug' exists in DB");
    if ($role) {
        assertTest((bool)$role->is_staff === false, "Non-staff role '$slug' has is_staff = 0");
    }
}

// ── TEST 2: EXCLUDED ROLES ──────────────────────────────────────────────────
echo "\n--- 2. Excluded Roles Check ---\n";
$classTeacherRole = Role::where('slug', 'class-teacher')->first();
assertTest($classTeacherRole === null, "Role 'class-teacher' does NOT exist in DB");

$transportOfficerRole = Role::where('slug', 'transport-officer')->first();
assertTest($transportOfficerRole === null, "Role 'transport-officer' does NOT exist in DB");

// ── TEST 3: EXACT 10 STAFF ROLES IN QUERY ───────────────────────────────────
echo "\n--- 3. Add Staff Roles Query Verification ---\n";
$staffRoles = Role::staffOnly()->whereIn('slug', Role::STAFF_SLUGS)->orderBy('name')->get();
assertTest($staffRoles->count() === 10, "Add Staff query returns exactly 10 roles (actual: {$staffRoles->count()})");

$staffSlugs = $staffRoles->pluck('slug')->toArray();
sort($staffSlugs);
$expectedSlugs = array_keys($expectedStaffRoles);
sort($expectedSlugs);
assertTest($staffSlugs === $expectedSlugs, "Add Staff slugs match expected 10 slugs exactly");

echo "  Add Staff Dropdown Options:\n";
foreach ($staffRoles as $r) {
    echo "   * {$r->name} (slug: {$r->slug}, id: {$r->id})\n";
}

// ── TEST 4: USER COUNT PRESERVATION ─────────────────────────────────────────
echo "\n--- 4. User Count & Integrity Preservation ---\n";
$totalUsers = User::count();
assertTest($totalUsers === 8, "Total user count is preserved at 8 (actual: $totalUsers)");

// Verify users and their role assignments
$users = User::with('role')->orderBy('id')->get();
echo "  Current Users:\n";
foreach ($users as $u) {
    echo "   - [ID: {$u->id}] {$u->name} | {$u->email} | Role: {$u->role->name} ({$u->role->slug}) | School ID: " . ($u->school_id ?? 'NULL') . "\n";
}

// ── TEST 5: DASHBOARD ROUTING ───────────────────────────────────────────────
echo "\n--- 5. Dashboard Routing (getDashboardRoute) ---\n";
foreach ($expectedStaffRoles as $slug => $name) {
    $role = Role::where('slug', $slug)->first();
    $mockUser = new User();
    $mockUser->setRelation('role', $role);
    $mockUser->is_demo = false;
    $mockUser->email = "test_{$slug}@example.com";
    $route = $mockUser->getDashboardRoute();
    echo "  Role '{$slug}' -> Dashboard Route: $route\n";
    assertTest(!empty($route) && $route !== route('login'), "Role '{$slug}' resolves to a valid dashboard route");
}

// Non-staff dashboard routing
foreach ($expectedNonStaffRoles as $slug) {
    $role = Role::where('slug', $slug)->first();
    $mockUser = new User();
    $mockUser->setRelation('role', $role);
    $mockUser->is_demo = false;
    $mockUser->email = "test_{$slug}@example.com";
    $route = $mockUser->getDashboardRoute();
    echo "  Non-staff Role '{$slug}' -> Dashboard Route: $route\n";
    assertTest(!empty($route), "Non-staff role '{$slug}' resolves to a valid dashboard route");
}

// ── TEST 6: BACKEND VALIDATION TEST ─────────────────────────────────────────
echo "\n--- 6. Backend Validation in UserManagementController ---\n";
// Let's test the validation logic in isolation
$schoolAdminUser = User::whereHas('role', fn($q) => $q->where('slug', 'school-admin'))->first();

$testPayloads = [
    // Unauthorized roles (must be blocked)
    ['role_slug' => 'super-admin', 'should_allow' => false, 'label' => 'Super Admin (slug: super-admin)'],
    ['role_slug' => 'school-admin', 'should_allow' => false, 'label' => 'School Admin (slug: school-admin)'],
    ['role_slug' => 'student', 'should_allow' => false, 'label' => 'Student (slug: student)'],
    ['role_slug' => 'parent', 'should_allow' => false, 'label' => 'Parent (slug: parent)'],
    // Allowed staff roles (must be permitted)
    ['role_slug' => 'teacher', 'should_allow' => true, 'label' => 'Teacher (slug: teacher)'],
    ['role_slug' => 'principal', 'should_allow' => true, 'label' => 'Principal (slug: principal)'],
    ['role_slug' => 'deputy-principal', 'should_allow' => true, 'label' => 'Deputy Principal (slug: deputy-principal)'],
    ['role_slug' => 'bursar', 'should_allow' => true, 'label' => 'Bursar (slug: bursar)'],
    ['role_slug' => 'librarian', 'should_allow' => true, 'label' => 'Librarian (slug: librarian)'],
    ['role_slug' => 'examinations-officer', 'should_allow' => true, 'label' => 'Examinations Officer (slug: examinations-officer)'],
    ['role_slug' => 'secretary', 'should_allow' => true, 'label' => 'Secretary (slug: secretary)'],
    ['role_slug' => 'ict-admin', 'should_allow' => true, 'label' => 'ICT Admin (slug: ict-admin)'],
    ['role_slug' => 'discipline-officer', 'should_allow' => true, 'label' => 'Discipline Officer (slug: discipline-officer)'],
    ['role_slug' => 'hostel-warden', 'should_allow' => true, 'label' => 'Dorm Master (slug: hostel-warden)'],
];

foreach ($testPayloads as $tp) {
    $role = Role::where('slug', $tp['role_slug'])->first();
    $isAllowed = $role && $role->is_staff && in_array($role->slug, Role::STAFF_SLUGS, true) && !in_array($role->slug, Role::NON_STAFF_SLUGS, true);
    assertTest($isAllowed === $tp['should_allow'], "Backend rule check for {$tp['label']} is " . ($tp['should_allow'] ? 'ALLOWED' : 'REJECTED'));
}

echo "\n========================================================\n";
echo "SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "========================================================\n";
exit($failCount > 0 ? 1 : 0);
