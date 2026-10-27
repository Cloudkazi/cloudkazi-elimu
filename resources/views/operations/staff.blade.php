@extends('layouts.app')
@section('title', 'Human Resources & Staff Directory')
@section('breadcrumb', 'Operations › Staff')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>👥 Human Resources & Non-Teaching Staff</h1>
        <p>Support staff, administration, security, catering, and institutional employee directory</p>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-primary" onclick="document.getElementById('modal-emp').style.display='flex'">
            <i class="bi bi-person-plus"></i> Add Employee
        </button>
        <a href="{{ route('operations.payroll') }}" class="btn btn-outline">
            <i class="bi bi-wallet2"></i> Payroll
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-person-lines-fill"></i> Staff Directory ({{ $employees->total() }})</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Emp No.</th>
                    <th>Full Name</th>
                    <th>Designation</th>
                    <th>Department</th>
                    <th>Hire Date</th>
                    <th>Basic Salary (KES)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $e)
                <tr>
                    <td><span class="badge badge-gray" style="font-family:monospace">{{ $e->employee_no }}</span></td>
                    <td><strong>{{ $e->first_name }} {{ $e->last_name }}</strong></td>
                    <td><span class="badge badge-primary">{{ $e->designation }}</span></td>
                    <td>{{ $e->department ?? 'General Operations' }}</td>
                    <td>{{ \Carbon\Carbon::parse($e->hire_date)->format('d M Y') }}</td>
                    <td><strong>KES {{ number_format($e->basic_salary, 2) }}</strong></td>
                    <td><span class="badge badge-success">{{ ucfirst($e->status) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="7" class="no-data">No employee records found. Click "Add Employee".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($employees->hasPages())
        <div class="card-footer">{{ $employees->links() }}</div>
    @endif
</div>

<!-- Modal: Add Employee -->
<div id="modal-emp" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-person-plus"></i> Add Institutional Staff Member</h5>
            <button onclick="document.getElementById('modal-emp').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('hr.employees.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Employee Number *</label>
                        <input type="text" name="employee_no" class="form-control" placeholder="e.g. EMP-101" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Designation *</label>
                        <input type="text" name="designation" class="form-control" placeholder="e.g. Bursar, Caterer, Driver" required>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control" placeholder="e.g. Finance, Maintenance">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Basic Salary (KES) *</label>
                        <input type="number" name="basic_salary" class="form-control" placeholder="e.g. 35000" min="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Hire Date *</label>
                    <input type="date" name="hire_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-emp').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Employee</button>
            </div>
        </form>
    </div>
</div>
@endsection
