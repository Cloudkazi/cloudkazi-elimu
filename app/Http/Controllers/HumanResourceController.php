<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\HrAuditLog;
use App\Models\LeaveAllocation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollPayment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\School;
use App\Models\User;
use App\Services\HrDefaultSetupService;
use App\Services\KenyanPayrollCalculator;
use App\Services\LeaveCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HumanResourceController extends Controller
{
    /**
     * Authorize HR and administrative access.
     */
    protected function authorizeHrAccess(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // Allow Super Admin, School Admin, Principal, Deputy Principal, or users with HR permission
        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin() && !in_array($user->role, ['principal', 'deputy-principal', 'accountant', 'hr'])) {
            abort(403, 'Unauthorized. Access is restricted to authorized Human Resources and administrative personnel.');
        }
    }

    /**
     * Get active school ID with fallback.
     */
    protected function getSchoolId(): int
    {
        $schoolId = Auth::user()?->school_id ?? session('active_school_id');
        if (!$schoolId) {
            abort(400, 'No active school context found.');
        }
        return (int)$schoolId;
    }

    // =========================================================================
    // 1. STAFF DIRECTORY — EMPLOYEE MANAGEMENT
    // =========================================================================

    public function staffIndex(Request $request)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $query = Employee::where('school_id', $schoolId)->with(['user', 'payrollRecords']);

        // Search
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_no', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter: Department
        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        // Filter: Employment Type
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->input('employment_type'));
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $employees = $query->orderBy('first_name')->paginate(15)->withQueryString();

        // Real Database-backed HR Dashboard Statistics
        $today = Carbon::today();
        $totalEmployees = Employee::where('school_id', $schoolId)->count();
        $activeEmployees = Employee::where('school_id', $schoolId)->where('status', 'active')->count();

        // Real count of employees currently on approved leave today
        $employeesOnLeave = Employee::where('school_id', $schoolId)
            ->whereHas('leaveRequests', function ($q) use ($today) {
                $q->where('status', 'approved')
                  ->whereDate('start_date', '<=', $today)
                  ->whereDate('end_date', '>=', $today);
            })->count();

        $totalMonthlySalary = Employee::where('school_id', $schoolId)
            ->where('status', 'active')
            ->sum('basic_salary');

        $pendingLeaveRequests = LeaveRequest::where('school_id', $schoolId)
            ->where('status', 'pending')
            ->count();

        $payrollAwaitingProcessing = PayrollPeriod::where('school_id', $schoolId)
            ->whereIn('status', ['draft', 'calculated', 'pending_approval'])
            ->count();

        $payrollAwaitingPayment = PayrollPeriod::where('school_id', $schoolId)
            ->whereIn('status', ['approved', 'finalized'])
            ->whereRaw('total_net_salary > total_paid')
            ->count();

        $contractsNearingExpiry = Employee::where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [$today, $today->copy()->addDays(60)])
            ->count();

        // Dropdown data
        $departments = Employee::where('school_id', $schoolId)
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');

        $unlinkedUsers = User::where('school_id', $schoolId)
            ->whereDoesntHave('employee')
            ->get();

        // Next auto-generated employee number
        $nextEmployeeNo = $this->generateNextEmployeeNo($schoolId);

        // Recent payroll records for quick reference
        $recentPayrolls = PayrollRecord::where('school_id', $schoolId)
            ->with(['employee', 'payrollPeriod'])
            ->latest()
            ->take(8)
            ->get();

        return view('operations.hr', compact(
            'employees',
            'totalEmployees',
            'activeEmployees',
            'employeesOnLeave',
            'totalMonthlySalary',
            'pendingLeaveRequests',
            'payrollAwaitingProcessing',
            'payrollAwaitingPayment',
            'contractsNearingExpiry',
            'departments',
            'unlinkedUsers',
            'nextEmployeeNo',
            'recentPayrolls'
        ));
    }

    /**
     * Store new employee.
     */
    public function storeEmployee(Request $request)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $validated = $request->validate([
            'employee_no' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('employees')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date|before:today',
            'national_id' => 'nullable|string|max:50',
            'kra_pin' => 'nullable|string|max:50',
            'nssf_no' => 'nullable|string|max:50',
            'nhif_no' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'physical_address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'designation' => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
            'employment_type' => 'required|in:permanent,contract,temporary,casual,intern',
            'hire_date' => 'required|date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
            'basic_salary' => 'required|numeric|min:0',
            'bank_name' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:100',
            'user_id' => 'nullable|exists:users,id',
            'photo' => 'nullable|image|max:2048',
        ]);

        $validated['school_id'] = $schoolId;
        $validated['status'] = 'active';

        // Photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('employees/photos', 'public');
            $validated['photo'] = $path;
        }

        $employee = DB::transaction(function () use ($validated, $schoolId) {
            $emp = Employee::create($validated);

            // Audit log
            HrAuditLog::log('employee.created', $emp, null, $emp->toArray(), "Created employee {$emp->full_name} ({$emp->employee_no})");

            // Ensure leave types exist and allocate default annual leave
            HrDefaultSetupService::ensureDefaultLeaveTypes($schoolId);
            $annualType = LeaveType::where('school_id', $schoolId)->where('name', 'Annual Leave')->first();
            if ($annualType) {
                LeaveAllocation::firstOrCreate(
                    [
                        'school_id' => $schoolId,
                        'employee_id' => $emp->id,
                        'leave_type_id' => $annualType->id,
                        'year' => (int)date('Y'),
                    ],
                    [
                        'entitled_days' => $annualType->days_per_year,
                        'carried_forward' => 0,
                        'adjusted_days' => 0,
                    ]
                );
            }

            return $emp;
        });

        return redirect()->route('operations.staff')->with('success', "Employee {$employee->full_name} ({$employee->employee_no}) registered successfully.");
    }

    /**
     * Show employee profile.
     */
    public function showEmployee(int $id)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $employee = Employee::where('school_id', $schoolId)
            ->with(['user', 'documents', 'leaveRequests.leaveType', 'payrollRecords.payrollPeriod', 'payrollPayments'])
            ->findOrFail($id);

        $leaveAllocations = LeaveAllocation::where('employee_id', $employee->id)
            ->where('year', (int)date('Y'))
            ->with('leaveType')
            ->get();

        $leaveTypes = LeaveType::where('school_id', $schoolId)->where('status', 'active')->get();

        // Audit history
        $auditLogs = HrAuditLog::where('school_id', $schoolId)
            ->where('auditable_type', Employee::class)
            ->where('auditable_id', $employee->id)
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('operations.staff-show', compact('employee', 'leaveAllocations', 'leaveTypes', 'auditLogs'));
    }

    /**
     * Update employee.
     */
    public function updateEmployee(Request $request, int $id)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $employee = Employee::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'employee_no' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('employees')->where(fn ($q) => $q->where('school_id', $schoolId))->ignore($employee->id),
            ],
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date|before:today',
            'national_id' => 'nullable|string|max:50',
            'kra_pin' => 'nullable|string|max:50',
            'nssf_no' => 'nullable|string|max:50',
            'nhif_no' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'physical_address' => 'nullable|string|max:500',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:50',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'designation' => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
            'employment_type' => 'required|in:permanent,contract,temporary,casual,intern',
            'hire_date' => 'required|date',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
            'basic_salary' => 'required|numeric|min:0',
            'bank_name' => 'nullable|string|max:100',
            'bank_branch' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:100',
            'user_id' => 'nullable|exists:users,id',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($employee->photo && Storage::disk('public')->exists($employee->photo)) {
                Storage::disk('public')->delete($employee->photo);
            }
            $validated['photo'] = $request->file('photo')->store('employees/photos', 'public');
        }

        $oldValues = $employee->only(array_keys($validated));
        $employee->update($validated);

        HrAuditLog::log('employee.updated', $employee, $oldValues, $employee->only(array_keys($validated)), "Updated details for {$employee->full_name}");

        return back()->with('success', "Employee {$employee->full_name} details updated.");
    }

    /**
     * Change employee status (Active, On Leave, Suspended, Terminated)
     */
    public function updateStatus(Request $request, int $id)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $employee = Employee::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:active,on_leave,suspended,terminated',
            'reason' => 'required|string|max:500',
        ]);

        $oldStatus = $employee->status;
        $employee->update(['status' => $validated['status']]);

        HrAuditLog::log(
            'employee.status_change',
            $employee,
            ['status' => $oldStatus],
            ['status' => $validated['status']],
            "Changed status from {$oldStatus} to {$validated['status']}. Reason: {$validated['reason']}"
        );

        return back()->with('success', "Employee status updated to " . ucfirst(str_replace('_', ' ', $validated['status'])) . ".");
    }

    /**
     * Export staff directory to CSV.
     */
    public function exportStaff(Request $request): StreamedResponse
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $employees = Employee::where('school_id', $schoolId)->orderBy('first_name')->get();

        $fileName = 'Staff_Directory_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($employees) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Employee No',
                'Full Name',
                'Designation',
                'Department',
                'Employment Type',
                'Status',
                'Hire Date',
                'Basic Salary (KES)',
                'National ID',
                'KRA PIN',
                'NSSF No',
                'NHIF No',
                'Phone',
                'Email',
                'Bank Name',
                'Bank Account',
            ]);

            foreach ($employees as $emp) {
                fputcsv($handle, [
                    $emp->employee_no,
                    $emp->full_name,
                    $emp->designation,
                    $emp->department ?? '',
                    ucfirst($emp->employment_type ?? 'permanent'),
                    ucfirst(str_replace('_', ' ', $emp->status)),
                    $emp->hire_date ? Carbon::parse($emp->hire_date)->format('Y-m-d') : '',
                    number_format((float)$emp->basic_salary, 2, '.', ''),
                    $emp->national_id ?? '',
                    $emp->kra_pin ?? '',
                    $emp->nssf_no ?? '',
                    $emp->nhif_no ?? '',
                    $emp->phone ?? '',
                    $emp->email ?? '',
                    $emp->bank_name ?? '',
                    $emp->bank_account_number ?? '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Upload employee document.
     */
    public function uploadDocument(Request $request, int $id)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $employee = Employee::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'document_type' => 'required|in:contract,cv,id_copy,academic_cert,kra_pin,nhif_card,nssf_card,other',
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ]);

        $filePath = $request->file('document')->store('employees/documents', 'public');

        EmployeeDocument::create([
            'school_id' => $schoolId,
            'employee_id' => $employee->id,
            'title' => $validated['title'],
            'document_type' => $validated['document_type'],
            'file_path' => $filePath,
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Helper to auto-generate next unique employee number.
     */
    protected function generateNextEmployeeNo(int $schoolId): string
    {
        $highest = Employee::where('school_id', $schoolId)
            ->where('employee_no', 'like', 'EMP-%')
            ->pluck('employee_no')
            ->map(function ($no) {
                if (preg_match('/EMP-(\d+)/', $no, $m)) {
                    return (int)$m[1];
                }
                return 0;
            })->max();

        $nextNum = ($highest ?? 0) + 1;
        return sprintf('EMP-%03d', $nextNum);
    }

    // =========================================================================
    // 2. LEAVE MANAGEMENT — CORRECT BALANCES & REAL LEAVE REQUESTS
    // =========================================================================

    public function leaveIndex(Request $request)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        // Ensure default leave types exist for this school
        HrDefaultSetupService::ensureDefaultLeaveTypes($schoolId);

        $year = (int)$request->input('year', date('Y'));
        $leaveTypes = LeaveType::where('school_id', $schoolId)->where('status', 'active')->get();
        $employees = Employee::where('school_id', $schoolId)->where('status', '!=', 'terminated')->orderBy('first_name')->get();

        // Calculate accurate, database-verified balances for each employee
        $staffBalances = [];
        $annualType = $leaveTypes->firstWhere('code', 'ANNUAL') ?? $leaveTypes->first();

        foreach ($employees as $emp) {
            $balance = LeaveCalculationService::getBalance($emp, $annualType, $year);
            $isCurrentlyOnLeave = $emp->isCurrentlyOnLeave();

            $staffBalances[] = [
                'employee' => $emp,
                'entitled' => $balance['entitled_days'],
                'taken' => $balance['approved_taken'],
                'pending' => $balance['pending_requested'],
                'remaining' => $balance['remaining_balance'],
                'is_on_leave' => $isCurrentlyOnLeave,
            ];
        }

        // Pending Leave Requests awaiting review
        $pendingRequests = LeaveRequest::where('school_id', $schoolId)
            ->where('status', 'pending')
            ->with(['employee', 'leaveType'])
            ->orderBy('created_at')
            ->get();

        // Leave Requests History
        $leaveHistory = LeaveRequest::where('school_id', $schoolId)
            ->with(['employee', 'leaveType', 'approver'])
            ->latest()
            ->paginate(15);

        // Active Leaves right now
        $today = Carbon::today();
        $currentlyOnLeave = LeaveRequest::where('school_id', $schoolId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with(['employee', 'leaveType'])
            ->get();

        return view('operations.leave', compact(
            'staffBalances',
            'leaveTypes',
            'employees',
            'pendingRequests',
            'leaveHistory',
            'currentlyOnLeave',
            'year',
            'annualType'
        ));
    }

    /**
     * Submit leave application.
     */
    public function applyLeave(Request $request)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png|max:5120',
        ]);

        $employee = Employee::where('school_id', $schoolId)->findOrFail($validated['employee_id']);
        $leaveType = LeaveType::where('school_id', $schoolId)->findOrFail($validated['leave_type_id']);

        // Check gender restriction
        if ($leaveType->gender_restriction !== 'all' && $employee->gender) {
            if (strtolower($leaveType->gender_restriction) !== strtolower($employee->gender)) {
                return back()->with('error', "{$leaveType->name} is restricted to {$leaveType->gender_restriction} employees only.");
            }
        }

        // Calculate working days (excluding weekends and public holidays)
        $workingDays = LeaveCalculationService::calculateWorkingDays($validated['start_date'], $validated['end_date']);
        if ($workingDays <= 0) {
            return back()->with('error', 'The selected date range does not contain any institutional working days (all dates fall on weekends or public holidays).');
        }

        // Check overlap
        if (LeaveCalculationService::hasOverlap($employee->id, $validated['start_date'], $validated['end_date'])) {
            return back()->with('error', "An approved or pending leave request already exists for {$employee->full_name} during the selected date range.");
        }

        // Check remaining balance
        $year = Carbon::parse($validated['start_date'])->year;
        $balance = LeaveCalculationService::getBalance($employee, $leaveType, $year);

        if ($leaveType->is_paid && $workingDays > $balance['remaining_balance']) {
            return back()->with('error', "Requested working days ({$workingDays}) exceed {$employee->full_name}'s available {$leaveType->name} balance ({$balance['remaining_balance']} days).");
        }

        // File attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave/attachments', 'public');
        } elseif ($leaveType->requires_attachment) {
            return back()->with('error', "{$leaveType->name} requires supporting documentation (medical certificate or clearance).");
        }

        $leaveRequest = LeaveRequest::create([
            'school_id' => $schoolId,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_requested' => $workingDays,
            'reason' => $validated['reason'],
            'attachment_path' => $attachmentPath,
            'status' => 'pending',
        ]);

        HrAuditLog::log(
            'leave.applied',
            $leaveRequest,
            null,
            $leaveRequest->toArray(),
            "Submitted leave request for {$employee->full_name}: {$workingDays} days of {$leaveType->name}"
        );

        return back()->with('success', "Leave request for {$employee->full_name} ({$workingDays} working days) submitted for approval.");
    }

    /**
     * Approve leave request.
     */
    public function approveLeave(Request $request, int $id)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $leaveRequest = LeaveRequest::where('school_id', $schoolId)->with(['employee', 'leaveType'])->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be approved.');
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        HrAuditLog::log(
            'leave.approved',
            $leaveRequest,
            ['status' => 'pending'],
            ['status' => 'approved'],
            "Approved {$leaveRequest->days_requested} days {$leaveRequest->leaveType->name} for {$leaveRequest->employee->full_name}"
        );

        return back()->with('success', "Leave request #{$leaveRequest->id} approved successfully.");
    }

    /**
     * Reject leave request.
     */
    public function rejectLeave(Request $request, int $id)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $leaveRequest = LeaveRequest::where('school_id', $schoolId)->with(['employee', 'leaveType'])->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Only pending leave requests can be rejected.');
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        HrAuditLog::log(
            'leave.rejected',
            $leaveRequest,
            ['status' => 'pending'],
            ['status' => 'rejected', 'reason' => $validated['rejection_reason']],
            "Rejected leave request for {$leaveRequest->employee->full_name}. Reason: {$validated['rejection_reason']}"
        );

        return back()->with('info', "Leave request #{$leaveRequest->id} was rejected.");
    }

    /**
     * Cancel leave request.
     */
    public function cancelLeave(Request $request, int $id)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $leaveRequest = LeaveRequest::where('school_id', $schoolId)->with(['employee', 'leaveType'])->findOrFail($id);

        if (!in_array($leaveRequest->status, ['pending', 'approved'])) {
            return back()->with('error', 'This leave request cannot be cancelled.');
        }

        $oldStatus = $leaveRequest->status;
        $leaveRequest->update([
            'status' => 'cancelled',
            'comments' => $request->input('reason', 'Cancelled by authorized HR administrator'),
        ]);

        HrAuditLog::log(
            'leave.cancelled',
            $leaveRequest,
            ['status' => $oldStatus],
            ['status' => 'cancelled'],
            "Cancelled leave request #{$leaveRequest->id} for {$leaveRequest->employee->full_name}"
        );

        return back()->with('success', "Leave request #{$leaveRequest->id} has been cancelled.");
    }

    // =========================================================================
    // 3. PAYROLL — REAL PROCESSING & PAYMENT TRACKING
    // =========================================================================

    public function payrollIndex(Request $request)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $periods = PayrollPeriod::where('school_id', $schoolId)->latest('start_date')->get();

        // Selected period: defaults to latest or requested
        $selectedPeriodId = $request->input('period_id');
        $selectedPeriod = null;

        if ($selectedPeriodId) {
            $selectedPeriod = PayrollPeriod::where('school_id', $schoolId)->find($selectedPeriodId);
        }

        if (!$selectedPeriod && $periods->isNotEmpty()) {
            $selectedPeriod = $periods->first();
        }

        $records = collect();
        if ($selectedPeriod) {
            $records = PayrollRecord::where('school_id', $schoolId)
                ->where('payroll_period_id', $selectedPeriod->id)
                ->with(['employee', 'payments', 'paidBy'])
                ->get();
        }

        // Active employees available for inclusion
        $activeEmployees = Employee::where('school_id', $schoolId)->where('status', 'active')->get();

        // All payment history for this school
        $recentPayments = PayrollPayment::where('school_id', $schoolId)
            ->with(['employee', 'payrollPeriod', 'recordedBy'])
            ->latest()
            ->take(10)
            ->get();

        return view('operations.payroll', compact(
            'periods',
            'selectedPeriod',
            'records',
            'activeEmployees',
            'recentPayments'
        ));
    }

    /**
     * Create a new payroll period.
     */
    public function createPeriod(Request $request)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $validated = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2020,2035',
            'notes' => 'nullable|string|max:500',
        ]);

        $monthDate = Carbon::createFromDate($validated['year'], $validated['month'], 1);
        $periodName = $monthDate->format('F Y');
        $startDate = $monthDate->copy()->startOfMonth()->toDateString();
        $endDate = $monthDate->copy()->endOfMonth()->toDateString();

        // Prevent duplicate period for same month/year
        $existing = PayrollPeriod::where('school_id', $schoolId)
            ->where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->first();

        if ($existing) {
            return back()->with('error', "Payroll period for {$periodName} already exists.");
        }

        $period = PayrollPeriod::create([
            'school_id' => $schoolId,
            'name' => $periodName,
            'month' => $validated['month'],
            'year' => $validated['year'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'draft',
            'prepared_by' => Auth::id(),
            'notes' => $validated['notes'],
        ]);

        HrAuditLog::log('payroll.period_created', $period, null, $period->toArray(), "Created payroll period: {$period->name}");

        return redirect()->route('operations.payroll', ['period_id' => $period->id])
            ->with('success', "Payroll period {$period->name} opened. You can now calculate payroll.");
    }

    /**
     * Calculate / Prepare payroll for all active employees in a period.
     */
    public function calculatePeriod(Request $request, int $periodId)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $period = PayrollPeriod::where('school_id', $schoolId)->findOrFail($periodId);

        if ($period->isFinalized()) {
            return back()->with('error', 'Cannot recalculate a finalized or paid payroll period.');
        }

        $activeEmployees = Employee::where('school_id', $schoolId)->where('status', 'active')->get();

        if ($activeEmployees->isEmpty()) {
            return back()->with('error', 'No active employees found in the school.');
        }

        DB::transaction(function () use ($period, $activeEmployees, $schoolId) {
            $totalBasic = 0.0;
            $totalAllowances = 0.0;
            $totalGross = 0.0;
            $totalStatutory = 0.0;
            $totalOtherDeductions = 0.0;
            $totalNet = 0.0;
            $totalEmployerContrib = 0.0;

            foreach ($activeEmployees as $employee) {
                // Official Kenyan Statutory Calculation
                $breakdown = KenyanPayrollCalculator::calculate((float)$employee->basic_salary);

                // Preserve existing amount_paid and payment_status if record already exists
                $existing = PayrollRecord::where('school_id', $schoolId)
                    ->where('payroll_period_id', $period->id)
                    ->where('employee_id', $employee->id)
                    ->first();

                $amountPaid = $existing ? (float)$existing->amount_paid : 0.0;
                $paymentStatus = $existing ? $existing->payment_status : 'unpaid';

                // Evaluate payment status against newly calculated net
                if ($amountPaid >= $breakdown['net_salary'] && $breakdown['net_salary'] > 0) {
                    $paymentStatus = 'paid';
                } elseif ($amountPaid > 0) {
                    $paymentStatus = 'partially_paid';
                } else {
                    $paymentStatus = 'unpaid';
                }

                PayrollRecord::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'payroll_period_id' => $period->id,
                        'employee_id' => $employee->id,
                    ],
                    [
                        'month' => (int)$period->month,
                        'year' => (int)$period->year,
                        'payroll_month' => $period->name,
                        'basic_salary' => $breakdown['basic_salary'],
                        'allowances' => $breakdown['allowances'],
                        'gross_salary' => $breakdown['gross_salary'],
                        'paye' => $breakdown['paye'],
                        'nssf_employee' => $breakdown['nssf_employee'],
                        'nssf_employer' => $breakdown['nssf_employer'],
                        'shif' => $breakdown['shif'],
                        'housing_levy_employee' => $breakdown['housing_levy_employee'],
                        'housing_levy_employer' => $breakdown['housing_levy_employer'],
                        'other_deductions' => $breakdown['other_deductions'],
                        'deductions' => $breakdown['total_deductions'],
                        'taxable_income' => $breakdown['taxable_income'],
                        'personal_relief' => $breakdown['personal_relief'],
                        'insurance_relief' => $breakdown['insurance_relief'],
                        'total_employer_contributions' => $breakdown['total_employer_contributions'],
                        'net_salary' => $breakdown['net_salary'],
                        'amount_paid' => $amountPaid,
                        'payment_status' => $paymentStatus,
                        'status' => 'calculated',
                    ]
                );

                $totalBasic += $breakdown['basic_salary'];
                $totalAllowances += $breakdown['allowances'];
                $totalGross += $breakdown['gross_salary'];
                $totalStatutory += $breakdown['statutory_deductions'];
                $totalOtherDeductions += $breakdown['other_deductions'];
                $totalNet += $breakdown['net_salary'];
                $totalEmployerContrib += $breakdown['total_employer_contributions'];
            }

            // Update period summary
            $period->update([
                'status' => 'calculated',
                'total_basic_salary' => round($totalBasic, 2),
                'total_allowances' => round($totalAllowances, 2),
                'total_gross' => round($totalGross, 2),
                'total_statutory_deductions' => round($totalStatutory, 2),
                'total_other_deductions' => round($totalOtherDeductions, 2),
                'total_net_salary' => round($totalNet, 2),
                'total_employer_contributions' => round($totalEmployerContrib, 2),
                'employee_count' => $activeEmployees->count(),
            ]);

            HrAuditLog::log(
                'payroll.period_calculated',
                $period,
                null,
                $period->toArray(),
                "Calculated payroll for {$activeEmployees->count()} employees. Net payable: KES " . number_format($totalNet, 2)
            );
        });

        return back()->with('success', "Payroll for {$period->name} calculated successfully for {$activeEmployees->count()} active staff members.");
    }

    /**
     * Submit payroll period for approval.
     */
    public function submitPeriodApproval(int $periodId)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $period = PayrollPeriod::where('school_id', $schoolId)->findOrFail($periodId);

        if (!in_array($period->status, ['calculated', 'draft'])) {
            return back()->with('error', 'Only calculated payroll periods can be submitted for approval.');
        }

        $period->update(['status' => 'pending_approval']);
        PayrollRecord::where('payroll_period_id', $period->id)->update(['status' => 'pending_approval']);

        HrAuditLog::log('payroll.period_submitted', $period, null, null, "Submitted {$period->name} payroll for review and approval.");

        return back()->with('success', "Payroll for {$period->name} submitted for management approval.");
    }

    /**
     * Approve payroll period.
     */
    public function approvePeriod(int $periodId)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $period = PayrollPeriod::where('school_id', $schoolId)->findOrFail($periodId);

        if (!in_array($period->status, ['calculated', 'pending_approval'])) {
            return back()->with('error', 'Only calculated or pending payroll periods can be approved.');
        }

        $period->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
        PayrollRecord::where('payroll_period_id', $period->id)->update(['status' => 'approved']);

        HrAuditLog::log('payroll.period_approved', $period, null, null, "Approved {$period->name} payroll.");

        return back()->with('success', "Payroll for {$period->name} approved. You can now finalize and disburse payments.");
    }

    /**
     * Finalize payroll period.
     */
    public function finalizePeriod(int $periodId)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $period = PayrollPeriod::where('school_id', $schoolId)->findOrFail($periodId);

        if ($period->status !== 'approved') {
            return back()->with('error', 'Payroll must be approved before it can be finalized.');
        }

        $period->update([
            'status' => 'finalized',
            'finalized_by' => Auth::id(),
            'finalized_at' => now(),
        ]);
        PayrollRecord::where('payroll_period_id', $period->id)->update(['status' => 'finalized']);

        HrAuditLog::log('payroll.period_finalized', $period, null, null, "Finalized {$period->name} payroll.");

        return back()->with('success', "Payroll for {$period->name} finalized. Records are locked for payment disbursement.");
    }

    /**
     * Record payment for an employee payroll entry.
     */
    public function recordPayment(Request $request, int $recordId)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $record = PayrollRecord::where('school_id', $schoolId)->with(['employee', 'payrollPeriod'])->findOrFail($recordId);

        $period = $record->payrollPeriod;
        if (!$period || !$period->isFinalized()) {
            return back()->with('error', 'Payments can only be recorded against finalized payroll periods.');
        }

        $balanceDue = $record->balance_due;
        if ($balanceDue <= 0) {
            return back()->with('info', "This payroll record is already fully paid.");
        }

        $validated = $request->validate([
            'amount' => "required|numeric|min:1|max:{$balanceDue}",
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:Bank Transfer,M-Pesa,Cheque,Cash',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = (float)$validated['amount'];

        DB::transaction(function () use ($record, $period, $validated, $amount, $schoolId) {
            // Create payment transaction
            $payment = PayrollPayment::create([
                'school_id' => $schoolId,
                'payroll_record_id' => $record->id,
                'payroll_period_id' => $period->id,
                'employee_id' => $record->employee_id,
                'amount' => $amount,
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'],
                'recorded_by' => Auth::id(),
                'notes' => $validated['notes'],
            ]);

            // Update record amount_paid and status
            $newAmountPaid = round((float)$record->amount_paid + $amount, 2);
            $newStatus = ($newAmountPaid >= (float)$record->net_salary) ? 'paid' : 'partially_paid';

            $record->update([
                'amount_paid' => $newAmountPaid,
                'payment_status' => $newStatus,
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['reference_number'],
                'paid_by' => Auth::id(),
                'paid_at' => now(),
            ]);

            // Update period total_paid
            $newPeriodPaid = PayrollPayment::where('payroll_period_id', $period->id)->sum('amount');
            $periodUpdates = ['total_paid' => $newPeriodPaid];

            // If all records in period are fully paid, mark period as paid
            $unpaidRecords = PayrollRecord::where('payroll_period_id', $period->id)
                ->where('payment_status', '!=', 'paid')
                ->exists();

            if (!$unpaidRecords) {
                $periodUpdates['status'] = 'paid';
            }

            $period->update($periodUpdates);

            HrAuditLog::log(
                'payroll.payment_recorded',
                $payment,
                null,
                $payment->toArray(),
                "Disbursed KES " . number_format($amount, 2) . " to {$record->employee->full_name} via {$validated['payment_method']}"
            );
        });

        return back()->with('success', "Payment of KES " . number_format($amount, 2) . " recorded for {$record->employee->full_name}.");
    }

    /**
     * Show / Download print-friendly payslip.
     */
    public function showPayslip(int $recordId)
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $record = PayrollRecord::where('school_id', $schoolId)
            ->with(['employee', 'payrollPeriod', 'payments.recordedBy'])
            ->findOrFail($recordId);

        $school = School::find($schoolId);

        return view('operations.payslip', compact('record', 'school'));
    }

    /**
     * Export payroll register for the period.
     */
    public function exportPayrollRegister(int $periodId): StreamedResponse
    {
        $this->authorizeHrAccess();
        $schoolId = $this->getSchoolId();

        $period = PayrollPeriod::where('school_id', $schoolId)->findOrFail($periodId);
        $records = PayrollRecord::where('payroll_period_id', $period->id)
            ->with('employee')
            ->get();

        $fileName = 'Payroll_Register_' . str_replace(' ', '_', $period->name) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($records, $period) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Payroll Period: ' . $period->name,
                'Status: ' . ucfirst($period->status),
            ]);
            fputcsv($handle, [
                'Emp No',
                'Employee Name',
                'Designation',
                'Bank Name',
                'Account Number',
                'Basic Salary (KES)',
                'Gross Salary (KES)',
                'PAYE (KES)',
                'NSSF Employee (KES)',
                'SHIF (KES)',
                'Housing Levy Employee (KES)',
                'Total Employee Deductions (KES)',
                'Net Salary (KES)',
                'Amount Paid (KES)',
                'Balance Due (KES)',
                'Payment Status',
                'NSSF Employer (KES)',
                'Housing Levy Employer (KES)',
                'Total Employer Cost (KES)',
            ]);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->employee?->employee_no ?? '',
                    $r->employee?->full_name ?? '',
                    $r->employee?->designation ?? '',
                    $r->employee?->bank_name ?? '',
                    $r->employee?->bank_account_number ?? '',
                    number_format((float)$r->basic_salary, 2, '.', ''),
                    number_format((float)$r->gross_salary, 2, '.', ''),
                    number_format((float)$r->paye, 2, '.', ''),
                    number_format((float)$r->nssf_employee, 2, '.', ''),
                    number_format((float)$r->shif, 2, '.', ''),
                    number_format((float)$r->housing_levy_employee, 2, '.', ''),
                    number_format((float)$r->deductions, 2, '.', ''),
                    number_format((float)$r->net_salary, 2, '.', ''),
                    number_format((float)$r->amount_paid, 2, '.', ''),
                    number_format((float)$r->balance_due, 2, '.', ''),
                    ucfirst(str_replace('_', ' ', $r->payment_status)),
                    number_format((float)$r->nssf_employer, 2, '.', ''),
                    number_format((float)$r->housing_levy_employer, 2, '.', ''),
                    number_format((float)$r->total_employer_contributions, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
