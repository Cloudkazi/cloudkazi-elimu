<?php

namespace Tests\Feature;

use App\Http\Controllers\FinanceController;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\User;
use App\Services\MpesaDarajaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class FinanceMultiTenantTest extends TestCase
{
    private School $schoolA;
    private School $schoolB;
    private User $userA;
    private User $userB;
    private FinanceController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolA = School::create([
            'name' => 'School Alpha',
            'code' => 'SCH-A-' . uniqid(),
            'status' => 'active',
        ]);

        $this->schoolB = School::create([
            'name' => 'School Beta',
            'code' => 'SCH-B-' . uniqid(),
            'status' => 'active',
        ]);

        $this->userA = User::create([
            'name' => 'Admin Alpha',
            'email' => 'alpha_' . uniqid() . '@school.test',
            'password' => bcrypt('secret123'),
            'school_id' => $this->schoolA->id,
        ]);

        $this->userB = User::create([
            'name' => 'Admin Beta',
            'email' => 'beta_' . uniqid() . '@school.test',
            'password' => bcrypt('secret123'),
            'school_id' => $this->schoolB->id,
        ]);

        $this->controller = new FinanceController(app(MpesaDarajaService::class));
    }

    public function test_fee_category_creation_and_scoping(): void
    {
        Auth::login($this->userA);
        Session::put('active_school_id', $this->schoolA->id);

        $request = Request::create('/finance/category', 'POST', [
            'name' => 'Tuition Alpha',
            'description' => 'Tuition for School Alpha',
        ]);

        $this->controller->storeCategory($request);

        $this->assertDatabaseHas('fee_categories', [
            'name' => 'Tuition Alpha',
            'school_id' => $this->schoolA->id,
        ]);

        // Verify School B does not see School A categories
        Session::put('active_school_id', $this->schoolB->id);
        Auth::login($this->userB);

        $view = $this->controller->fees();
        $categoriesForSchoolB = $view->getData()['categories'];

        $this->assertFalse($categoriesForSchoolB->contains('name', 'Tuition Alpha'));
    }

    public function test_fee_structure_requires_owned_class_and_category(): void
    {
        Auth::login($this->userA);
        Session::put('active_school_id', $this->schoolA->id);

        $yearA = AcademicYear::create(['name' => '2026', 'school_id' => $this->schoolA->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $termA = AcademicTerm::create(['name' => 'Term 1', 'academic_year_id' => $yearA->id, 'school_id' => $this->schoolA->id, 'start_date' => '2026-01-01', 'end_date' => '2026-04-30', 'status' => 'active']);
        $classA = SchoolClass::create(['name' => 'Grade 1', 'school_id' => $this->schoolA->id, 'level' => 'junior_school', 'curriculum' => 'CBC']);
        $catA = FeeCategory::create(['name' => 'Tuition', 'school_id' => $this->schoolA->id]);

        // Cross-school class belonging to School B
        $classB = SchoolClass::create(['name' => 'Grade 1 Beta', 'school_id' => $this->schoolB->id, 'level' => 'junior_school', 'curriculum' => 'CBC']);

        // Attempt to create structure in School A using School B's class
        $request = Request::create('/finance/structure', 'POST', [
            'academic_year_id' => $yearA->id,
            'term_id' => $termA->id,
            'school_class_id' => $classB->id,
            'fee_category_id' => $catA->id,
            'amount' => 15000,
        ]);

        $response = $this->controller->storeStructure($request);
        $this->assertTrue(session()->has('errors'));

        $this->assertDatabaseMissing('fee_structures', [
            'school_id' => $this->schoolA->id,
            'school_class_id' => $classB->id,
        ]);
    }

    public function test_payment_updates_invoice_balance_transactionally(): void
    {
        Auth::login($this->userA);
        Session::put('active_school_id', $this->schoolA->id);

        $year = AcademicYear::create(['name' => '2026', 'school_id' => $this->schoolA->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        $term = AcademicTerm::create(['name' => 'Term 1', 'academic_year_id' => $year->id, 'school_id' => $this->schoolA->id, 'start_date' => '2026-01-01', 'end_date' => '2026-04-30', 'status' => 'active']);
        $class = SchoolClass::create(['name' => 'Grade 1', 'school_id' => $this->schoolA->id, 'level' => 'junior_school', 'curriculum' => 'CBC']);

        $student = Student::create([
            'school_id' => $this->schoolA->id,
            'school_class_id' => $class->id,
            'admission_number' => 'ADM-' . uniqid(),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'Male',
            'dob' => '2015-05-10',
            'admission_date' => '2026-01-05',
            'status' => 'active',
        ]);

        $invoice = StudentInvoice::create([
            'school_id' => $this->schoolA->id,
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'invoice_number' => 'INV-' . uniqid(),
            'total_amount' => 20000,
            'paid_amount' => 0,
            'waiver_amount' => 0,
            'balance' => 20000,
            'status' => 'unpaid',
        ]);

        $request = Request::create('/finance/payment', 'POST', [
            'student_id' => $student->id,
            'amount' => 7500,
            'payment_method' => 'mpesa',
            'reference_code' => 'QWE123RTY',
            'payment_date' => '2026-09-21',
            'notes' => 'Partial term payment',
        ]);

        $response = $this->controller->storePayment($request);
        $this->assertEquals(302, $response->getStatusCode());

        $payment = \App\Models\FeePayment::where('student_id', $student->id)->firstOrFail();
        $this->assertEquals(20000, (float) $payment->balance_before);
        $this->assertEquals(12500, (float) $payment->balance_after);

        $invoice->refresh();
        $this->assertEquals(7500, (float) $invoice->paid_amount);
        $this->assertEquals(12500, (float) $invoice->balance);
        $this->assertEquals('partially_paid', $invoice->status);

        $this->assertDatabaseHas('fee_payments', [
            'school_id' => $this->schoolA->id,
            'student_id' => $student->id,
            'amount' => 7500,
            'reference_code' => 'QWE123RTY',
            'student_invoice_id' => $invoice->id,
        ]);
    }

    public function test_cross_school_payment_is_blocked(): void
    {
        Auth::login($this->userA);
        Session::put('active_school_id', $this->schoolA->id);

        $classB = SchoolClass::create(['name' => 'Grade 1 Beta', 'school_id' => $this->schoolB->id, 'level' => 'junior_school', 'curriculum' => 'CBC']);
        $studentB = Student::create([
            'school_id' => $this->schoolB->id,
            'school_class_id' => $classB->id,
            'admission_number' => 'ADM-B-' . uniqid(),
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 'Female',
            'dob' => '2016-03-20',
            'admission_date' => '2026-01-05',
            'status' => 'active',
        ]);

        $request = Request::create('/finance/payment', 'POST', [
            'student_id' => $studentB->id,
            'amount' => 5000,
            'payment_method' => 'cash',
            'reference_code' => 'CASH-001',
            'payment_date' => '2026-09-21',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->controller->storePayment($request);
    }

    public function test_student_payment_search_returns_only_active_students_from_current_school(): void
    {
        $classA = SchoolClass::create(['name' => 'Grade 1', 'school_id' => $this->schoolA->id, 'level' => 'junior_school', 'curriculum' => 'CBC']);
        $classB = SchoolClass::create(['name' => 'Grade 1 Beta', 'school_id' => $this->schoolB->id, 'level' => 'junior_school', 'curriculum' => 'CBC']);
        $searchName = 'PaymentSearch' . uniqid();

        $studentA = Student::create([
            'school_id' => $this->schoolA->id,
            'school_class_id' => $classA->id,
            'admission_number' => 'PAY-SEARCH-A-' . uniqid(),
            'first_name' => $searchName,
            'last_name' => 'Student',
            'gender' => 'Female',
            'dob' => '2016-03-20',
            'admission_date' => '2026-01-05',
            'status' => 'active',
        ]);

        Student::create([
            'school_id' => $this->schoolB->id,
            'school_class_id' => $classB->id,
            'admission_number' => 'PAY-SEARCH-B-' . uniqid(),
            'first_name' => $searchName,
            'last_name' => 'Student',
            'gender' => 'Female',
            'dob' => '2016-03-20',
            'admission_date' => '2026-01-05',
            'status' => 'active',
        ]);

        Auth::login($this->userA);
        Session::put('active_school_id', $this->schoolA->id);
        $request = Request::create('/finance/students/search', 'GET', ['q' => $searchName . ' Student']);
        $response = $this->controller->searchStudents($request);
        $results = $response->getData(true);

        $this->assertCount(1, $results);
        $this->assertSame($studentA->id, $results[0]['id']);
        $this->assertSame($searchName . ' Student', $results[0]['name']);
        $this->assertSame('Grade 1', $results[0]['class']);
    }
}
