<?php

use App\Http\Controllers\AcademicController;
use App\Http\Controllers\AIHelpController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExaminationController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\HumanResourceController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LearningPracticeController;
use App\Http\Controllers\OperationController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SuperAdminLegalController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WardenController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Public & Authentication routes
Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');

Route::get('/school/register', [SuperAdminController::class, 'showPublicSchoolRegistration'])->name('school.register');
Route::get('/register', [SuperAdminController::class, 'showPublicSchoolRegistration'])->name('register');
Route::post('/school/register/search', [SuperAdminController::class, 'searchPublicSchoolRegistration'])->name('school.register.search');
Route::post('/school/register', [SuperAdminController::class, 'storePublicSchool'])->name('school.register.store');
Route::get('/school/payment/{school}', [SuperAdminController::class, 'paymentPage'])->name('school.payment');
Route::post('/school/payment/{school}/mpesa', [SuperAdminController::class, 'initiateMpesaSchoolPayment'])->name('school.payment.mpesa');
Route::post('/school/payment/{school}/paystack', [SuperAdminController::class, 'initiatePaystackSchoolPayment'])->name('school.payment.paystack');
Route::get('/school/payment/{school}/paystack/verify', [SuperAdminController::class, 'verifyPaystackSchoolPayment'])->name('school.payment.paystack.verify');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Legal Compliance & Help Center Public Pages
Route::get('/privacy-policy', [LegalController::class, 'privacyPolicy'])->name('privacy.policy');
Route::get('/terms-and-conditions', [LegalController::class, 'terms'])->name('terms.conditions');
Route::get('/help-center', [LegalController::class, 'helpCenter'])->name('help.center');

// Account Status Pages — must be reachable even when suspended/expired
Route::middleware(['auth'])->group(function () {
    Route::get('/account-suspended', function () {
        $school = Auth::user()?->school ?? \App\Models\School::find(session('active_school_id'));
        $subscription = $school ? \App\Models\Subscription::withoutGlobalScopes()->where('school_id', $school->id)->latest()->with('package')->first() : null;
        return response()->view('errors.account-suspended', compact('school', 'subscription'), 403);
    })->name('school.suspended');

    Route::get('/account-expired', function () {
        $school = Auth::user()?->school ?? \App\Models\School::find(session('active_school_id'));
        $subscription = $school ? \App\Models\Subscription::withoutGlobalScopes()->where('school_id', $school->id)->latest()->with('package')->first() : null;
        return response()->view('errors.account-expired', compact('school', 'subscription'), 403);
    })->name('school.expired');
});

// AI Help Assistant Endpoints
Route::post('/api/ai-help/ask', [AIHelpController::class, 'ask'])->middleware('throttle:ai-help')->name('ai-help.ask');
Route::get('/api/ai-help/suggestions', [AIHelpController::class, 'suggestions'])->middleware('throttle:60,1')->name('ai-help.suggestions');
Route::post('/api/ai-help/feedback', [AIHelpController::class, 'feedback'])->middleware('throttle:ai-help')->name('ai-help.feedback');

// Quick Demo Role Switcher
Route::get('/auth/quick-login/{userId}', [AuthController::class, 'quickLogin'])->name('auth.quick-login');
Route::post('/auth/switch-school', [AuthController::class, 'switchSchool'])->name('auth.switch-school');

// Daraja M-Pesa Callback (Exempt from CSRF) — Student School Fees (Unchanged)
Route::post('/mpesa/callback', [FinanceController::class, 'mpesaCallback'])->name('mpesa.callback');
// SaaS Subscription M-Pesa Callback — Separate from Student Fees
Route::post('/mpesa/subscription-callback', function (\Illuminate\Http\Request $request) {
    $mpesaService = app(\App\Services\MpesaDarajaService::class);
    $mpesaService->processSubscriptionCallback($request->all());
    return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
})->name('mpesa.subscription.callback');


