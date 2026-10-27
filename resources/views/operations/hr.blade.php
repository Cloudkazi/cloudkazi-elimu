@extends('layouts.app')
@section('title', 'HR & Staff Directory')
@section('breadcrumb', 'Operations › Human Resources › Staff Directory')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>👥 Staff Directory & Human Resources</h1>
        <p>Institutional employee records, designations, compensation, and staff lifecycle management</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn btn-primary" onclick="openAddEmployeeModal()">
            <i class="bi bi-person-plus-fill"></i> Add Employee
        </button>
        <a href="{{ route('operations.staff.export') }}" class="btn btn-outline">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>
        <a href="{{ route('operations.leave') }}" class="btn btn-outline">
            <i class="bi bi-calendar-check"></i> Leave ({{ $pendingLeaveRequests }})
        </a>
        <a href="{{ route('operations.payroll') }}" class="btn btn-outline">
            <i class="bi bi-wallet2"></i> Payroll
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- HR Dashboard Metrics (Database Backed) --}}
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff"><i class="bi bi-people-fill"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Employees</span>
            <span class="stat-value">{{ $totalEmployees }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff"><i class="bi bi-person-check-fill"></i></div>
        <div class="stat-info">
            <span class="stat-label">Active Staff</span>
            <span class="stat-value">{{ $activeEmployees }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#0284c7,#38bdf8);color:#fff"><i class="bi bi-calendar-event"></i></div>
        <div class="stat-info">
            <span class="stat-label">On Approved Leave Today</span>
            <span class="stat-value">{{ $employeesOnLeave }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff"><i class="bi bi-cash-stack"></i></div>
        <div class="stat-info">
            <span class="stat-label">Active Basic Salary</span>
            <span class="stat-value">KES {{ number_format($totalMonthlySalary) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);color:#fff"><i class="bi bi-clock-history"></i></div>
        <div class="stat-info">
            <span class="stat-label">Pending Leave Requests</span>
            <span class="stat-value">{{ $pendingLeaveRequests }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#ea580c,#f97316);color:#fff"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-info">
            <span class="stat-label">Contracts Expiring (60d)</span>
            <span class="stat-value" style="{{ $contractsNearingExpiry > 0 ? 'color:var(--danger)' : '' }}">{{ $contractsNearingExpiry }}</span>
        </div>
    </div>
</div>

{{-- Search & Filter Bar --}}
<div class="card" style="margin-bottom:20px;padding:16px">
    <form method="GET" action="{{ route('operations.staff') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
        <div style="flex:2;min-width:200px">
            <label style="font-size:0.75rem;font-weight:600;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by name, employee #, phone, ID...">
        </div>
        <div style="flex:1;min-width:140px">
            <label style="font-size:0.75rem;font-weight:600;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px">Department</label>
            <select name="department" class="form-control">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept }}" {{ request('department') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1;min-width:140px">
            <label style="font-size:0.75rem;font-weight:600;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px">Employment Type</label>
            <select name="employment_type" class="form-control">
                <option value="">All Types</option>
                <option value="permanent" {{ request('employment_type') === 'permanent' ? 'selected' : '' }}>Permanent</option>
                <option value="contract" {{ request('employment_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                <option value="temporary" {{ request('employment_type') === 'temporary' ? 'selected' : '' }}>Temporary</option>
                <option value="casual" {{ request('employment_type') === 'casual' ? 'selected' : '' }}>Casual</option>
                <option value="intern" {{ request('employment_type') === 'intern' ? 'selected' : '' }}>Intern</option>
            </select>
        </div>
        <div style="flex:1;min-width:130px">
            <label style="font-size:0.75rem;font-weight:600;text-transform:uppercase;color:#64748b;display:block;margin-bottom:4px">Status</label>
            <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="on_leave" {{ request('status') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="terminated" {{ request('status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
            </select>
        </div>
        <div style="display:flex;gap:6px">
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
            @if(request()->hasAny(['search', 'department', 'employment_type', 'status']))
                <a href="{{ route('operations.staff') }}" class="btn btn-outline">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Staff Table --}}
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-person-lines-fill"></i> Staff Directory ({{ $employees->total() }})</h5>
        <span style="font-size:0.85rem;color:#64748b">Showing {{ $employees->firstItem() ?? 0 }} - {{ $employees->lastItem() ?? 0 }} of {{ $employees->total() }}</span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width:50px"></th>
                    <th>Emp No.</th>
                    <th>Full Name</th>
                    <th>Designation & Dept</th>
                    <th>Employment Type</th>
                    <th>Hire Date</th>
                    <th>Basic Salary</th>
                    <th>Status</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                <tr>
                    <td>
                        @if($emp->photo)
                            <img src="{{ asset('storage/' . $emp->photo) }}" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:1px solid #e2e8f0">
                        @else
                            <div style="width:36px;height:36px;border-radius:50%;background:#e0e7ff;color:#4f46e5;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.8rem">
                                {{ strtoupper(substr($emp->first_name, 0, 1) . substr($emp->last_name, 0, 1)) }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-gray" style="font-family:monospace;font-weight:600">{{ $emp->employee_no }}</span>
                    </td>
                    <td>
                        <a href="{{ route('operations.staff.show', $emp->id) }}" style="font-weight:600;color:inherit;text-decoration:none">
                            {{ $emp->full_name }}
                        </a>
                        @if($emp->phone)
                            <div style="font-size:0.75rem;color:#64748b"><i class="bi bi-telephone"></i> {{ $emp->phone }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight:600">{{ $emp->designation }}</div>
                        <div style="font-size:0.75rem;color:#64748b">{{ $emp->department ?? 'General' }}</div>
                    </td>
                    <td>
                        <span style="text-transform:capitalize;font-size:0.85rem">{{ $emp->employment_type ?? 'Permanent' }}</span>
                        @if($emp->contract_end_date)
                            <div style="font-size:0.72rem;color:{{ \Carbon\Carbon::parse($emp->contract_end_date)->isPast() ? 'var(--danger)' : '#64748b' }}">
                                Ends: {{ \Carbon\Carbon::parse($emp->contract_end_date)->format('d M Y') }}
                            </div>
                        @endif
                    </td>
                    <td>{{ $emp->hire_date ? \Carbon\Carbon::parse($emp->hire_date)->format('d M Y') : '—' }}</td>
                    <td style="font-weight:600;font-family:'Outfit'">KES {{ number_format($emp->basic_salary, 2) }}</td>
                    <td>
                        @if($emp->status === 'active')
                            <span class="badge badge-success"><i class="bi bi-check-circle"></i> Active</span>
                        @elseif($emp->status === 'on_leave')
                            <span class="badge badge-info"><i class="bi bi-calendar-event"></i> On Leave</span>
                        @elseif($emp->status === 'suspended')
                            <span class="badge badge-warning"><i class="bi bi-pause-circle"></i> Suspended</span>
                        @else
                            <span class="badge badge-danger"><i class="bi bi-x-circle"></i> Terminated</span>
                        @endif
                    </td>
                    <td style="text-align:right">
                        <div style="display:inline-flex;gap:4px">
                            <a href="{{ route('operations.staff.show', $emp->id) }}" class="btn btn-sm btn-outline" title="View Full Profile">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <button class="btn btn-sm btn-outline" onclick="openStatusModal({{ $emp->id }}, '{{ addslashes($emp->full_name) }}', '{{ $emp->status }}')" title="Change Status">
                                <i class="bi bi-sliders"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted" style="padding:32px">
                        <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                        No employees found matching the filter criteria.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
    <div style="padding:16px;border-top:1px solid #e2e8f0">{{ $employees->links() }}</div>
    @endif
</div>

{{-- Add Employee Modal --}}
<div id="modal-emp" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:820px;max-height:90vh;overflow-y:auto">
        <div class="modal-header">
            <h4><i class="bi bi-person-plus-fill"></i> Register New Employee</h4>
            <button onclick="closeAddEmployeeModal()" class="modal-close">✕</button>
        </div>
        <form method="POST" action="{{ route('operations.staff.store') }}" enctype="multipart/form-data">
            @csrf
            <div style="padding:20px;display:flex;flex-direction:column;gap:20px">
                
                {{-- Section 1: Biographical & Identification --}}
                <div>
                    <h6 style="color:#4f46e5;font-weight:700;margin-bottom:12px;border-bottom:1px solid #e2e8f0;padding-bottom:6px">
                        1. Identification & Biographical Details
                    </h6>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                        <div class="form-group">
                            <label>Employee Number *</label>
                            <input type="text" name="employee_no" value="{{ old('employee_no', $nextEmployeeNo) }}" class="form-control" required style="font-family:monospace;font-weight:600">
                        </div>
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Gender *</label>
                            <select name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender') === 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>National ID / Passport #</label>
                            <input type="text" name="national_id" value="{{ old('national_id') }}" class="form-control" placeholder="e.g. 12345678">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label>Employee Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                            <small class="text-muted">JPG or PNG passport photo (max 2MB)</small>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Contact & Emergency --}}
                <div>
                    <h6 style="color:#4f46e5;font-weight:700;margin-bottom:12px;border-bottom:1px solid #e2e8f0;padding-bottom:6px">
                        2. Contact & Emergency Information
                    </h6>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                        <div class="form-group">
                            <label>Primary Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" placeholder="0712345678">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="staff@kituischool.sc.ke">
                        </div>
                        <div class="form-group">
                            <label>Physical Address / Residence</label>
                            <input type="text" name="physical_address" value="{{ old('physical_address') }}" class="form-control" placeholder="Estate, Town, Street">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Phone</label>
                            <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Relationship</label>
                            <input type="text" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship') }}" class="form-control" placeholder="Spouse, Sibling, Parent...">
                        </div>
                    </div>
                </div>

                {{-- Section 3: Job & Designation --}}
                <div>
                    <h6 style="color:#4f46e5;font-weight:700;margin-bottom:12px;border-bottom:1px solid #e2e8f0;padding-bottom:6px">
                        3. Job, Department & Employment Terms
                    </h6>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                        <div class="form-group">
                            <label>Job Title / Designation *</label>
                            <input type="text" name="designation" value="{{ old('designation') }}" class="form-control" placeholder="e.g. Senior Teacher / Accountant / Driver" required>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" value="{{ old('department') }}" class="form-control" placeholder="e.g. Sciences / Finance / Kitchen / Security">
                        </div>
                        <div class="form-group">
                            <label>Employment Type *</label>
                            <select name="employment_type" class="form-control" required>
                                <option value="permanent" {{ old('employment_type') === 'permanent' ? 'selected' : '' }}>Permanent & Pensionable</option>
                                <option value="contract" {{ old('employment_type') === 'contract' ? 'selected' : '' }}>Fixed-Term Contract</option>
                                <option value="temporary" {{ old('employment_type') === 'temporary' ? 'selected' : '' }}>Temporary</option>
                                <option value="casual" {{ old('employment_type') === 'casual' ? 'selected' : '' }}>Casual / Daily</option>
                                <option value="intern" {{ old('employment_type') === 'intern' ? 'selected' : '' }}>Intern</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Hire Date *</label>
                            <input type="date" name="hire_date" value="{{ old('hire_date', date('Y-m-d')) }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Contract Start Date</label>
                            <input type="date" name="contract_start_date" value="{{ old('contract_start_date') }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Contract End Date</label>
                            <input type="date" name="contract_end_date" value="{{ old('contract_end_date') }}" class="form-control">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label>Link to Existing System User Account (Optional)</label>
                            <select name="user_id" class="form-control">
                                <option value="">-- No User Account (Or Create Separately) --</option>
                                @foreach($unlinkedUsers as $u)
                                    <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>
                                        {{ $u->name }} ({{ $u->email }}) [Role: {{ $u->role }}]
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">If employee already logs into the system, link them here to prevent duplicate user accounts.</small>
                        </div>
                    </div>
                </div>

                {{-- Section 4: Compensation & Statutory Info --}}
                <div>
                    <h6 style="color:#4f46e5;font-weight:700;margin-bottom:12px;border-bottom:1px solid #e2e8f0;padding-bottom:6px">
                        4. Salary, Banking & Statutory Identifiers (Kenya)
                    </h6>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                        <div class="form-group">
                            <label>Basic Monthly Salary (KES) *</label>
                            <input type="number" name="basic_salary" value="{{ old('basic_salary') }}" class="form-control" min="0" step="0.01" required placeholder="e.g. 35000">
                        </div>
                        <div class="form-group">
                            <label>KRA PIN Number</label>
                            <input type="text" name="kra_pin" value="{{ old('kra_pin') }}" class="form-control" placeholder="A012345678B">
                        </div>
                        <div class="form-group">
                            <label>NSSF Number</label>
                            <input type="text" name="nssf_no" value="{{ old('nssf_no') }}" class="form-control" placeholder="e.g. 123456789">
                        </div>
                        <div class="form-group">
                            <label>SHIF / NHIF Number</label>
                            <input type="text" name="nhif_no" value="{{ old('nhif_no') }}" class="form-control" placeholder="e.g. 987654321">
                        </div>
                        <div class="form-group">
                            <label>Bank Name</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}" class="form-control" placeholder="e.g. KCB / Equity / Co-op">
                        </div>
                        <div class="form-group">
                            <label>Bank Branch</label>
                            <input type="text" name="bank_branch" value="{{ old('bank_branch') }}" class="form-control" placeholder="Kitui Branch">
                        </div>
                        <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" class="form-control">
                        </div>
                        <div class="form-group" style="grid-column:span 2">
                            <label>Account Name</label>
                            <input type="text" name="bank_account_name" value="{{ old('bank_account_name') }}" class="form-control" placeholder="As written in bank statement">
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer" style="padding:16px 20px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" onclick="closeAddEmployeeModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save & Register Employee</button>
            </div>
        </form>
    </div>
</div>

{{-- Status Change Modal --}}
<div id="modal-status" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <h4><i class="bi bi-sliders"></i> Update Employment Status</h4>
            <button onclick="closeStatusModal()" class="modal-close">✕</button>
        </div>
        <form id="status-form" method="POST" action="">
            @csrf
            <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
                <p id="status-emp-name" style="font-weight:600;margin:0;color:#1e293b"></p>
                <div class="form-group">
                    <label>New Status *</label>
                    <select id="status-select" name="status" class="form-control" required>
                        <option value="active">Active</option>
                        <option value="on_leave">On Leave</option>
                        <option value="suspended">Suspended</option>
                        <option value="terminated">Terminated</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reason / Audit Note *</label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="State the reason or institutional memo reference for this status change..."></textarea>
                </div>
            </div>
            <div class="modal-footer" style="padding:16px 20px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" onclick="closeStatusModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddEmployeeModal() {
    document.getElementById('modal-emp').style.display = 'flex';
}
function closeAddEmployeeModal() {
    document.getElementById('modal-emp').style.display = 'none';
}
function openStatusModal(empId, empName, currentStatus) {
    const form = document.getElementById('status-form');
    form.action = '/operations/staff/' + empId + '/status';
    document.getElementById('status-emp-name').textContent = 'Updating status for: ' + empName;
    document.getElementById('status-select').value = currentStatus;
    document.getElementById('modal-status').style.display = 'flex';
}
function closeStatusModal() {
    document.getElementById('modal-status').style.display = 'none';
}
</script>
@endsection
