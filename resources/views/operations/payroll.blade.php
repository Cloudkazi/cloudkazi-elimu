@extends('layouts.app')
@section('title', 'Payroll Management & Salaries')
@section('breadcrumb', 'Operations › Human Resources › Payroll')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>💵 Institutional Payroll Management</h1>
        <p>Kenyan statutory deductions (PAYE, NSSF Tier I/II, SHIF 2.75%, AHL 1.5%), period workflows, and verified payment tracking</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn btn-primary" onclick="openCreatePeriodModal()">
            <i class="bi bi-calendar-plus"></i> Open New Payroll Period
        </button>
        @if($selectedPeriod)
            <a href="{{ route('operations.payroll.periods.export', $selectedPeriod->id) }}" class="btn btn-outline">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export Bank Register
            </a>
        @endif
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

{{-- Period Selector & Workflow Controls Bar --}}
<div class="card" style="margin-bottom:20px;padding:16px;background:#f8fafc;border:1px solid #e2e8f0">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px">
        <form method="GET" action="{{ route('operations.payroll') }}" style="display:flex;align-items:center;gap:10px">
            <label style="font-weight:700;font-size:0.9rem;color:#334155;white-space:nowrap">Select Payroll Period:</label>
            <select name="period_id" class="form-control" onchange="this.form.submit()" style="min-width:200px;font-weight:600">
                @forelse($periods as $p)
                    <option value="{{ $p->id }}" {{ $selectedPeriod && $selectedPeriod->id === $p->id ? 'selected' : '' }}>
                        {{ $p->name }} [{{ strtoupper($p->status) }}]
                    </option>
                @empty
                    <option value="">No payroll periods created yet</option>
                @endforelse
            </select>
        </form>

        @if($selectedPeriod)
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
            <span style="font-size:0.85rem;color:#64748b">Period Status:</span>
            @if($selectedPeriod->status === 'draft')
                <span class="badge badge-gray"><i class="bi bi-pencil"></i> Draft</span>
            @elseif($selectedPeriod->status === 'calculated')
                <span class="badge badge-info"><i class="bi bi-calculator"></i> Calculated</span>
            @elseif($selectedPeriod->status === 'pending_approval')
                <span class="badge badge-warning"><i class="bi bi-hourglass-split"></i> Awaiting Approval</span>
            @elseif($selectedPeriod->status === 'approved')
                <span class="badge badge-primary"><i class="bi bi-check2"></i> Approved</span>
            @elseif($selectedPeriod->status === 'finalized')
                <span class="badge badge-success" style="background:#059669"><i class="bi bi-lock-fill"></i> Finalized (Locked)</span>
            @elseif($selectedPeriod->status === 'paid')
                <span class="badge badge-success"><i class="bi bi-check-all"></i> Fully Paid</span>
            @endif

            {{-- Period Lifecycle Action Buttons --}}
            @if(in_array($selectedPeriod->status, ['draft', 'calculated']))
                <form method="POST" action="{{ route('operations.payroll.periods.calculate', $selectedPeriod->id) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-calculator"></i> {{ $selectedPeriod->status === 'draft' ? 'Calculate Payroll' : 'Recalculate' }}
                    </button>
                </form>
                @if($selectedPeriod->status === 'calculated')
                    <form method="POST" action="{{ route('operations.payroll.periods.submit', $selectedPeriod->id) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline">
                            <i class="bi bi-send"></i> Submit for Approval
                        </button>
                    </form>
                @endif
            @elseif($selectedPeriod->status === 'pending_approval')
                <form method="POST" action="{{ route('operations.payroll.periods.approve', $selectedPeriod->id) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this payroll period for disbursement?')">
                        <i class="bi bi-check-circle"></i> Approve Period
                    </button>
                </form>
            @elseif($selectedPeriod->status === 'approved')
                <form method="POST" action="{{ route('operations.payroll.periods.finalize', $selectedPeriod->id) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success" style="background:#059669;border-color:#059669" onclick="return confirm('Finalize and lock payroll records for payment?')">
                        <i class="bi bi-lock"></i> Finalize Payroll
                    </button>
                </form>
            @endif
        </div>
        @endif
    </div>
