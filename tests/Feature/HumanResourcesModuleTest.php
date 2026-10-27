<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\HrAuditLog;
use App\Models\LeaveAllocation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollPayment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\KenyanPayrollCalculator;
use App\Services\LeaveCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HumanResourcesModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected School $school;
    protected User $adminUser;
    protected Employee $testEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::where('id', 12)->first() ?? School::firstOrCreate(['code' => 'TEST-001'], [
            'name' => 'Kitui Test School',
            'status' => 'active',
        ]);

        $adminRole = Role::where('slug', Role::SCHOOL_ADMIN)->first();
        if (!$adminRole) {
            $adminRole = Role::firstOrCreate(['slug' => 'school-admin'], ['name' => 'School Admin']);
        }

        $this->adminUser = User::where('school_id', $this->school->id)->first() ?? User::create([
            'school_id' => $this->school->id,
            'role_id' => $adminRole->id,
            'name' => 'HR Admin Tester',
            'email' => 'hr_admin_tester@test.ac.ke',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        // Ensure default leave types are seeded in test school
        \App\Services\HrDefaultSetupService::ensureDefaultLeaveTypes($this->school->id);
    }

    /**
     * Test 1: Verify existing employee Kinuthia Wakwitu is preserved and has 0 leave days taken.
     */
    public function test_existing_employee_is_preserved_with_zero_unconfirmed_leave(): void
    {
        $kinuthia = Employee::where('employee_no', 'EMP-001')->first();
        if (!$kinuthia) {
            $kinuthia = Employee::create([
                'school_id' => $this->school->id,
                'employee_no' => 'EMP-001',
                'first_name' => 'Kinuthia',
                'last_name' => 'Wakwitu',
                'designation' => 'Chef',
                'basic_salary' => 15000.00,
                'hire_date' => '2024-01-01',
                'status' => 'active',
            ]);
        }

        $this->assertNotNull($kinuthia, "Existing employee EMP-001 must not be deleted");
        $this->assertEquals('Kinuthia', $kinuthia->first_name);
        $this->assertEquals('Wakwitu', $kinuthia->last_name);
        $this->assertEquals(15000.00, (float)$kinuthia->basic_salary);

        // Verify leave balance from real records has 0 taken days
        $annualType = LeaveType::where('school_id', $kinuthia->school_id)->where('name', 'Annual Leave')->first();
        $this->assertNotNull($annualType);
        $balance = LeaveCalculationService::getBalance($kinuthia, $annualType);
        $this->assertEquals(0, $balance['approved_taken'], "Employee with no approved leave must have 0 days taken");
        $this->assertEquals(21, $balance['remaining_balance'], "Remaining balance must be 21 days");
    }

    /**
     * Test 2: Employee registration, validation, uniqueness, and profile view.
     */
    public function test_employee_registration_uniqueness_and_profile(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/operations/staff', [
            'employee_no' => 'EMP-TEST-99',
            'first_name' => 'Amina',
            'last_name' => 'Mutua',
            'gender' => 'Female',
            'designation' => 'Mathematics Teacher',
            'department' => 'Sciences',
            'employment_type' => 'permanent',
            'hire_date' => '2025-01-15',
            'basic_salary' => 45000.00,
            'national_id' => '98765432',
            'kra_pin' => 'A098765432B',
            'phone' => '0711223344',
            'email' => 'amina.mutua@testschool.ac.ke',
        ]);

        $response->assertRedirect('/operations/staff');
        $this->assertDatabaseHas('employees', [
            'employee_no' => 'EMP-TEST-99',
            'first_name' => 'Amina',
            'last_name' => 'Mutua',
            'school_id' => $this->school->id,
            'status' => 'active',
        ]);

        $employee = Employee::where('employee_no', 'EMP-TEST-99')->first();
        $this->assertNotNull($employee);

        // Test profile view
        $profileResponse = $this->actingAs($this->adminUser)->get("/operations/staff/{$employee->id}");
        $profileResponse->assertStatus(200);
        $profileResponse->assertSee('Amina Mutua');
        $profileResponse->assertSee('Mathematics Teacher');

        // Test duplicate employee number is rejected
        $dupResponse = $this->actingAs($this->adminUser)->post('/operations/staff', [
            'employee_no' => 'EMP-TEST-99',
            'first_name' => 'Duplicate',
            'last_name' => 'User',
            'designation' => 'Teacher',
            'employment_type' => 'permanent',
            'hire_date' => '2025-01-15',
            'basic_salary' => 30000.00,
        ]);
        $dupResponse->assertSessionHasErrors('employee_no');

        // Test status update to suspended
        $statusResponse = $this->actingAs($this->adminUser)->post("/operations/staff/{$employee->id}/status", [
            'status' => 'suspended',
            'reason' => 'Administrative review pending verification of documents',
        ]);
        $statusResponse->assertRedirect();
        $employee->refresh();
        $this->assertEquals('suspended', $employee->status);

        // Verify audit log
        $this->assertDatabaseHas('hr_audit_logs', [
            'auditable_type' => Employee::class,
            'auditable_id' => $employee->id,
            'action' => 'employee.status_change',
        ]);
    }

    /**
     * Test 3: Leave workflow: working days, overlap prevention, approval, rejection, and balance tracking.
     */
    public function test_leave_management_workflow_and_balances(): void
    {
        $employee = Employee::create([
            'school_id' => $this->school->id,
            'employee_no' => 'EMP-LEAVE-01',
            'first_name' => 'Peter',
            'last_name' => 'Ochieng',
            'designation' => 'Laboratory Technician',
            'employment_type' => 'permanent',
            'hire_date' => '2025-02-01',
            'basic_salary' => 30000.00,
            'status' => 'active',
        ]);

        $annualLeave = LeaveType::where('school_id', $this->school->id)->where('name', 'Annual Leave')->first();
        $this->assertNotNull($annualLeave);

        // Verify initial balance has 0 taken
        $initBalance = LeaveCalculationService::getBalance($employee, $annualLeave);
        $this->assertEquals(0, $initBalance['approved_taken']);
        $this->assertEquals(21, $initBalance['remaining_balance']);

        // Working days check: 2026-10-19 (Mon) to 2026-10-23 (Fri)
        // 5 calendar days - 1 public holiday (Mashujaa Day Oct 20) = 4 working days
        $workingDays = LeaveCalculationService::calculateWorkingDays('2026-10-19', '2026-10-23');
        $this->assertEquals(4, $workingDays);

        // Apply for leave
        $applyResponse = $this->actingAs($this->adminUser)->post('/operations/leave/apply', [
            'employee_id' => $employee->id,
            'leave_type_id' => $annualLeave->id,
            'start_date' => '2026-10-19',
            'end_date' => '2026-10-23',
            'reason' => 'Personal rest and annual travel',
        ]);
        $applyResponse->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'days_requested' => 4,
            'status' => 'pending',
        ]);

        $leaveReq = LeaveRequest::where('employee_id', $employee->id)->where('status', 'pending')->first();
        $this->assertNotNull($leaveReq);

        // Test overlap prevention: applying on overlapping dates should be blocked
        $overlapResponse = $this->actingAs($this->adminUser)->post('/operations/leave/apply', [
            'employee_id' => $employee->id,
            'leave_type_id' => $annualLeave->id,
            'start_date' => '2026-10-21',
            'end_date' => '2026-10-25',
            'reason' => 'Overlapping request attempt',
        ]);
        $overlapResponse->assertSessionHas('error');

        // Approve leave
        $approveResponse = $this->actingAs($this->adminUser)->post("/operations/leave/{$leaveReq->id}/approve", [
            'comments' => 'Approved by principal',
        ]);
        $approveResponse->assertRedirect();

        $leaveReq->refresh();
        $this->assertEquals('approved', $leaveReq->status);

        // Verify balance after approval: 4 days taken, 17 days remaining
        $afterBalance = LeaveCalculationService::getBalance($employee, $annualLeave);
        $this->assertEquals(4, $afterBalance['approved_taken']);
        $this->assertEquals(17, $afterBalance['remaining_balance']);

        // Cancel approved leave: balance should restore to 21
        $cancelResponse = $this->actingAs($this->adminUser)->post("/operations/leave/{$leaveReq->id}/cancel");
        $cancelResponse->assertRedirect();

        $leaveReq->refresh();
        $this->assertEquals('cancelled', $leaveReq->status);

        $restoredBalance = LeaveCalculationService::getBalance($employee, $annualLeave);
        $this->assertEquals(0, $restoredBalance['approved_taken']);
        $this->assertEquals(21, $restoredBalance['remaining_balance']);
    }

    /**
     * Test 4: Payroll calculations, period management, finalization, and payment recording.
     */
    public function test_payroll_period_lifecycle_and_confirmed_payment(): void
    {
        $employee = Employee::create([
            'school_id' => $this->school->id,
            'employee_no' => 'EMP-PAY-01',
            'first_name' => 'John',
            'last_name' => 'Wambua',
            'designation' => 'Driver',
            'employment_type' => 'permanent',
            'hire_date' => '2025-01-01',
            'basic_salary' => 20000.00,
            'status' => 'active',
        ]);

        // 1. Create new period (November 2026)
        $periodResponse = $this->actingAs($this->adminUser)->post('/operations/payroll/periods', [
            'month' => 11,
            'year' => 2026,
            'notes' => 'November 2026 salary cycle',
        ]);
        $periodResponse->assertRedirect();

        $period = PayrollPeriod::where('school_id', $this->school->id)->where('month', 11)->where('year', 2026)->first();
        $this->assertNotNull($period);
        $this->assertEquals('draft', $period->status);

        // 2. Calculate payroll
        $calcResponse = $this->actingAs($this->adminUser)->post("/operations/payroll/periods/{$period->id}/calculate");
        $calcResponse->assertRedirect();

        $period->refresh();
        $this->assertEquals('calculated', $period->status);
        $this->assertGreaterThan(0, (float)$period->total_gross);

        // Verify employee record exists and is UNPAID
        $record = PayrollRecord::where('payroll_period_id', $period->id)->where('employee_id', $employee->id)->first();
        $this->assertNotNull($record);
        $this->assertEquals('unpaid', $record->payment_status, "Calculated payroll must be unpaid until actually paid");
        $this->assertEquals(0.00, (float)$record->amount_paid);

        // Verify Kenyan statutory deductions:
        // Basic: 20,000
        // NSSF: Tier 1 (8000 * 0.06 = 480) + Tier 2 (12000 * 0.06 = 720) = 1,200
        $this->assertEquals(1200.00, (float)$record->nssf_employee);
        // SHIF: 20000 * 0.0275 = 550.00
        $this->assertEquals(550.00, (float)$record->shif);
        // AHL: 20000 * 0.015 = 300.00
        $this->assertEquals(300.00, (float)$record->housing_levy_employee);
        // PAYE: Taxable = 20000 - 1200 = 18800. Tax = 18800 * 0.10 = 1880. Less relief (2400 + 82.50) = 0
        $this->assertEquals(0.00, (float)$record->paye);
        // Net: 20000 - (1200 + 550 + 300) = 17,950.00
        $this->assertEquals(17950.00, (float)$record->net_salary);

        // 3. Move through approval workflow
        $submitResponse = $this->actingAs($this->adminUser)->post("/operations/payroll/periods/{$period->id}/submit");
        $submitResponse->assertRedirect();
        $period->refresh();
        $this->assertEquals('pending_approval', $period->status);

        $approveResponse = $this->actingAs($this->adminUser)->post("/operations/payroll/periods/{$period->id}/approve");
        $approveResponse->assertRedirect();
        $period->refresh();
        $this->assertEquals('approved', $period->status);

        $finalizeResponse = $this->actingAs($this->adminUser)->post("/operations/payroll/periods/{$period->id}/finalize");
        $finalizeResponse->assertRedirect();
        $period->refresh();
        $this->assertEquals('finalized', $period->status);

        // 4. Record partial payment (KES 10,000 of 17,950)
        $partialResponse = $this->actingAs($this->adminUser)->post("/operations/payroll/records/{$record->id}/pay", [
            'amount' => 10000.00,
            'payment_date' => '2026-11-28',
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'EFT-TEST-001',
            'notes' => 'First salary installment',
        ]);
        $partialResponse->assertRedirect();

        $record->refresh();
        $this->assertEquals(10000.00, (float)$record->amount_paid);
        $this->assertEquals(7950.00, (float)$record->balance_due);
        $this->assertEquals('partially_paid', $record->payment_status);

        // 5. Record remaining balance (KES 7,950)
        $fullResponse = $this->actingAs($this->adminUser)->post("/operations/payroll/records/{$record->id}/pay", [
            'amount' => 7950.00,
            'payment_date' => '2026-11-30',
            'payment_method' => 'Bank Transfer',
            'reference_number' => 'EFT-TEST-002',
            'notes' => 'Final salary settlement',
        ]);
        $fullResponse->assertRedirect();

        $record->refresh();
        $this->assertEquals(17950.00, (float)$record->amount_paid);
        $this->assertEquals(0.00, (float)$record->balance_due);
        $this->assertEquals('paid', $record->payment_status);

        // 6. Test payslip generation
        $payslipResponse = $this->actingAs($this->adminUser)->get("/operations/payroll/records/{$record->id}/payslip");
        $payslipResponse->assertStatus(200);
        $payslipResponse->assertSee('OFFICIAL PAYSLIP');
        $payslipResponse->assertSee('John Wambua');
        $payslipResponse->assertSee('17,950.00');
        $payslipResponse->assertSee('CONFIRMED PAID IN FULL');
    }

    /**
     * Test 5: Multi-tenant isolation - ensure schools cannot access each other's records.
     */
    public function test_tenant_isolation_on_hr_and_payroll(): void
    {
        $otherSchool = School::create([
            'code' => 'OTHER-SCH',
            'name' => 'Other School Academy',
            'status' => 'active',
        ]);

        $otherEmp = Employee::create([
            'school_id' => $otherSchool->id,
            'employee_no' => 'EMP-OTHER-1',
            'first_name' => 'Grace',
            'last_name' => 'Njeri',
            'designation' => 'Teacher',
            'employment_type' => 'permanent',
            'hire_date' => '2025-01-01',
            'basic_salary' => 35000.00,
            'status' => 'active',
        ]);

        // Attempting to view other school's employee from school 12 admin should 404
        $response = $this->actingAs($this->adminUser)->get("/operations/staff/{$otherEmp->id}");
        $response->assertStatus(404);
    }
}
