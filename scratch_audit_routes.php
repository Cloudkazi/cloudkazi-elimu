<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$routesToTest = [
    'dashboard' => '/dashboard',
    'academics.classes' => '/academics/classes',
    'academics.subjects' => '/academics/subjects',
    'academics.timetable' => '/academics/timetable',
    'academics.curriculum' => '/academics/curriculum',
    'students.index' => '/students',
    'students.create' => '/students/create',
    'students.promotions' => '/students/promotions',
    'students.documents' => '/students/documents',
    'academics.teachers' => '/academics/teachers',
    'academics.teacher-subjects' => '/academics/teacher-subjects',
    'attendance.index' => '/attendance',
    'examinations.index' => '/examinations',
    'examinations.marks' => '/examinations/marks',
    'examinations.results' => '/examinations/results',
    'examinations.report-cards' => '/examinations/report-cards',
    'finance.fees' => '/finance/fees',
    'finance.payments' => '/finance/payments',
    'finance.mpesa' => '/finance/mpesa',
    'finance.invoices' => '/finance/invoices',
    'finance.reports' => '/finance/reports',
    'operations.library' => '/operations/library',
    'operations.library-issue' => '/operations/library/issue',
    'operations.transport' => '/operations/transport',
    'operations.vehicles' => '/operations/transport/vehicles',
    'operations.hostel' => '/operations/hostel',
    'operations.staff' => '/operations/staff',
    'operations.leave' => '/operations/leave',
    'operations.payroll' => '/operations/payroll',
    'operations.notices' => '/operations/notices',
    'portal.parent' => '/portal/parent',
    'portal.student' => '/portal/student',
    'teacher.dashboard' => '/teacher/dashboard',
    'academics.settings' => '/academics/settings',
];

$adminUser = \App\Models\User::where('email', 'admin@npa.ac.ke')->first();

$errors = [];
foreach ($routesToTest as $name => $uri) {
    // Create new fresh app & kernel per request to avoid session state bleeding
    $app = require __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();

    $request = \Illuminate\Http\Request::create($uri, 'GET');
    $app->instance('request', $request);
    \Illuminate\Support\Facades\Auth::login($adminUser);
    \Illuminate\Support\Facades\Session::put('active_school_id', $adminUser->school_id);
    
    try {
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        if ($status >= 400) {
            $content = $response->getContent();
            $errors[] = "FAIL: $name ($uri) -> HTTP $status: " . substr(strip_tags($content), 0, 150);
        } else {
            echo "OK: $name ($uri) -> HTTP $status\n";
        }
    } catch (\Throwable $e) {
        $errors[] = "EXCEPTION: $name ($uri) -> " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
    }
}

if (!empty($errors)) {
    echo "\n=== ERRORS FOUND ===\n" . implode("\n", $errors) . "\n";
} else {
    echo "\n=== ALL MENU ROUTES PASSED! ===\n";
}
