@extends('layouts.app')
@section('title', 'Employee Profile: ' . $employee->full_name)
@section('breadcrumb', 'Operations › Staff Directory › ' . $employee->full_name)

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <a href="{{ route('operations.staff') }}" style="font-size:0.85rem;color:#4f46e5;text-decoration:none;display:inline-block;margin-bottom:6px">
            ← Back to Staff Directory
        </a>
        <h1>{{ $employee->full_name }} <span class="badge badge-gray" style="font-family:monospace;font-size:1rem">{{ $employee->employee_no }}</span></h1>
        <p>{{ $employee->designation }} • {{ $employee->department ?? 'General' }}</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <button class="btn btn-outline" onclick="openEditEmployeeModal()">
            <i class="bi bi-pencil-square"></i> Edit Profile
        </button>
        <button class="btn btn-primary" onclick="openUploadDocModal()">
            <i class="bi bi-upload"></i> Upload Document
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
@endif

<div style="display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start">
    
    {{-- Left Sidebar: Key Profile Card --}}
    <div style="display:flex;flex-direction:column;gap:20px">
        <div class="card" style="text-align:center;padding:24px">
            @if($employee->photo)
                <img src="{{ asset('storage/' . $employee->photo) }}" alt="" style="width:110px;height:110px;border-radius:50%;object-fit:cover;margin:0 auto 16px;border:3px solid #e0e7ff">
            @else
                <div style="width:110px;height:110px;border-radius:50%;background:#e0e7ff;color:#4f46e5;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:2rem;margin:0 auto 16px">
                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                </div>
            @endif

            <h4 style="margin:0 0 4px">{{ $employee->full_name }}</h4>
            <div style="font-size:0.9rem;color:#64748b;margin-bottom:12px">{{ $employee->designation }}</div>

            <div style="margin-bottom:16px">
                @if($employee->status === 'active')
                    <span class="badge badge-success"><i class="bi bi-check-circle"></i> Active Employee</span>
                @elseif($employee->status === 'on_leave')
                    <span class="badge badge-info"><i class="bi bi-calendar-event"></i> Currently On Leave</span>
                @elseif($employee->status === 'suspended')
                    <span class="badge badge-warning"><i class="bi bi-pause-circle"></i> Suspended</span>
                @else
                    <span class="badge badge-danger"><i class="bi bi-x-circle"></i> Terminated</span>
                @endif
            </div>

            <div style="text-align:left;border-top:1px solid #e2e8f0;padding-top:16px;font-size:0.85rem;display:flex;flex-direction:column;gap:8px">
                <div><strong>Emp #:</strong> <span style="font-family:monospace">{{ $employee->employee_no }}</span></div>
                <div><strong>Department:</strong> {{ $employee->department ?? '—' }}</div>
                <div><strong>Employment:</strong> <span style="text-transform:capitalize">{{ $employee->employment_type ?? 'Permanent' }}</span></div>
                <div><strong>Hire Date:</strong> {{ $employee->hire_date ? \Carbon\Carbon::parse($employee->hire_date)->format('d M Y') : '—' }}</div>
                @if($employee->contract_end_date)
                    <div><strong>Contract Expiry:</strong> {{ \Carbon\Carbon::parse($employee->contract_end_date)->format('d M Y') }}</div>
                @endif
                <div><strong>System Account:</strong> {{ $employee->user ? $employee->user->email : 'Not Linked' }}</div>
            </div>
        </div>

        {{-- Emergency Contact Card --}}
        <div class="card" style="padding:20px">
            <h6 style="color:#4f46e5;font-weight:700;margin-bottom:12px"><i class="bi bi-shield-exclamation"></i> Emergency Contact</h6>
            <div style="font-size:0.85rem;display:flex;flex-direction:column;gap:6px">
                <div><strong>Name:</strong> {{ $employee->emergency_contact_name ?? '—' }}</div>
                <div><strong>Phone:</strong> {{ $employee->emergency_contact_phone ?? '—' }}</div>
                <div><strong>Relationship:</strong> {{ $employee->emergency_contact_relationship ?? '—' }}</div>
            </div>
        </div>

        {{-- Uploaded Documents --}}
        <div class="card" style="padding:20px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                <h6 style="color:#4f46e5;font-weight:700;margin:0"><i class="bi bi-file-earmark-text"></i> Documents ({{ $employee->documents->count() }})</h6>
                <button class="btn btn-sm btn-outline" onclick="openUploadDocModal()">+ Add</button>
            </div>
            <div style="display:flex;flex-direction:column;gap:8px">
                @forelse($employee->documents as $doc)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px;background:#f8fafc;border-radius:6px;font-size:0.85rem">
                        <div>
                            <div style="font-weight:600">{{ $doc->title }}</div>
                            <small class="text-muted" style="text-transform:uppercase">{{ $doc->document_type }}</small>
                        </div>
                        <a href="{{ asset('storage/' . $doc->file_path) }}" target="_blank" class="btn btn-sm btn-outline">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                @empty
                    <div class="text-muted" style="font-size:0.85rem">No documents attached yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Right Main Content --}}
    <div style="display:flex;flex-direction:column;gap:20px">
        
        {{-- Compensation & Banking Details Card --}}
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-cash-coin"></i> Compensation & Statutory Payroll Details</h5>
            </div>
            <div style="padding:20px;display:grid;grid-template-columns:repeat(3,1fr);gap:16px;font-size:0.9rem">
                <div>
                    <label class="text-muted" style="font-size:0.75rem;text-transform:uppercase;font-weight:600">Basic Monthly Salary</label>
                    <div style="font-size:1.25rem;font-weight:700;color:var(--success);font-family:'Outfit'">
                        KES {{ number_format($employee->basic_salary, 2) }}
                    </div>
                </div>
                <div>
                    <label class="text-muted" style="font-size:0.75rem;text-transform:uppercase;font-weight:600">KRA PIN Number</label>
                    <div style="font-weight:600;font-family:monospace">{{ $employee->kra_pin ?? '—' }}</div>
                </div>
                <div>
                    <label class="text-muted" style="font-size:0.75rem;text-transform:uppercase;font-weight:600">NSSF Number</label>
                    <div style="font-weight:600;font-family:monospace">{{ $employee->nssf_no ?? '—' }}</div>
                </div>
                <div>
                    <label class="text-muted" style="font-size:0.75rem;text-transform:uppercase;font-weight:600">SHIF / NHIF Number</label>
                    <div style="font-weight:600;font-family:monospace">{{ $employee->nhif_no ?? '—' }}</div>
                </div>
                <div>
                    <label class="text-muted" style="font-size:0.75rem;text-transform:uppercase;font-weight:600">Bank & Branch</label>
                    <div style="font-weight:600">{{ $employee->bank_name ? $employee->bank_name . ' (' . ($employee->bank_branch ?? 'Main') . ')' : '—' }}</div>
                </div>
                <div>
                    <label class="text-muted" style="font-size:0.75rem;text-transform:uppercase;font-weight:600">Account Number & Name</label>
                    <div style="font-weight:600;font-family:monospace">{{ $employee->bank_account_number ?? '—' }}</div>
                    <div style="font-size:0.75rem;color:#64748b">{{ $employee->bank_account_name }}</div>
                </div>
            </div>
        </div>

        {{-- Leave Balances & History --}}
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <h5><i class="bi bi-calendar2-check"></i> Leave Balances & History ({{ date('Y') }})</h5>
                <a href="{{ route('operations.leave') }}" class="btn btn-sm btn-outline">Apply Leave</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Entitled</th>
                            <th>Carried Forward</th>
                            <th>Approved Taken</th>
                            <th>Remaining</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveTypes as $lt)
                        @php
                            $bal = \App\Services\LeaveCalculationService::getBalance($employee, $lt, (int)date('Y'));
                        @endphp
                        <tr>
                            <td><strong>{{ $lt->name }}</strong></td>
                            <td>{{ $bal['entitled_days'] }} Days</td>
                            <td>{{ $bal['carried_forward'] }} Days</td>
                            <td>{{ $bal['approved_taken'] }} Days</td>
                            <td><strong style="color:var(--success)">{{ $bal['remaining_balance'] }} Days</strong></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center">No leave types configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($employee->leaveRequests->count())
            <div style="padding:16px;border-top:1px solid #e2e8f0">
                <h6 style="font-weight:700;margin-bottom:8px">Recent Leave Applications</h6>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Dates</th>
                                <th>Working Days</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employee->leaveRequests->take(5) as $lr)
                            <tr>
                                <td>{{ $lr->leaveType?->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($lr->start_date)->format('d M') }} — {{ \Carbon\Carbon::parse($lr->end_date)->format('d M Y') }}</td>
                                <td>{{ $lr->days_requested }} Days</td>
                                <td>{{ $lr->reason }}</td>
                                <td>
                                    <span class="badge {{ $lr->status === 'approved' ? 'badge-success' : ($lr->status === 'pending' ? 'badge-warning' : 'badge-danger') }}">
                                        {{ ucfirst($lr->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- Payroll History --}}
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-wallet2"></i> Payroll History</h5>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Gross Pay</th>
                            <th>Deductions</th>
                            <th>Net Pay</th>
                            <th>Payment Status</th>
                            <th>Amount Paid</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employee->payrollRecords as $pr)
                        <tr>
                            <td><strong>{{ $pr->payroll_month ?? $pr->payrollPeriod?->name }}</strong></td>
                            <td>KES {{ number_format($pr->gross_salary, 2) }}</td>
                            <td style="color:var(--danger)">KES {{ number_format($pr->deductions, 2) }}</td>
                            <td style="font-weight:700;color:var(--success)">KES {{ number_format($pr->net_salary, 2) }}</td>
                            <td>
                                @if($pr->payment_status === 'paid')
                                    <span class="badge badge-success">Paid</span>
                                @elseif($pr->payment_status === 'partially_paid')
                                    <span class="badge badge-warning">Partial</span>
                                @else
                                    <span class="badge badge-gray">Unpaid</span>
                                @endif
                            </td>
                            <td>KES {{ number_format($pr->amount_paid, 2) }}</td>
                            <td>
                                <a href="{{ route('operations.payroll.records.payslip', $pr->id) }}" target="_blank" class="btn btn-sm btn-outline">
                                    <i class="bi bi-receipt"></i> Payslip
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-muted text-center" style="padding:20px">No payroll records generated yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Audit Trail for Employee --}}
        @if($auditLogs->count())
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-clock-history"></i> Audit Log & History</h5>
            </div>
            <div style="padding:16px;display:flex;flex-direction:column;gap:10px">
                @foreach($auditLogs as $log)
                <div style="font-size:0.85rem;border-bottom:1px solid #f1f5f9;padding-bottom:8px">
                    <div style="display:flex;justify-content:space-between">
                        <strong>{{ $log->description }}</strong>
                        <span class="text-muted">{{ $log->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div style="font-size:0.75rem;color:#64748b">
                        By: {{ $log->user?->name ?? 'System' }} | IP: {{ $log->ip_address }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>

{{-- Edit Employee Modal --}}
<div id="modal-edit-emp" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:820px;max-height:90vh;overflow-y:auto">
        <div class="modal-header">
            <h4><i class="bi bi-pencil-square"></i> Edit Employee: {{ $employee->full_name }}</h4>
            <button onclick="closeEditEmployeeModal()" class="modal-close">✕</button>
        </div>
        <form method="POST" action="{{ route('operations.staff.update', $employee->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div style="padding:20px;display:flex;flex-direction:column;gap:20px">
                
                {{-- Section 1: Biographical --}}
                <div>
                    <h6 style="color:#4f46e5;font-weight:700;margin-bottom:12px;border-bottom:1px solid #e2e8f0;padding-bottom:6px">
                        1. Identification & Biographical Details
                    </h6>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                        <div class="form-group">
                            <label>Employee Number *</label>
                            <input type="text" name="employee_no" value="{{ old('employee_no', $employee->employee_no) }}" class="form-control" required style="font-family:monospace;font-weight:600">
                        </div>
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" class="form-control">
                                <option value="Male" {{ old('gender', $employee->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender', $employee->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender', $employee->gender) === 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $employee->date_of_birth ? $employee->date_of_birth->format('Y-m-d') : '') }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>National ID / Passport</label>
                            <input type="text" name="national_id" value="{{ old('national_id', $employee->national_id) }}" class="form-control">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label>Replace Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
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
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Physical Address</label>
                            <input type="text" name="physical_address" value="{{ old('physical_address', $employee->physical_address) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Phone</label>
                            <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Relationship</label>
                            <input type="text" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $employee->emergency_contact_relationship) }}" class="form-control">
                        </div>
                    </div>
                </div>

                {{-- Section 3: Job Terms --}}
                <div>
                    <h6 style="color:#4f46e5;font-weight:700;margin-bottom:12px;border-bottom:1px solid #e2e8f0;padding-bottom:6px">
                        3. Job Terms & Compensation
                    </h6>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
                        <div class="form-group">
                            <label>Job Title / Designation *</label>
                            <input type="text" name="designation" value="{{ old('designation', $employee->designation) }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" value="{{ old('department', $employee->department) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Employment Type *</label>
                            <select name="employment_type" class="form-control" required>
                                <option value="permanent" {{ old('employment_type', $employee->employment_type) === 'permanent' ? 'selected' : '' }}>Permanent & Pensionable</option>
                                <option value="contract" {{ old('employment_type', $employee->employment_type) === 'contract' ? 'selected' : '' }}>Fixed-Term Contract</option>
                                <option value="temporary" {{ old('employment_type', $employee->employment_type) === 'temporary' ? 'selected' : '' }}>Temporary</option>
                                <option value="casual" {{ old('employment_type', $employee->employment_type) === 'casual' ? 'selected' : '' }}>Casual</option>
                                <option value="intern" {{ old('employment_type', $employee->employment_type) === 'intern' ? 'selected' : '' }}>Intern</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Hire Date *</label>
                            <input type="date" name="hire_date" value="{{ old('hire_date', $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '') }}" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Contract Start Date</label>
                            <input type="date" name="contract_start_date" value="{{ old('contract_start_date', $employee->contract_start_date ? $employee->contract_start_date->format('Y-m-d') : '') }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Contract End Date</label>
                            <input type="date" name="contract_end_date" value="{{ old('contract_end_date', $employee->contract_end_date ? $employee->contract_end_date->format('Y-m-d') : '') }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Basic Monthly Salary (KES) *</label>
                            <input type="number" name="basic_salary" value="{{ old('basic_salary', $employee->basic_salary) }}" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>KRA PIN Number</label>
                            <input type="text" name="kra_pin" value="{{ old('kra_pin', $employee->kra_pin) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>NSSF Number</label>
                            <input type="text" name="nssf_no" value="{{ old('nssf_no', $employee->nssf_no) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>SHIF / NHIF Number</label>
                            <input type="text" name="nhif_no" value="{{ old('nhif_no', $employee->nhif_no) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Bank Name</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Account Number</label>
                            <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}" class="form-control">
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer" style="padding:16px 20px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" onclick="closeEditEmployeeModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Update Employee Details</button>
            </div>
        </form>
    </div>
