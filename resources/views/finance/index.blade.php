@extends('layouts.app')
@section('title', 'Finance Overview')
@section('breadcrumb', 'Finance')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>💰 School Bursary & Finance Center</h1>
        <p>Comprehensive fee billing, Daraja M-Pesa automated reconciliation, and cashflow monitoring</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('finance.payments') }}" class="btn btn-primary">
            <i class="bi bi-cash-coin"></i> Record Payment
        </a>
        <a href="{{ route('finance.mpesa') }}" class="btn btn-accent" style="background:#00A651;color:#fff">
            <i class="bi bi-phone"></i> M-Pesa STK Push
        </a>
    </div>
</div>

<div class="stats-grid stats-grid-4" style="margin-bottom:24px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📑</div>
        <div class="stat-body">
            <div class="stat-label">Total Invoiced</div>
            <div class="stat-value">KES {{ number_format($totalInvoiced) }}</div>
            <div class="stat-trend up"><i class="bi bi-receipt"></i> Term billings</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-label">Total Collected</div>
            <div class="stat-value">KES {{ number_format($totalCollected) }}</div>
            <div class="stat-trend up"><i class="bi bi-check-circle"></i> Payments cleared</div>
        </div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-icon">⚠️</div>
        <div class="stat-body">
            <div class="stat-label">Total Arrears</div>
            <div class="stat-value">KES {{ number_format($totalBalance) }}</div>
            <div class="stat-trend down"><i class="bi bi-exclamation-triangle"></i> Outstanding balance</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">📈</div>
        <div class="stat-body">
            <div class="stat-label">Collection Rate</div>
            <div class="stat-value">
                {{ $totalInvoiced > 0 ? round(($totalCollected / $totalInvoiced) * 100, 1) : 0 }}%
            </div>
            <div class="stat-trend up"><i class="bi bi-graph-up"></i> Recovery efficiency</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;margin-bottom:20px">
    <!-- Recent Payments -->
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-clock-history"></i> Recent Payment Transactions</h5>
            <a href="{{ route('finance.payments') }}" class="btn btn-outline btn-sm">All Payments</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Receipt No.</th>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $p)
                    <tr>
                        <td>
                            <strong style="font-family:monospace;color:var(--primary)">{{ $p->receipt_number }}</strong>
                            <div style="font-size:0.7rem;color:var(--text-muted)">{{ $p->reference_code }}</div>
                        </td>
                        <td>
                            <div style="font-weight:600">{{ $p->student?->full_name }}</div>
                            <div style="font-size:0.75rem;color:var(--text-muted)">{{ $p->student?->schoolClass?->name }}</div>
                        </td>
                        <td>
                            <strong style="color:var(--success);font-family:'Outfit'">KES {{ number_format($p->amount) }}</strong>
                        </td>
                        <td>
                            <span class="badge badge-{{ $p->payment_method === 'mpesa' ? 'success' : 'primary' }}">
                                {{ strtoupper($p->payment_method) }}
                            </span>
                        </td>
                        <td style="font-size:0.8rem;color:var(--text-muted)">
                            {{ \Carbon\Carbon::parse($p->payment_date)->format('d M Y') }}
                        </td>
                        <td>
                            <a href="{{ route('finance.receipt', $p->id) }}" class="btn btn-outline btn-sm" title="Print Receipt">
                                <i class="bi bi-printer"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="no-data">No payment records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Navigation & Arrears -->
    <div style="display:flex;flex-direction:column;gap:20px">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-lightning-charge"></i> Finance Actions</h5>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                <a href="{{ route('finance.payments') }}" class="btn btn-primary" style="text-align:left;justify-content:flex-start">
                    <i class="bi bi-receipt" style="margin-right:8px"></i> Record Student Payment
                </a>
                <a href="{{ route('finance.fees') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                    <i class="bi bi-currency-dollar" style="margin-right:8px"></i> Fee Structures (Vote-Heads)
                </a>
                <a href="{{ route('finance.invoices') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                    <i class="bi bi-file-earmark-spreadsheet" style="margin-right:8px"></i> Student Invoices & Statements
                </a>
                <a href="{{ route('finance.arrears') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                    <i class="bi bi-exclamation-triangle" style="margin-right:8px"></i> Arrears Recovery & Reminders
                </a>
                <a href="{{ route('finance.mpesa') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                    <i class="bi bi-phone" style="margin-right:8px"></i> Daraja M-Pesa Gateway
                </a>
                <a href="{{ route('finance.reports') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                    <i class="bi bi-graph-up" style="margin-right:8px"></i> Financial Audit Reports
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
