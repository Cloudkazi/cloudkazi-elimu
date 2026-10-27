@extends('layouts.app')
@section('title', 'Fee Statement – ' . $student->full_name)
@section('breadcrumb', 'Finance › Student Statement')

@section('content')

{{-- ── Page Header ────────────────────────────────────────────────── --}}
<div class="page-header">
    <div class="page-header-text">
        <h1>📑 Student Fee Statement</h1>
        <p>
            Comprehensive billing &amp; payment ledger for
            <strong>{{ $student->full_name }}</strong>
            ({{ $student->admission_number }})
            &bull; {{ $student->schoolClass?->name ?? '—' }}
            @if($student->stream) / {{ $student->stream->name }} @endif
        </p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('students.show', $student->id) }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Student Profile
        </a>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Statement
        </button>
    </div>
</div>

{{-- ── Balance Summary ────────────────────────────────────────────── --}}
<div class="stats-grid stats-grid-3" style="margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📄</div>
        <div class="stat-body">
            <div class="stat-label">Total Billed</div>
            <div class="stat-value">KES {{ number_format($totalInvoiced) }}</div>
            <div class="stat-trend neutral">Gross Invoiced</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-label">Total Paid</div>
            <div class="stat-value">KES {{ number_format($totalPaid) }}</div>
            <div class="stat-trend up">Payments Received</div>
        </div>
    </div>
    <div class="stat-card {{ $balance > 0 ? 'stat-red' : 'stat-green' }}">
        <div class="stat-icon">{{ $balance > 0 ? '⚠️' : '🎉' }}</div>
        <div class="stat-body">
            <div class="stat-label">Outstanding Balance</div>
            <div class="stat-value">KES {{ number_format($balance) }}</div>
            <div class="stat-trend {{ $balance > 0 ? 'down' : 'up' }}">
                {{ $balance > 0 ? 'Arrears Due' : 'Fully Cleared' }}
            </div>
        </div>
    </div>
</div>

{{-- ── Fee Category Breakdown ─────────────────────────────────────── --}}
@if(!empty($feeBreakdown['categories']))
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h5><i class="bi bi-grid-3x3-gap"></i> Fee Category Breakdown
            &nbsp;<span style="font-size:.78rem;font-weight:400;opacity:.65">
                – based on student's fee profile:
                <strong>{{ ucfirst(str_replace('_',' ', $student->fee_profile ?? 'day_scholar')) }}</strong>
            </span>
        </h5>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;padding:18px">
        @foreach($feeBreakdown['categories'] as $key => $cat)
        @php
            $icons = ['tuition' => '📚', 'boarding' => '🏠', 'transport' => '🚌', 'other' => '🧾'];
            $icon  = $icons[$key] ?? '💰';
        @endphp
        <div style="
            border:1px solid {{ $cat['applicable'] ? 'var(--border)' : '#e0e0e0' }};
            border-radius:10px;
            padding:16px;
            background:{{ $cat['applicable'] ? 'var(--card-bg)' : '#f8f9fa' }};
            opacity:{{ $cat['applicable'] ? '1' : '0.55' }};
        ">
            <div style="font-size:1.6rem;margin-bottom:6px">{{ $icon }}</div>
            <div style="font-weight:600;margin-bottom:4px">{{ $cat['label'] }}</div>
            <div style="font-size:1.2rem;font-weight:700;color:{{ $cat['applicable'] ? 'var(--primary)' : '#aaa' }}">
                KES {{ number_format($cat['amount']) }}
            </div>
            <div style="font-size:.78rem;color:var(--muted);margin-top:4px">{{ $cat['description'] }}</div>
            @if(!$cat['applicable'])
                <span class="badge badge-secondary" style="margin-top:6px">Not Applicable</span>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Breakdown totals bar --}}
    <div style="display:flex;justify-content:flex-end;gap:20px;padding:10px 18px 14px;border-top:1px solid var(--border);font-size:.9rem">
        <span>Total Billed: <strong>KES {{ number_format($feeBreakdown['total_billed']) }}</strong></span>
        <span>Total Paid: <strong style="color:var(--success)">KES {{ number_format($feeBreakdown['total_paid']) }}</strong></span>
        <span>Balance: <strong style="color:{{ $feeBreakdown['balance'] > 0 ? 'var(--danger)' : 'var(--success)' }}">
            KES {{ number_format($feeBreakdown['balance']) }}
        </strong></span>
        @if($feeBreakdown['is_cleared'])
            <span class="badge badge-success">✅ Cleared</span>
        @else
            <span class="badge badge-danger">⚠️ Arrears</span>
        @endif
    </div>
</div>
@endif

{{-- ── Invoices & Payments Grid ───────────────────────────────────── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    {{-- INVOICES TABLE --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-file-earmark-text"></i> Invoices &amp; Term Billings</h5>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Term</th>
                        <th>Amount</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($student->invoices as $inv)
                    <tr>
                        <td><strong>{{ $inv->invoice_number }}</strong></td>
                        <td>{{ $inv->term?->name ?? '—' }}</td>
                        <td>KES {{ number_format($inv->total_amount) }}</td>
                        <td>
                            <span class="badge badge-{{ $inv->balance > 0 ? 'danger' : 'success' }}">
                                KES {{ number_format($inv->balance) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="no-data">No invoices recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- PAYMENTS TABLE --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-receipt"></i> Payment Transactions</h5>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $pmt)
                    <tr>
                        <td><strong>{{ $pmt->receipt_number }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($pmt->payment_date)->format('d M Y') }}</td>
                        <td><span class="badge badge-info">{{ strtoupper($pmt->payment_method) }}</span></td>
                        <td style="color:var(--success);font-weight:600">KES {{ number_format($pmt->amount) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="no-data">No payments recorded yet.</td></tr>
                    @endforelse
                </tbody>
                @if($payments->count())
                <tfoot>
                    <tr style="background:var(--card-bg);font-weight:700">
                        <td colspan="3" style="text-align:right">Total Paid:</td>
                        <td style="color:var(--success)">KES {{ number_format($totalPaid) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>

{{-- ── Payment Reference Note ──────────────────────────────────────── --}}
@if($payments->count() && $balance > 0)
<div class="alert alert-warning" style="margin-top:16px">
    <i class="bi bi-info-circle"></i>
    <strong>Note:</strong> An outstanding balance of <strong>KES {{ number_format($balance) }}</strong> remains.
    Please ensure payment is made before the end of the current term.
    Contact the school Bursar for payment arrangements.
</div>
@endif

@endsection
