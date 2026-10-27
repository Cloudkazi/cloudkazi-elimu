<?php
// Test all key routes for 500 errors by simulating Blade rendering
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\User;

// Login as School Admin (Dr. James Kariuki)
$admin = User::with('role')->find(2);
Auth::login($admin);
session()->put('active_school_id', 1);

$routes = [
    'dashboard'                  => ['App\Http\Controllers\DashboardController', 'index'],
    'students.index'             => ['App\Http\Controllers\StudentController', 'index'],
    'academics.classes'          => ['App\Http\Controllers\AcademicController', 'classes'],
    'academics.subjects'         => ['App\Http\Controllers\AcademicController', 'subjects'],
    'academics.teachers'         => ['App\Http\Controllers\AcademicController', 'teachers'],
    'academics.curriculum'       => ['App\Http\Controllers\AcademicController', 'curriculum'],
    'academics.settings'         => ['App\Http\Controllers\AcademicController', 'settings'],
    'attendance.index'           => ['App\Http\Controllers\AttendanceController', 'index'],
    'attendance.report'          => ['App\Http\Controllers\AttendanceController', 'report'],
    'examinations.index'         => ['App\Http\Controllers\ExaminationController', 'index'],
    'examinations.marks'         => ['App\Http\Controllers\ExaminationController', 'generalMarks'],
    'examinations.results'       => ['App\Http\Controllers\ExaminationController', 'results'],
    'finance.index'              => ['App\Http\Controllers\FinanceController', 'index'],
    'finance.fees'               => ['App\Http\Controllers\FinanceController', 'fees'],
    'finance.payments'           => ['App\Http\Controllers\FinanceController', 'payments'],
    'finance.invoices'           => ['App\Http\Controllers\FinanceController', 'invoices'],
    'finance.arrears'            => ['App\Http\Controllers\FinanceController', 'arrears'],
    'finance.reports'            => ['App\Http\Controllers\FinanceController', 'reports'],
    'operations.library'         => ['App\Http\Controllers\OperationController', 'library'],
    'operations.transport'       => ['App\Http\Controllers\OperationController', 'transport'],
    'operations.hostel'          => ['App\Http\Controllers\OperationController', 'hostel'],
    'operations.notices'         => ['App\Http\Controllers\OperationController', 'communication'],
    'operations.staff'           => ['App\Http\Controllers\OperationController', 'hr'],
    'operations.materials'       => ['App\Http\Controllers\OperationController', 'materials'],
    'portal.parent'              => ['App\Http\Controllers\PortalController', 'parent'],
    'portal.student'             => ['App\Http\Controllers\PortalController', 'student'],
    'school.users'               => ['App\Http\Controllers\UserManagementController', 'index'],
    'timetable.index'            => ['App\Http\Controllers\TimetableController', 'index'],
];

$errors = [];
$ok = [];

foreach ($routes as $name => [$class, $method]) {
    try {
        $controller = app($class);
        $request = \Illuminate\Http\Request::create('/' . str_replace('.', '/', $name), 'GET');
        $request->setLaravelSession(session());
        
        $response = $controller->$method($request);
        
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            $ok[] = "$name → REDIRECT to " . $response->getTargetUrl();
        } elseif ($response instanceof \Illuminate\View\View || $response instanceof \Illuminate\Contracts\View\View) {
            // Try to render
            try {
                $rendered = $response->render();
                $ok[] = "$name → OK (rendered " . strlen($rendered) . " bytes)";
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
