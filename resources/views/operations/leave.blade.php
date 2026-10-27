@extends('layouts.app')
@section('title', 'Leave Management & Absence')
@section('breadcrumb', 'Operations › Human Resources › Leave Management')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🏖️ Leave Management & Staff Absence</h1>
        <p>Statutory leave entitlement, working-day calculations, approval workflows, and balance tracking</p>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-primary" onclick="openApplyLeaveModal()">
            <i class="bi bi-calendar-plus"></i> Submit Leave Request
        </button>
        <a href="{{ route('operations.staff') }}" class="btn btn-outline">
            <i class="bi bi-people"></i> Staff Directory
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}</div>
@endif

@if(session('info'))
    <div class="alert alert-info"><i class="bi bi-info-circle-fill"></i> {{ session('info') }}</div>
@endif

{{-- Summary Stats --}}
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#0284c7,#38bdf8);color:#fff"><i class="bi bi-person-walking"></i></div>
        <div class="stat-info">
            <span class="stat-label">On Leave Today</span>
            <span class="stat-value">{{ $currentlyOnLeave->count() }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff"><i class="bi bi-clock-history"></i></div>
        <div class="stat-info">
            <span class="stat-label">Pending Approvals</span>
            <span class="stat-value">{{ $pendingRequests->count() }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#34d399);color:#fff"><i class="bi bi-check2-circle"></i></div>
        <div class="stat-info">
            <span class="stat-label">Active Staff</span>
            <span class="stat-value">{{ count($staffBalances) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff"><i class="bi bi-calendar3"></i></div>
        <div class="stat-info">
            <span class="stat-label">Configured Leave Types</span>
            <span class="stat-value">{{ $leaveTypes->count() }}</span>
        </div>
    </div>
</div>

{{-- Pending Leave Approvals Section (If Any) --}}
@if($pendingRequests->isNotEmpty())
<div class="card" style="margin-bottom:24px;border:1px solid #fef08a;background:#fffefb">
    <div class="card-header" style="background:#fef9c3;display:flex;justify-content:space-between;align-items:center">
        <h5 style="color:#854d0e;margin:0"><i class="bi bi-exclamation-circle-fill"></i> Pending Leave Requests Awaiting Review ({{ $pendingRequests->count() }})</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Working Days</th>
                    <th>Reason</th>
                    <th>Attachment</th>
                    <th style="text-align:right">Review Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingRequests as $pr)
                <tr>
                    <td>
                        <strong>{{ $pr->employee?->full_name }}</strong>
                        <div style="font-size:0.75rem;color:#64748b">{{ $pr->employee?->designation }} ({{ $pr->employee?->employee_no }})</div>
                    </td>
                    <td><span class="badge badge-info">{{ $pr->leaveType?->name }}</span></td>
                    <td>{{ \Carbon\Carbon::parse($pr->start_date)->format('d M Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($pr->end_date)->format('d M Y') }}</td>
                    <td><strong>{{ $pr->days_requested }} Days</strong></td>
                    <td style="max-width:220px;font-size:0.85rem">{{ $pr->reason }}</td>
                    <td>
                        @if($pr->attachment_path)
                            <a href="{{ asset('storage/' . $pr->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline">
                                <i class="bi bi-paperclip"></i> View File
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="text-align:right">
                        <div style="display:inline-flex;gap:6px">
                            <form method="POST" action="{{ route('operations.leave.approve', $pr->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve leave request for {{ addslashes($pr->employee?->full_name) }}?')">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline" style="color:var(--danger)" onclick="openRejectModal({{ $pr->id }}, '{{ addslashes($pr->employee?->full_name) }}')">
                                <i class="bi bi-x-lg"></i> Reject
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Real Database Leave Balances Table --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-table"></i> Verified Annual Leave Balances (Year {{ $year }})</h5>
        <div style="display:flex;align-items:center;gap:10px">
            <span style="font-size:0.8rem;color:#64748b">Verified from recorded approved requests</span>
            <button class="btn btn-sm btn-primary" onclick="openApplyLeaveModal()">+ Apply Leave</button>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Emp No.</th>
                    <th>Employee Name</th>
                    <th>Designation & Dept</th>
                    <th>Annual Entitlement</th>
                    <th>Approved Taken</th>
                    <th>Pending Requests</th>
                    <th>Available Remaining</th>
                    <th>Current Status</th>
                    <th style="text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffBalances as $b)
                @php $emp = $b['employee']; @endphp
                <tr>
                    <td><span class="badge badge-gray" style="font-family:monospace">{{ $emp->employee_no }}</span></td>
                    <td>
                        <a href="{{ route('operations.staff.show', $emp->id) }}" style="font-weight:600;color:inherit;text-decoration:none">
                            {{ $emp->full_name }}
                        </a>
                    </td>
                    <td>
                        <div>{{ $emp->designation }}</div>
                        <div style="font-size:0.75rem;color:#64748b">{{ $emp->department ?? 'General' }}</div>
                    </td>
                    <td>{{ $b['entitled'] }} Days</td>
                    <td>
                        @if($b['taken'] > 0)
                            <strong style="color:var(--warning)">{{ $b['taken'] }} Days</strong>
                        @else
                            <span class="text-muted">0 Days</span>
                        @endif
                    </td>
                    <td>
                        @if($b['pending'] > 0)
                            <span class="badge badge-warning">{{ $b['pending'] }} Days</span>
                        @else
                            <span class="text-muted">0 Days</span>
                        @endif
                    </td>
                    <td>
                        <strong style="color:var(--success);font-size:1rem">{{ $b['remaining'] }} Days</strong>
                    </td>
                    <td>
                        @if($b['is_on_leave'])
                            <span class="badge badge-info"><i class="bi bi-airplane"></i> On Approved Leave</span>
                        @elseif($emp->status === 'active')
                            <span class="badge badge-success"><i class="bi bi-check-circle"></i> Active On Duty</span>
                        @elseif($emp->status === 'suspended')
                            <span class="badge badge-warning">Suspended</span>
                        @else
                            <span class="badge badge-danger">Inactive</span>
                        @endif
                    </td>
                    <td style="text-align:right">
                        <button class="btn btn-sm btn-outline" onclick="openApplyForEmployee({{ $emp->id }})">
                            <i class="bi bi-plus-circle"></i> Request
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="no-data">No staff records found in this school.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Configured Statutory Leave Types in Kenya --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h5><i class="bi bi-sliders"></i> Institutional Leave Types (Kenya Employment Standards)</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Code</th>
                    <th>Default Days</th>
                    <th>Paid / Unpaid</th>
                    <th>Gender Policy</th>
                    <th>Carry-Over</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leaveTypes as $lt)
                <tr>
                    <td><strong>{{ $lt->name }}</strong></td>
                    <td><span class="badge badge-gray" style="font-family:monospace">{{ $lt->code ?? '—' }}</span></td>
                    <td><strong>{{ $lt->days_per_year }} Days</strong></td>
                    <td>
                        <span class="badge {{ $lt->is_paid ? 'badge-success' : 'badge-danger' }}">
                            {{ $lt->is_paid ? 'Paid' : 'Unpaid' }}
                        </span>
                    </td>
                    <td><span style="text-transform:capitalize">{{ $lt->gender_restriction }}</span></td>
                    <td>{{ $lt->carries_forward ? 'Yes (Max ' . $lt->max_carry_forward_days . 'd)' : 'No' }}</td>
                    <td style="font-size:0.85rem;color:#64748b;max-width:280px">{{ $lt->description }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Leave History / Audit Trail --}}
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-clock-history"></i> Leave Request History & Absence Audit Log</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Employee</th>
                    <th>Leave Type</th>
                    <th>Dates</th>
                    <th>Working Days</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Approver / Date</th>
                    <th style="text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaveHistory as $lh)
                <tr>
                    <td><span class="badge badge-gray" style="font-family:monospace">#{{ $lh->id }}</span></td>
                    <td>
                        <strong>{{ $lh->employee?->full_name }}</strong>
                        <div style="font-size:0.75rem;color:#64748b">{{ $lh->employee?->employee_no }}</div>
                    </td>
                    <td>{{ $lh->leaveType?->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($lh->start_date)->format('d M Y') }} — {{ \Carbon\Carbon::parse($lh->end_date)->format('d M Y') }}</td>
                    <td><strong>{{ $lh->days_requested }} Days</strong></td>
                    <td style="font-size:0.85rem;max-width:180px">{{ $lh->reason }}</td>
                    <td>
                        @if($lh->status === 'approved')
                            <span class="badge badge-success"><i class="bi bi-check-circle"></i> Approved</span>
                        @elseif($lh->status === 'pending')
                            <span class="badge badge-warning"><i class="bi bi-hourglass-split"></i> Pending</span>
                        @elseif($lh->status === 'rejected')
                            <span class="badge badge-danger"><i class="bi bi-x-circle"></i> Rejected</span>
                        @else
                            <span class="badge badge-gray">Cancelled</span>
                        @endif
                    </td>
                    <td style="font-size:0.8rem">
                        @if($lh->approver)
                            <div>{{ $lh->approver->name }}</div>
                            <div class="text-muted">{{ $lh->approved_at ? $lh->approved_at->format('d M Y') : '' }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="text-align:right">
                        @if(in_array($lh->status, ['pending', 'approved']))
                        <form method="POST" action="{{ route('operations.leave.cancel', $lh->id) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger)" onclick="return confirm('Cancel this leave request?')">
                                Cancel
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="no-data">No historical leave applications recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaveHistory->hasPages())
    <div style="padding:16px">{{ $leaveHistory->links() }}</div>
    @endif
</div>

{{-- Submit Leave Application Modal --}}
<div id="modal-apply-leave" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:580px">
        <div class="modal-header">
            <h4><i class="bi bi-calendar-plus"></i> Submit Staff Leave Request</h4>
            <button onclick="closeApplyLeaveModal()" class="modal-close">✕</button>
        </div>
        <form method="POST" action="{{ route('operations.leave.apply') }}" enctype="multipart/form-data">
            @csrf
            <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
                <div class="form-group">
                    <label>Employee *</label>
                    <select id="leave-emp-select" name="employee_id" class="form-control" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->employee_no }} - {{ $emp->designation }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Leave Type *</label>
                    <select name="leave_type_id" class="form-control" required>
                        @foreach($leaveTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->days_per_year }} days entitlement {{ $lt->is_paid ? '• Paid' : '• Unpaid' }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Start Date *</label>
                        <input type="date" name="start_date" class="form-control" required min="{{ date('Y-m-d', strtotime('-30 days')) }}">
                    </div>
                    <div class="form-group">
                        <label>End Date *</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="alert alert-info" style="font-size:0.8rem;padding:8px 12px;margin:0">
                    <i class="bi bi-info-circle"></i> <strong>Working Days Rule:</strong> Saturdays, Sundays, and Kenyan National Public Holidays (e.g. Mashujaa, Jamhuri, Madaraka, Easter) are automatically excluded from leave deductions.
                </div>
                <div class="form-group">
                    <label>Reason / Handover Notes *</label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="Detail the reason for leave and any faculty/duties handover arrangement..."></textarea>
                </div>
                <div class="form-group">
                    <label>Supporting Document (Optional / Required for Sick/Maternity)</label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    <small class="text-muted">Medical certificate or hospital clearance note if applicable</small>
                </div>
            </div>
            <div class="modal-footer" style="padding:16px 20px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" onclick="closeApplyLeaveModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Submit Leave Request</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Leave Modal --}}
<div id="modal-reject" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:460px">
        <div class="modal-header">
            <h4><i class="bi bi-x-circle" style="color:var(--danger)"></i> Reject Leave Request</h4>
            <button onclick="closeRejectModal()" class="modal-close">✕</button>
        </div>
        <form id="reject-form" method="POST" action="">
            @csrf
            <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
                <p id="reject-emp-name" style="font-weight:600;margin:0;color:#1e293b"></p>
                <div class="form-group">
                    <label>Reason for Rejection *</label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Explain why the leave request cannot be approved at this time..."></textarea>
                </div>
            </div>
            <div class="modal-footer" style="padding:16px 20px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" onclick="closeRejectModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:var(--danger);border-color:var(--danger)">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function openApplyLeaveModal() { document.getElementById('modal-apply-leave').style.display = 'flex'; }
function closeApplyLeaveModal() { document.getElementById('modal-apply-leave').style.display = 'none'; }
function openApplyForEmployee(empId) {
    document.getElementById('leave-emp-select').value = empId;
    openApplyLeaveModal();
}
function openRejectModal(id, empName) {
    const form = document.getElementById('reject-form');
    form.action = '/operations/leave/' + id + '/reject';
    document.getElementById('reject-emp-name').textContent = 'Rejecting request for: ' + empName;
    document.getElementById('modal-reject').style.display = 'flex';
}
function closeRejectModal() { document.getElementById('modal-reject').style.display = 'none'; }
</script>
@endsection