</div>

@if($selectedPeriod)

{{-- Period Financial Summary Dashboard --}}
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff"><i class="bi bi-people-fill"></i></div>
        <div class="stat-info">
            <span class="stat-label">Staff Included</span>
            <span class="stat-value">{{ $selectedPeriod->employee_count }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#0284c7,#38bdf8);color:#fff"><i class="bi bi-wallet2"></i></div>
        <div class="stat-info">
            <span class="stat-label">Gross Payroll</span>
            <span class="stat-value">KES {{ number_format($selectedPeriod->total_gross, 2) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#ef4444,#f87171);color:#fff"><i class="bi bi-receipt"></i></div>
        <div class="stat-info">
            <span class="stat-label">Employee Deductions</span>
            <span class="stat-value" style="color:var(--danger)">KES {{ number_format($selectedPeriod->total_statutory_deductions, 2) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#10b981,#34d399);color:#fff"><i class="bi bi-cash-stack"></i></div>
        <div class="stat-info">
            <span class="stat-label">Net Pay Payable</span>
            <span class="stat-value" style="color:var(--success)">KES {{ number_format($selectedPeriod->total_net_salary, 2) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#059669,#10b981);color:#fff"><i class="bi bi-check-circle-fill"></i></div>
        <div class="stat-info">
            <span class="stat-label">Total Disbursed (Paid)</span>
            <span class="stat-value" style="color:#059669">KES {{ number_format($selectedPeriod->total_paid, 2) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff"><i class="bi bi-exclamation-circle"></i></div>
        <div class="stat-info">
            <span class="stat-label">Unpaid Balance Due</span>
            <span class="stat-value" style="{{ $selectedPeriod->unpaid_amount > 0 ? 'color:var(--warning)' : '' }}">
                KES {{ number_format($selectedPeriod->unpaid_amount, 2) }}
            </span>
        </div>
    </div>
</div>

{{-- Payroll Register Table --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5>
            <i class="bi bi-receipt-cutoff"></i> Payroll Register: {{ $selectedPeriod->name }}
            <small style="font-size:0.8rem;color:#64748b;font-weight:normal">({{ $records->count() }} employees)</small>
        </h5>
        @if($records->isNotEmpty())
            <a href="{{ route('operations.payroll.periods.export', $selectedPeriod->id) }}" class="btn btn-sm btn-outline">
                <i class="bi bi-download"></i> Download CSV
            </a>
        @endif
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Emp No.</th>
                    <th>Employee Name</th>
                    <th>Basic (KES)</th>
                    <th>Gross (KES)</th>
                    <th>Statutory Deductions (Kenya)</th>
                    <th>Total Ded.</th>
                    <th>Net Salary (KES)</th>
                    <th>Status</th>
                    <th>Disbursed</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                <tr>
                    <td><span class="badge badge-gray" style="font-family:monospace">{{ $rec->employee?->employee_no }}</span></td>
                    <td>
                        <a href="{{ route('operations.staff.show', $rec->employee_id) }}" style="font-weight:600;color:inherit;text-decoration:none">
                            {{ $rec->employee?->full_name }}
                        </a>
                        <div style="font-size:0.75rem;color:#64748b">{{ $rec->employee?->designation }}</div>
                    </td>
                    <td>KES {{ number_format($rec->basic_salary, 2) }}</td>
                    <td style="font-weight:600">KES {{ number_format($rec->gross_salary, 2) }}</td>
                    <td style="font-size:0.75rem;line-height:1.4">
                        <div>PAYE: <strong>KES {{ number_format($rec->paye, 2) }}</strong></div>
                        <div>NSSF: <strong>KES {{ number_format($rec->nssf_employee, 2) }}</strong></div>
                        <div>SHIF: <strong>KES {{ number_format($rec->shif, 2) }}</strong></div>
                        <div>AHL: <strong>KES {{ number_format($rec->housing_levy_employee, 2) }}</strong></div>
                    </td>
                    <td style="color:var(--danger);font-weight:600">KES {{ number_format($rec->deductions, 2) }}</td>
                    <td><strong style="color:var(--success);font-size:1.05rem;font-family:'Outfit'">KES {{ number_format($rec->net_salary, 2) }}</strong></td>
                    <td>
                        @if($rec->payment_status === 'paid')
                            <span class="badge badge-success"><i class="bi bi-check-circle-fill"></i> Paid</span>
                        @elseif($rec->payment_status === 'partially_paid')
                            <span class="badge badge-warning"><i class="bi bi-pie-chart"></i> Partial</span>
                        @else
                            <span class="badge badge-gray"><i class="bi bi-clock"></i> Unpaid</span>
                        @endif
                    </td>
                    <td>
                        <div>KES {{ number_format($rec->amount_paid, 2) }}</div>
                        @if($rec->balance_due > 0)
                            <div style="font-size:0.72rem;color:var(--danger)">Due: KES {{ number_format($rec->balance_due, 2) }}</div>
                        @endif
                    </td>
                    <td style="text-align:right">
                        <div style="display:inline-flex;gap:4px">
                            <a href="{{ route('operations.payroll.records.payslip', $rec->id) }}" target="_blank" class="btn btn-sm btn-outline" title="Printable Payslip">
                                <i class="bi bi-receipt"></i> Payslip
                            </a>
                            @if($selectedPeriod->isFinalized() && $rec->balance_due > 0)
                                <button type="button" class="btn btn-sm btn-success" onclick="openPaymentModal({{ $rec->id }}, '{{ addslashes($rec->employee?->full_name) }}', {{ $rec->net_salary }}, {{ $rec->amount_paid }}, {{ $rec->balance_due }})" title="Record Payment">
                                    <i class="bi bi-cash"></i> Pay
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted" style="padding:32px">
                        <i class="bi bi-calculator" style="font-size:2rem;display:block;margin-bottom:8px"></i>
                        No payroll calculations have been prepared for this period yet.
                        <div style="margin-top:10px">
                            <form method="POST" action="{{ route('operations.payroll.periods.calculate', $selectedPeriod->id) }}" style="display:inline">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-play-circle"></i> Calculate Payroll for Active Staff
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@else
{{-- Empty state when no period exists --}}
<div class="card" style="text-align:center;padding:48px 24px">
    <i class="bi bi-calendar-x" style="font-size:3rem;color:#94a3b8;display:block;margin-bottom:12px"></i>
    <h4>No Payroll Periods Found</h4>
    <p class="text-muted" style="max-width:480px;margin:0 auto 20px">
        Get started by opening a payroll period for your school (e.g. October 2026). You will then be able to prepare, review, approve, and record verified staff payments.
    </p>
    <button class="btn btn-primary" onclick="openCreatePeriodModal()">
        <i class="bi bi-calendar-plus"></i> Open First Payroll Period
    </button>
</div>
@endif

{{-- Recent Payment Transactions Log --}}
@if($recentPayments->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-clock-history"></i> Confirmed Payment Disbursement Audit History</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Employee</th>
                    <th>Period</th>
                    <th>Amount Disbursed</th>
                    <th>Payment Method</th>
                    <th>Reference #</th>
                    <th>Disbursed By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentPayments as $pay)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') }}</td>
                    <td><strong>{{ $pay->employee?->full_name }}</strong> ({{ $pay->employee?->employee_no }})</td>
                    <td>{{ $pay->payrollPeriod?->name }}</td>
                    <td><strong style="color:var(--success)">KES {{ number_format($pay->amount, 2) }}</strong></td>
                    <td><span class="badge badge-info">{{ $pay->payment_method }}</span></td>
                    <td><span style="font-family:monospace">{{ $pay->reference_number ?? '—' }}</span></td>
                    <td>{{ $pay->recordedBy?->name ?? 'System' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Create Period Modal --}}
<div id="modal-create-period" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <h4><i class="bi bi-calendar-plus"></i> Open New Payroll Period</h4>
            <button onclick="closeCreatePeriodModal()" class="modal-close">✕</button>
        </div>
        <form method="POST" action="{{ route('operations.payroll.periods.store') }}">
            @csrf
            <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
                <div class="form-group">
                    <label>Payroll Month *</label>
                    <select name="month" class="form-control" required>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (int)date('n') === $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="form-group">
                    <label>Payroll Year *</label>
                    <select name="year" class="form-control" required>
                        @for($y = (int)date('Y') - 1; $y <= (int)date('Y') + 1; $y++)
                            <option value="{{ $y }}" {{ (int)date('Y') === $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes / Memo (Optional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Standard monthly faculty salary disbursement"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="padding:16px 20px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" onclick="closeCreatePeriodModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Open Period</button>
            </div>
        </form>
    </div>
</div>

{{-- Record Payment Modal --}}
<div id="modal-record-payment" class="modal-overlay" style="display:none">
    <div class="modal-box" style="max-width:520px">
        <div class="modal-header">
            <h4><i class="bi bi-cash-coin"></i> Record Verified Staff Payment</h4>
            <button onclick="closePaymentModal()" class="modal-close">✕</button>
        </div>
        <form id="payment-form" method="POST" action="">
            @csrf
            <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
                <div style="background:#f1f5f9;padding:12px;border-radius:6px;font-size:0.85rem">
                    <div style="font-weight:700;font-size:1rem;color:#1e293b;margin-bottom:4px" id="pay-emp-name"></div>
                    <div style="display:flex;justify-content:space-between">
                        <span>Net Salary Due: <strong id="pay-net-due"></strong></span>
                        <span>Already Paid: <strong id="pay-already-paid"></strong></span>
                    </div>
                    <div style="margin-top:4px;color:var(--danger)">
                        Outstanding Balance: <strong id="pay-balance-due"></strong>
                    </div>
                </div>

                <div class="form-group">
                    <label>Amount to Disburse (KES) *</label>
                    <input type="number" id="pay-amount-input" name="amount" class="form-control" step="0.01" min="1" required style="font-size:1.1rem;font-weight:700">
                </div>
                <div class="form-group">
                    <label>Disbursement Date *</label>
                    <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label>Payment Method *</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="Bank Transfer">Bank Transfer / Electronic Funds Transfer (EFT)</option>
                        <option value="M-Pesa">M-Pesa / Mobile Money</option>
                        <option value="Cheque">Bank Cheque</option>
                        <option value="Cash">Cash Voucher</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Bank Reference / M-Pesa Transaction Code *</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="e.g. QK89271891 or CHQ-00291" required>
                </div>
                <div class="form-group">
                    <label>Payment Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Audit notes or account particulars..."></textarea>
                </div>
            </div>
            <div class="modal-footer" style="padding:16px 20px;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" onclick="closePaymentModal()" class="btn btn-outline">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:var(--success);border-color:var(--success)">
                    <i class="bi bi-check-circle-fill"></i> Confirm & Record Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreatePeriodModal() { document.getElementById('modal-create-period').style.display = 'flex'; }
function closeCreatePeriodModal() { document.getElementById('modal-create-period').style.display = 'none'; }

function openPaymentModal(recId, empName, netSalary, amountPaid, balanceDue) {
    const form = document.getElementById('payment-form');
    form.action = '/operations/payroll/records/' + recId + '/pay';
    document.getElementById('pay-emp-name').textContent = empName;
    document.getElementById('pay-net-due').textContent = 'KES ' + Number(netSalary).toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('pay-already-paid').textContent = 'KES ' + Number(amountPaid).toLocaleString(undefined, {minimumFractionDigits: 2});
    document.getElementById('pay-balance-due').textContent = 'KES ' + Number(balanceDue).toLocaleString(undefined, {minimumFractionDigits: 2});
    
    const amountInput = document.getElementById('pay-amount-input');
    amountInput.value = balanceDue.toFixed(2);
    amountInput.max = balanceDue.toFixed(2);

    document.getElementById('modal-record-payment').style.display = 'flex';
}
function closePaymentModal() { document.getElementById('modal-record-payment').style.display = 'none'; }
</script>
@endsection