</div>

{{-- Upload Document Modal --}}
<div id="modal-upload-doc" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <h4><i class="bi bi-file-earmark-arrow-up"></i> Upload Staff Document</h4>
            <button onclick="closeUploadDocModal()" class="modal-close">✕</button>
        </div>
        <form method="POST" action="{{ route('operations.staff.document', $employee->id) }}" enctype="multipart/form-data">
            @csrf
            <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
                <div class="form-group">
                    <label>Document Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Employment Contract 2026 / ID Copy" required>
                </div>
                <div class="form-group">
                    <label>Document Type *</label>
                    <select name="document_type" class="form-control" required>
                        <option value="contract">Employment Contract</option>
                        <option value="id_copy">National ID / Passport Copy</option>
                        <option value="kra_pin">KRA PIN Certificate</option>
                        <option value="academic_cert">Academic / Professional Certificate</option>
                        <option value="cv">Curriculum Vitae (CV)</option>
                        <option value="other">Other Document</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select File *</label>
                    <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                    <small class="text-muted">PDF, JPG, PNG or DOCX (max 5MB)</small>
                </div>
            </div>
            <div class="modal-footer" style="padding:16px 20px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" onclick="closeUploadDocModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditEmployeeModal() { document.getElementById('modal-edit-emp').style.display = 'flex'; }
function closeEditEmployeeModal() { document.getElementById('modal-edit-emp').style.display = 'none'; }
function openUploadDocModal() { document.getElementById('modal-upload-doc').style.display = 'flex'; }
function closeUploadDocModal() { document.getElementById('modal-upload-doc').style.display = 'none'; }
</script>
@endsection
