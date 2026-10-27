@extends('layouts.app')
@section('title', 'Payment Reconciliation Center')
@section('breadcrumb', 'Finance › Reconciliation Center')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📊 Payment Reconciliation Center</h1>
        <p>Inspect multi-channel settlement ledgers across M-Pesa, Bank Transfers, and Deposits with automated student matching</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('finance.gateway-settings') }}" class="btn btn-outline">
            <i class="bi bi-gear"></i> Gateway Settings
        </a>
        <a href="{{ route('finance.payments') }}" class="btn btn-primary">
            <i class="bi bi-wallet2"></i> Record Payment
        </a>
    </div>
</div>

{{-- KPI Summary Stats --}}
<div class="stats-grid stats-grid-4" style="margin-bottom:24px">
    <div class="stat-card stat-green">
        <div class="stat-icon">📱</div>
        <div class="stat-body">
            <div class="stat-label">Total M-Pesa Inflow</div>
            <div class="stat-value">KES {{ number_format($totalMpesa) }}</div>
            <div class="stat-trend up"><i class="bi bi-shield-check"></i> STK, Paybill &amp; Till</div>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon">🏦</div>
        <div class="stat-body">
            <div class="stat-label">Total Bank Inflow</div>
            <div class="stat-value">KES {{ number_format($totalBank) }}</div>
            <div class="stat-trend up"><i class="bi bi-bank"></i> EFT &amp; Cash Deposits</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">⏳</div>
        <div class="stat-body">
            <div class="stat-label">Open Student Invoices</div>
            <div class="stat-value">{{ number_format($unallocatedInvoices) }}</div>
            <div class="stat-trend down"><i class="bi bi-hourglass-split"></i> Awaiting settlement</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">💰</div>
        <div class="stat-body">
            <div class="stat-label">Total Fee Arrears</div>
            <div class="stat-value">KES {{ number_format($totalArrears) }}</div>
            <div class="stat-trend down"><i class="bi bi-exclamation-circle"></i> Active balance</div>
        </div>
    </div>
</div>

{{-- Main Reconciliation Tables --}}
<div style="display:grid;grid-template-columns:1fr;gap:24px;margin-bottom:30px">

    {{-- Section 1: Safaricom Daraja M-Pesa Inflow Ledger --}}
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;background:#f0fdf4;border-bottom:1px solid #bbf7d0">
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:1.4rem">📱</span>
                <div>
                    <h5 style="margin:0;color:#166534">M-Pesa Daraja Transactions Ledger</h5>
                    <small style="color:#15803d">Live IPN notifications, STK Push, Paybill and Till Number settlements</small>
                </div>
            </div>
            <span class="badge badge-success">{{ $mpesaTransactions->total() }} Total Events</span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Receipt / Ref</th>
                        <th>Student Name</th>
                        <th>Admission No</th>
                        <th>Payer Phone</th>
                        <th class="text-right">Amount (KES)</th>
                        <th>Status</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mpesaTransactions as $tx)
                    <tr>
                        <td>
                            <span class="badge badge-success">
                                {{ strtoupper($tx->mpesa_type ?? 'STK') }}
                            </span>
                        </td>
                        <td>
                            <strong style="font-family:monospace;color:#00A651">
                                {{ $tx->mpesa_receipt_number ?? $tx->checkout_request_id }}
                            </strong>
                        </td>
                        <td>
                            <strong>{{ $tx->student?->full_name ?? 'Unmatched / Payer' }}</strong>
                            @if($tx->student?->schoolClass)
                                <div style="font-size:0.75rem;color:var(--text-muted)">{{ $tx->student->schoolClass->name }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-gray" style="font-family:monospace">
                                {{ $tx->student?->admission_number ?? '—' }}
                            </span>
                        </td>
                        <td>{{ $tx->phone_number }}</td>
                        <td class="text-right">
                            <strong style="color:#00A651">KES {{ number_format($tx->amount, 2) }}</strong>
                        </td>
                        <td>
                            @if($tx->status === 'completed')
                                <span class="badge badge-success">✔ RECONCILED</span>
                            @elseif($tx->status === 'initiated')
                                <span class="badge badge-warning">⏳ INITIATED</span>
                            @else
                                <span class="badge badge-danger">FAILED</span>
                            @endif
                        </td>
                        <td style="font-size:0.8rem;color:var(--text-muted)">
                            {{ $tx->transaction_date ? \Carbon\Carbon::parse($tx->transaction_date)->format('d M Y, H:i A') : $tx->created_at->format('d M Y, H:i A') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="no-data">No M-Pesa transactions logged yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($mpesaTransactions->hasPages())
        <div style="padding:14px">
            {{ $mpesaTransactions->links() }}
        </div>
        @endif
    </div>

    {{-- Section 2: Bank Transfers & Branch Deposits Ledger --}}
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;background:#eff6ff;border-bottom:1px solid #bfdbfe">
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:1.4rem">🏦</span>
                <div>
                    <h5 style="margin:0;color:#1e40af">Bank Transfers &amp; Direct Deposits Ledger</h5>
                    <small style="color:#1d4ed8">Verified EFT, RTGS, PesaLink, and Bank Branch teller slip deposits</small>
                </div>
            </div>
            <span class="badge badge-primary">{{ $bankPayments->total() }} Total Deposits</span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Method</th>
                        <th>Bank / Account</th>
                        <th>Reference Code</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th class="text-right">Amount (KES)</th>
                        <th>Bursar Officer</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bankPayments as $bp)
                    <tr>
                        <td>
                            <a href="{{ route('finance.receipt', $bp->id) }}" target="_blank" style="font-family:monospace;font-weight:700;color:var(--primary)">
                                {{ $bp->receipt_number }}
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-primary">
                                {{ $bp->method_label }}
                            </span>
                        </td>
                        <td>
                            @if($bp->bankAccount)
                                <strong>{{ $bp->bankAccount->bank_name }}</strong>
                                <div style="font-size:0.75rem;font-family:monospace;color:var(--text-muted)">{{ $bp->bankAccount->account_number }}</div>
                            @else
                                <span style="color:var(--text-muted)">General School Account</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-gray" style="font-family:monospace">
                                {{ $bp->reference_code }}
                            </span>
                        </td>
                        <td><strong>{{ $bp->student?->full_name }}</strong></td>
                        <td>{{ $bp->student?->schoolClass?->name ?? '—' }}</td>
                        <td class="text-right">
                            <strong>KES {{ number_format($bp->amount, 2) }}</strong>
                        </td>
                        <td style="font-size:0.8rem">{{ $bp->receivedBy?->name ?? 'System' }}</td>
                        <td style="font-size:0.8rem;color:var(--text-muted)">
                            {{ $bp->payment_date?->format('d M Y') }}
                        </td>
                        <td>
                            <a href="{{ route('finance.receipt', $bp->id) }}" target="_blank" class="btn btn-outline btn-sm" title="View Official Receipt">
                                <i class="bi bi-printer"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="no-data">No bank deposits or transfers logged yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($bankPayments->hasPages())
        <div style="padding:14px">
            {{ $bankPayments->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