// Authenticated Routes
Route::middleware(['auth', 'tenant'])->group(function () {

    // MODULE 1: Super Admin HQ
    Route::prefix('superadmin')->name('superadmin.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

        // School management
        Route::get('/schools', [SuperAdminController::class, 'schools'])->name('schools');
        Route::get('/schools/create', [SuperAdminController::class, 'createSchool'])->name('schools.create');
        Route::post('/schools', [SuperAdminController::class, 'storeSchool'])->name('schools.store');
        Route::get('/schools/{school}/account', [SuperAdminController::class, 'schoolAccount'])->name('schools.account');

        // School lifecycle actions
        Route::post('/schools/{school}/suspend', [SuperAdminController::class, 'suspendSchool'])->name('schools.suspend');
        Route::post('/schools/{school}/reactivate', [SuperAdminController::class, 'reactivateSchool'])->name('schools.reactivate');
        Route::post('/schools/{school}/cancel', [SuperAdminController::class, 'cancelSchool'])->name('schools.cancel');
        Route::post('/schools/{school}/change-package', [SuperAdminController::class, 'changePackage'])->name('schools.change-package');
        Route::post('/schools/{school}/extend-subscription', [SuperAdminController::class, 'extendSubscription'])->name('schools.extend-subscription');
        Route::post('/schools/{school}/allocate-population', [SuperAdminController::class, 'allocatePopulation'])->name('schools.allocate-population');
        Route::post('/schools/{school}/feature', [SuperAdminController::class, 'updateFeature'])->name('schools.feature.update');
        Route::post('/schools/{school}/feature/reset', [SuperAdminController::class, 'resetFeature'])->name('schools.feature.reset');
        Route::post('/schools/{school}/record-payment', [SuperAdminController::class, 'recordManualPayment'])->name('schools.record-payment');

        // Packages
        Route::get('/packages', [SuperAdminController::class, 'packages'])->name('packages');
        Route::get('/packages/create', [SuperAdminController::class, 'createPackage'])->name('packages.create');
        Route::post('/packages', [SuperAdminController::class, 'storePackage'])->name('packages.store');
        Route::get('/packages/{package}/edit', [SuperAdminController::class, 'editPackage'])->name('packages.edit');
        Route::put('/packages/{package}', [SuperAdminController::class, 'updatePackage'])->name('packages.update');
        Route::post('/packages/{package}/toggle', [SuperAdminController::class, 'togglePackage'])->name('packages.toggle');
        Route::post('/packages/{package}/duplicate', [SuperAdminController::class, 'duplicatePackage'])->name('packages.duplicate');

        // Subscriptions
        Route::get('/subscriptions', [SuperAdminController::class, 'subscriptions'])->name('subscriptions');

        // Billing (SaaS only — separate from student fees)
        Route::get('/billing', [SuperAdminController::class, 'billing'])->name('billing');

        // Usage aggregates
        Route::get('/usage', [SuperAdminController::class, 'usage'])->name('usage');

        // Audit logs
        Route::get('/audit-logs', [SuperAdminController::class, 'auditLogs'])->name('audit-logs');

        // Platform settings
        Route::get('/settings', [SuperAdminController::class, 'platformSettings'])->name('settings');
        Route::post('/settings', [SuperAdminController::class, 'updatePlatformSettings'])->name('settings.update');

        // Users (platform-level only: Super Admin + School Admins)
        Route::get('/users', [SuperAdminController::class, 'users'])->name('users');

        // MODULE 1B: Legal Documents & Compliance
        Route::get('/legal/privacy', [SuperAdminLegalController::class, 'privacyEdit'])->name('legal.privacy');
        Route::post('/legal/privacy', [SuperAdminLegalController::class, 'privacyUpdate'])->name('legal.privacy.update');
        Route::get('/legal/terms', [SuperAdminLegalController::class, 'termsEdit'])->name('legal.terms');
        Route::post('/legal/terms', [SuperAdminLegalController::class, 'termsUpdate'])->name('legal.terms.update');

        // MODULE 1C: Help Articles (Knowledge Base)
        Route::get('/help-articles', [SuperAdminLegalController::class, 'articlesIndex'])->name('help-articles.index');
        Route::get('/help-articles/create', [SuperAdminLegalController::class, 'articlesCreate'])->name('help-articles.create');
        Route::post('/help-articles', [SuperAdminLegalController::class, 'articlesStore'])->name('help-articles.store');
        Route::get('/help-articles/{article}/edit', [SuperAdminLegalController::class, 'articlesEdit'])->name('help-articles.edit');
        Route::put('/help-articles/{article}', [SuperAdminLegalController::class, 'articlesUpdate'])->name('help-articles.update');
        Route::delete('/help-articles/{article}', [SuperAdminLegalController::class, 'articlesDestroy'])->name('help-articles.destroy');

        // MODULE 1D: AI Help Assistant Settings & Audit Logs
        Route::get('/ai-assistant/settings', [SuperAdminLegalController::class, 'aiSettings'])->name('ai-assistant.settings');
        Route::post('/ai-assistant/settings', [SuperAdminLegalController::class, 'updateAiSettings'])->name('ai-assistant.settings.update');
        Route::get('/ai-assistant/logs', [SuperAdminLegalController::class, 'aiLogs'])->name('ai-assistant.logs');
    });


    // Super Admin Hyphenated Aliases for sidebar compatibility
    Route::prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/schools', [SuperAdminController::class, 'schools'])->name('schools');
        Route::get('/subscriptions', [SuperAdminController::class, 'subscriptions'])->name('subscriptions');
        Route::get('/users', [SuperAdminController::class, 'users'])->name('users');
    });

    // MODULE 2: School Admin & Principal Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/school/dashboard', [DashboardController::class, 'index'])->name('school.dashboard');

    // MODULE 3: Student Registration & Admissions
    Route::prefix('students')->name('students.')->middleware('school.feature:student_management')->group(function () {
        Route::get('/search', [StudentController::class, 'search'])->name('search');
        Route::get('/promotions', [StudentController::class, 'promotions'])->name('promotions');
        Route::post('/promotions', [StudentController::class, 'processPromotions'])->name('promotions.process');
        Route::get('/documents', [StudentController::class, 'documents'])->name('documents');
        Route::get('/import', [StudentImportController::class, 'showImportForm'])->name('import');
        Route::post('/import', [StudentImportController::class, 'import'])->name('import.process');
        Route::get('/import/template', [StudentImportController::class, 'downloadTemplate'])->name('import.template');

        Route::get('/', [StudentController::class, 'index'])->name('index');
        Route::get('/create', [StudentController::class, 'create'])->name('create');
        Route::post('/', [StudentController::class, 'store'])->name('store');
        Route::get('/{student}', [StudentController::class, 'show'])->name('show');
        Route::get('/{student}/edit', [StudentController::class, 'edit'])->name('edit');
        Route::put('/{student}', [StudentController::class, 'update'])->name('update');
        Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy');
    });

    // MODULE 4: Academics (CBC & 8-4-4)
    Route::prefix('academics')->name('academics.')->middleware('school.feature:student_management')->group(function () {
        Route::get('/', [AcademicController::class, 'index'])->name('index');
        Route::get('/classes', [AcademicController::class, 'classes'])->name('classes');
        Route::get('/subjects', [AcademicController::class, 'subjects'])->name('subjects');
        Route::get('/curriculum', [AcademicController::class, 'curriculum'])->name('curriculum');
        Route::get('/teachers', [AcademicController::class, 'teachers'])->name('teachers');
        Route::get('/teacher-subjects', [AcademicController::class, 'teacherSubjects'])->name('teacher-subjects');
        Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable');
        Route::match(['GET', 'POST'], '/timetable/generate', [TimetableController::class, 'generate'])->name('timetable.generate');
        Route::post('/timetable/settings', [TimetableController::class, 'saveSettings'])->name('timetable.settings.save');
        Route::post('/timetable/repair', [TimetableController::class, 'repair'])->name('timetable.repair');
        Route::get('/settings', [AcademicController::class, 'settings'])->name('settings');
        Route::post('/settings', [AcademicController::class, 'updateSettings'])->name('settings.update');
        Route::post('/settings/academic-year', [AcademicController::class, 'storeAcademicYear'])->name('settings.academic-year.store');
        Route::post('/settings/academic-year/{year}/activate', [AcademicController::class, 'activateAcademicYear'])->name('settings.academic-year.activate');
        Route::post('/settings/academic-term', [AcademicController::class, 'storeAcademicTerm'])->name('settings.academic-term.store');
        Route::post('/settings/academic-term/{term}/activate', [AcademicController::class, 'activateAcademicTerm'])->name('settings.academic-term.activate');

        Route::post('/classes', [AcademicController::class, 'storeClass'])->name('classes.store');
        Route::put('/classes/{class}', [AcademicController::class, 'updateClass'])->name('classes.update');
        Route::delete('/classes/{class}', [AcademicController::class, 'destroyClass'])->name('classes.destroy');
        Route::post('/streams', [AcademicController::class, 'storeStream'])->name('streams.store');
        Route::post('/streams/{id}', [AcademicController::class, 'updateStream'])->name('streams.update');
        Route::post('/subjects', [AcademicController::class, 'storeSubject'])->name('subjects.store');
        Route::put('/subjects/{subject}', [AcademicController::class, 'updateSubject'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [AcademicController::class, 'destroySubject'])->name('subjects.destroy');

        Route::get('/subject-offerings', [AcademicController::class, 'subjectOfferings'])->name('subject-offerings');
        Route::post('/subject-offerings', [AcademicController::class, 'storeSubjectOffering'])->name('subject-offerings.store');
        Route::delete('/subject-offerings/{offering}', [AcademicController::class, 'destroySubjectOffering'])->name('subject-offerings.destroy');

        Route::post('/allocations', [AcademicController::class, 'storeAllocation'])->name('allocations.store');
        Route::delete('/allocations/{allocation}', [AcademicController::class, 'destroyAllocation'])->name('allocations.destroy');
    });

    // MODULE 5: Timetable Generator (Flagship)
    Route::prefix('timetable')->name('timetable.')->middleware('school.feature:timetable')->group(function () {
        Route::get('/', [TimetableController::class, 'index'])->name('index');
        Route::match(['GET', 'POST'], '/generate', [TimetableController::class, 'generate'])->name('generate');
        Route::post('/settings', [TimetableController::class, 'saveSettings'])->name('settings.save');
        Route::post('/repair', [TimetableController::class, 'repair'])->name('repair');
        Route::get('/print', [TimetableController::class, 'print'])->name('print');
        Route::post('/{timetable}/publish', [TimetableController::class, 'publish'])->name('publish');
        Route::post('/{timetable}/archive', [TimetableController::class, 'archive'])->name('archive');
        Route::delete('/{timetable}', [TimetableController::class, 'destroy'])->name('destroy');
        Route::get('/readiness', [TimetableController::class, 'readiness'])->name('readiness');
    });

    // MODULE 6: Attendance Management
    Route::prefix('attendance')->name('attendance.')->middleware('school.feature:attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/report', [AttendanceController::class, 'report'])->name('report');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('/teachers', [AttendanceController::class, 'teachers'])->name('teachers');
        Route::post('/teachers', [AttendanceController::class, 'storeTeacher'])->name('teachers.store');
    });

    // MODULE 7: Fees Management & M-Pesa
    Route::prefix('finance')->name('finance.')->middleware('school.feature:finance')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::get('/dashboard', [FinanceController::class, 'index'])->name('dashboard');
        Route::get('/payments', [FinanceController::class, 'payments'])->name('payments');
        Route::get('/students/search', [FinanceController::class, 'searchStudents'])->name('students.search');
        Route::get('/fees', [FinanceController::class, 'fees'])->name('fees');
        Route::get('/invoices', [FinanceController::class, 'invoices'])->name('invoices');
        Route::get('/reports', [FinanceController::class, 'reports'])->name('reports');
        Route::get('/mpesa', [FinanceController::class, 'mpesa'])->name('mpesa');
        Route::get('/receipt/{id}', [FinanceController::class, 'receipt'])->name('receipt');
        Route::post('/receipt/{id}/resend', [FinanceController::class, 'resendReceipt'])->name('receipt.resend');
        Route::get('/arrears', [FinanceController::class, 'arrears'])->name('arrears');
        Route::get('/student/{id}/statement', [FinanceController::class, 'studentStatement'])->name('student-statement');
        Route::get('/reconciliation', [PaymentGatewayController::class, 'reconciliation'])->name('reconciliation');
        Route::get('/gateway-settings', [PaymentGatewayController::class, 'settings'])->name('gateway-settings');
        Route::post('/gateway-settings', [PaymentGatewayController::class, 'updateSettings'])->name('gateway-settings.update');
        Route::post('/bank-account', [PaymentGatewayController::class, 'storeBankAccount'])->name('bank-account.store');
        Route::post('/bank-account/{bankAccount}/toggle', [PaymentGatewayController::class, 'toggleBankAccount'])->name('bank-account.toggle');
        Route::post('/bank-account/{bankAccount}/primary', [PaymentGatewayController::class, 'setPrimaryBankAccount'])->name('bank-account.primary');
        Route::delete('/bank-account/{bankAccount}', [PaymentGatewayController::class, 'destroyBankAccount'])->name('bank-account.destroy');
        Route::post('/reminder', [FinanceController::class, 'sendReminder'])->name('reminder.send');

        Route::post('/structure', [FinanceController::class, 'storeStructure'])->name('structure.store');
        Route::post('/category', [FinanceController::class, 'storeCategory'])->name('category.store');
        Route::post('/payment', [FinanceController::class, 'storePayment'])->name('payment.store');
        Route::post('/store-payment', [FinanceController::class, 'storePayment'])->name('store-payment');
        Route::post('/mpesa-stk', [FinanceController::class, 'triggerMpesa'])->name('mpesa.stk');
    });

    // MODULE 8: Examination & Report Cards
    Route::prefix('examinations')->name('examinations.')->middleware('school.feature:examinations')->group(function () {
        Route::get('/', [ExaminationController::class, 'index'])->name('index');
        Route::post('/', [ExaminationController::class, 'store'])->name('store');
        Route::get('/marks', [ExaminationController::class, 'generalMarks'])->name('marks');
        Route::post('/store-marks', [ExaminationController::class, 'storeMarksGeneral'])->name('store-marks');
        Route::get('/{id}/marks', [ExaminationController::class, 'marks'])->name('marks.entry');
        Route::post('/{id}/marks', [ExaminationController::class, 'storeMarks'])->name('marks.store');
        Route::get('/results', [ExaminationController::class, 'results'])->name('results');
        Route::get('/report-cards', [ExaminationController::class, 'reportCardsList'])->name('report-cards');
        Route::get('/student/{id}/results', [ExaminationController::class, 'studentResults'])->name('student-results');
        Route::get('/{examId}/report-card/{studentId}', [ExaminationController::class, 'reportCard'])->name('report-card');
        Route::get('/{examId}/report_card/{studentId}', [ExaminationController::class, 'reportCard'])->name('report_card');
    });

    // MODULE 9: Portals
    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('/parent', [PortalController::class, 'parent'])->middleware('school.feature:parent_portal')->name('parent');
        Route::post('/parent/pay', [PaymentGatewayController::class, 'parentInitiatePayment'])->middleware('school.feature:parent_portal')->name('parent.pay');
        Route::get('/teacher', [PortalController::class, 'teacher'])->middleware('school.feature:teacher_management')->name('teacher');
        Route::get('/student', [PortalController::class, 'student'])->middleware('school.feature:student_portal')->name('student');
        Route::get('/student/diary', [PortalController::class, 'studentDiary'])->middleware('school.feature:student_portal')->name('student.diary');
        Route::get('/parent/diary', [PortalController::class, 'parentDiary'])->middleware('school.feature:parent_portal')->name('parent.diary');
        Route::post('/diary/announcements/{announcement}/read', [PortalController::class, 'markAnnouncementRead'])->name('diary.announcement.read');
        Route::get('/diary/materials/{material}/download', [PortalController::class, 'downloadDiaryAttachment'])->name('diary.attachment');
        Route::get('/student/performance', [PortalController::class, 'studentPerformance'])->middleware('school.feature:student_portal')->name('student.performance');
        Route::get('/parent/performance', [PortalController::class, 'parentPerformance'])->middleware('school.feature:parent_portal')->name('parent.performance');
        Route::get('/student/practice', [LearningPracticeController::class, 'index'])->middleware('school.feature:student_portal')->name('practice');
        Route::post('/student/practice/questions/{question}/answer', [LearningPracticeController::class, 'answer'])->middleware('school.feature:student_portal')->name('practice.answer');
        Route::get('/practice/content', [LearningPracticeController::class, 'manage'])->middleware('school.feature:student_management')->name('practice.content');
        Route::post('/practice/content/chapters', [LearningPracticeController::class, 'storeChapter'])->middleware('school.feature:student_management')->name('practice.content.chapters.store');
        Route::put('/practice/content/chapters/{chapter}', [LearningPracticeController::class, 'updateChapter'])->middleware('school.feature:student_management')->name('practice.content.chapters.update');
        Route::post('/practice/content/questions', [LearningPracticeController::class, 'storeQuestion'])->middleware('school.feature:student_management')->name('practice.content.questions.store');
        Route::put('/practice/content/questions/{question}', [LearningPracticeController::class, 'updateQuestion'])->middleware('school.feature:student_management')->name('practice.content.questions.update');
    });
    Route::get('/parent/dashboard', [PortalController::class, 'parent'])->middleware('school.feature:parent_portal')->name('parent.dashboard');
    Route::post('/parent/dashboard/pay', [PaymentGatewayController::class, 'parentInitiatePayment'])->middleware('school.feature:parent_portal')->name('parent.dashboard.pay');
    Route::get('/teacher/dashboard', [PortalController::class, 'teacher'])->middleware('school.feature:teacher_management')->name('teacher.dashboard');
    Route::get('/student/dashboard', [PortalController::class, 'student'])->middleware('school.feature:student_portal')->name('student.dashboard');

    // MODULE 10-20: Operations
    Route::prefix('operations')->name('operations.')->group(function () {
        Route::get('/library', [OperationController::class, 'library'])->middleware('school.feature:library')->name('library');
        Route::get('/library/issue', [OperationController::class, 'libraryIssue'])->middleware('school.feature:library')->name('library-issue');
        Route::get('/transport', [OperationController::class, 'transport'])->middleware('school.feature:transport')->name('transport');
        Route::get('/transport/vehicles', [OperationController::class, 'vehicles'])->middleware('school.feature:transport')->name('vehicles');
        Route::get('/hostel', [OperationController::class, 'hostel'])->middleware('school.feature:hostel')->name('hostel');
        // Staff Directory
        Route::get('/staff', [HumanResourceController::class, 'staffIndex'])->middleware('school.feature:teacher_management')->name('staff');
        Route::post('/staff', [HumanResourceController::class, 'storeEmployee'])->middleware('school.feature:teacher_management')->name('staff.store');
        Route::get('/staff/export', [HumanResourceController::class, 'exportStaff'])->middleware('school.feature:teacher_management')->name('staff.export');
        Route::get('/staff/{employee}', [HumanResourceController::class, 'showEmployee'])->middleware('school.feature:teacher_management')->name('staff.show');
        Route::put('/staff/{employee}', [HumanResourceController::class, 'updateEmployee'])->middleware('school.feature:teacher_management')->name('staff.update');
        Route::post('/staff/{employee}/status', [HumanResourceController::class, 'updateStatus'])->middleware('school.feature:teacher_management')->name('staff.status');
        Route::post('/staff/{employee}/documents', [HumanResourceController::class, 'uploadDocument'])->middleware('school.feature:teacher_management')->name('staff.document');

        // Leave Management
        Route::get('/leave', [HumanResourceController::class, 'leaveIndex'])->middleware('school.feature:teacher_management')->name('leave');
        Route::post('/leave/apply', [HumanResourceController::class, 'applyLeave'])->middleware('school.feature:teacher_management')->name('leave.apply');
        Route::post('/leave/{id}/approve', [HumanResourceController::class, 'approveLeave'])->middleware('school.feature:teacher_management')->name('leave.approve');
        Route::post('/leave/{id}/reject', [HumanResourceController::class, 'rejectLeave'])->middleware('school.feature:teacher_management')->name('leave.reject');
        Route::post('/leave/{id}/cancel', [HumanResourceController::class, 'cancelLeave'])->middleware('school.feature:teacher_management')->name('leave.cancel');

        // Payroll
        Route::get('/payroll', [HumanResourceController::class, 'payrollIndex'])->middleware('school.feature:finance')->name('payroll');
        Route::post('/payroll/periods', [HumanResourceController::class, 'createPeriod'])->middleware('school.feature:finance')->name('payroll.periods.store');
        Route::post('/payroll/periods/{id}/calculate', [HumanResourceController::class, 'calculatePeriod'])->middleware('school.feature:finance')->name('payroll.periods.calculate');
        Route::post('/payroll/periods/{id}/submit', [HumanResourceController::class, 'submitPeriodApproval'])->middleware('school.feature:finance')->name('payroll.periods.submit');
        Route::post('/payroll/periods/{id}/approve', [HumanResourceController::class, 'approvePeriod'])->middleware('school.feature:finance')->name('payroll.periods.approve');
        Route::post('/payroll/periods/{id}/finalize', [HumanResourceController::class, 'finalizePeriod'])->middleware('school.feature:finance')->name('payroll.periods.finalize');
        Route::get('/payroll/periods/{id}/export', [HumanResourceController::class, 'exportPayrollRegister'])->middleware('school.feature:finance')->name('payroll.periods.export');
        Route::post('/payroll/records/{id}/pay', [HumanResourceController::class, 'recordPayment'])->middleware('school.feature:finance')->name('payroll.records.pay');
        Route::get('/payroll/records/{id}/payslip', [HumanResourceController::class, 'showPayslip'])->middleware('school.feature:finance')->name('payroll.records.payslip');
        Route::get('/notices', [OperationController::class, 'communication'])->middleware('school.feature:communication')->name('notices');
        Route::get('/materials', [OperationController::class, 'materials'])->middleware('school.feature:student_management')->name('materials');
        Route::post('/materials', [OperationController::class, 'storeMaterial'])->middleware('school.feature:student_management')->name('materials.store');
        Route::get('/reports', [OperationController::class, 'reports'])->middleware('school.feature:reports')->name('reports');
    });

    // Legacy / direct operation routes
    // Library Management
    Route::prefix('library')->name('library.')->middleware(['school.feature:library', 'role:super-admin,school-admin,principal,deputy-principal,librarian'])->group(function () {
        Route::get('/', [OperationController::class, 'libraryDashboard'])->name('index');
        Route::get('/dashboard', [OperationController::class, 'libraryDashboard'])->name('dashboard');
        Route::get('/books', [OperationController::class, 'libraryBooks'])->name('books');
        Route::post('/books', [OperationController::class, 'storeBook'])->name('books.store');
        Route::get('/categories', [OperationController::class, 'libraryCategories'])->name('categories');
        Route::post('/categories', [OperationController::class, 'storeBookCategory'])->name('categories.store');
        Route::get('/issue', [OperationController::class, 'libraryIssueForm'])->name('issue.form');
        Route::post('/issue', [OperationController::class, 'issueBook'])->name('issue');
        Route::get('/returns', [OperationController::class, 'libraryReturns'])->name('returns');
        Route::post('/return/{id}', [OperationController::class, 'returnBook'])->name('return');
        Route::get('/history', [OperationController::class, 'libraryHistory'])->name('history');
        Route::get('/reports', [OperationController::class, 'libraryReports'])->name('reports');
    });

    Route::prefix('transport')->name('transport.')->middleware('school.feature:transport')->group(function () {
        Route::get('/', [OperationController::class, 'transport'])->name('index');
        Route::post('/routes', [OperationController::class, 'storeTransportRoute'])->name('routes.store');
        Route::post('/assign', [OperationController::class, 'assignTransport'])->name('assign');
    });

    Route::prefix('hostel')->name('hostel.')->middleware('school.feature:hostel')->group(function () {
        Route::get('/', [OperationController::class, 'hostel'])->name('index');
        Route::post('/dormitories', [OperationController::class, 'storeDormitory'])->name('dormitories.store');
        Route::post('/assign', [OperationController::class, 'assignHostel'])->name('assign');
    });

    // MODULE 23: Hostel Warden Portal
    Route::prefix('warden')->name('warden.')->middleware('role:hostel-warden')->group(function () {
        Route::get('/dashboard', [WardenController::class, 'dashboard'])->name('dashboard');
    });

    Route::prefix('communication')->name('communication.')->middleware('school.feature:communication')->group(function () {
        Route::get('/', [OperationController::class, 'communication'])->name('index');
        Route::post('/announcements', [OperationController::class, 'storeAnnouncement'])->name('announcements.store');
        Route::post('/send-sms', [OperationController::class, 'sendSms'])->name('send_sms');
    });

    Route::prefix('discipline')->name('discipline.')->middleware('school.feature:student_management')->group(function () {
        Route::get('/', [OperationController::class, 'discipline'])->name('index');
        Route::post('/', [OperationController::class, 'storeDiscipline'])->name('store');
    });

    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [OperationController::class, 'inventory'])->name('index');
        Route::post('/', [OperationController::class, 'storeAsset'])->name('store');
    });

    Route::prefix('hr')->name('hr.')->middleware('school.feature:teacher_management')->group(function () {
        Route::get('/', [HumanResourceController::class, 'staffIndex'])->name('index');
        Route::post('/employees', [HumanResourceController::class, 'storeEmployee'])->name('employees.store');
    });

    Route::prefix('materials')->name('materials.')->middleware('school.feature:student_management')->group(function () {
        Route::get('/', [OperationController::class, 'materials'])->name('index');
        Route::post('/', [OperationController::class, 'storeMaterial'])->name('store');
    });

    Route::get('/reports', [OperationController::class, 'reports'])->middleware('school.feature:reports')->name('reports.index');

    // MODULE 21: School Staff & User Accounts Management
    // Restricted to School Admin / Principal — explicitly blocks Hostel Warden and other limited roles
    Route::prefix('school')->name('school.')->middleware('role:super-admin,school-admin,principal')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::post('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    });

    // MODULE 22: User Profile & Password Change
    Route::get('/profile/change-password', [UserManagementController::class, 'showChangePassword'])->name('profile.change-password');
    Route::post('/profile/change-password', [UserManagementController::class, 'updatePassword'])->name('profile.change-password.update');
});
