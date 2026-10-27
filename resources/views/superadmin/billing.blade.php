@extends('layouts.app')
@section('title', 'Platform SaaS Billing — CloudKazi')
@section('breadcrumb', 'Platform Admin › SaaS Billing')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-cash-coin text-success"></i> CloudKazi SaaS Billing & Revenue</h1>
        <p style="color:var(--text-secondary);font-size:0.9rem;">Platform subscription fees and tenant renewal transactions (Completely isolated from school student fees)</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:18px;">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
    </div>
@endif

<!-- Filters -->
<div class="card" style="margin-bottom:20px;padding:16px;">
    <form method="GET" action="{{ route('superadmin.billing') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <div style="min-width:180px;">
            <select name="school_id" class="form-select">
                <option value="">All Schools</option>
                @foreach($schools as $sch)
                    <option value="{{ $sch->id }}" {{ request('school_id') == $sch->id ? 'selected' : '' }}>{{ $sch->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:140px;">
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <option value="successful" {{ request('status') == 'successful' ? 'selected' : '' }}>Successful</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
        <div style="min-width:140px;">
            <select name="payment_method" class="form-select">
                <option value="">All Methods</option>
                <option value="mpesa" {{ request('payment_method') == 'mpesa' ? 'selected' : '' }}>M-Pesa</option>
                <option value="bank" {{ request('payment_method') == 'bank' ? 'selected' : '' }}>Bank Transfer</option>
                <option value="paystack" {{ request('payment_method') == 'paystack' ? 'selected' : '' }}>Paystack</option>
                <option value="manual" {{ request('payment_method') == 'manual' ? 'selected' : '' }}>Manual</option>
            </select>
        </div>
        <div style="display:flex;gap:6px;align-items:center;">
            <input type="date" name="from" class="form-control" value="{{ request('from') }}" title="From Date">
            <span class="text-muted">to</span>
            <input type="date" name="to" class="form-control" value="{{ request('to') }}" title="To Date">
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        @if(request()->hasAny(['school_id', 'status', 'payment_method', 'from', 'to']))
            <a href="{{ route('superadmin.billing') }}" class="btn btn-outline">Reset</a>
        @endif
    </form>
</div>

<!-- Payments Ledger -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Payment Date</th>
                    <th>School</th>
                    <th>Package</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>M-Pesa Receipt</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $pmt)
                <tr>
                    <td>
                        <div style="font-size:0.85rem;font-weight:600;">{{ $pmt->created_at->format('d M Y') }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">{{ $pmt->created_at->format('H:i') }}</div>
                    </td>
                    <td>
                        <strong>{{ $pmt->school?->name ?? 'N/A' }}</strong>
                        <div class="text-muted" style="font-size:0.75rem;">Code: {{ $pmt->school?->code }}</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">{{ $pmt->package?->name ?? 'Standard' }}</span>
                    </td>
                    <td>
                        <span style="font-weight:700;font-size:0.95rem;color:var(--text-primary);">
                            KES {{ number_format($pmt->amount, 2) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark font-monospace">{{ strtoupper($pmt->payment_method) }}</span>
                    </td>
                    <td>
                        <span class="font-monospace" style="font-size:0.8rem;">{{ $pmt->reference }}</span>
                    </td>
                    <td>
                        @if($pmt->mpesa_receipt)
                            <span class="badge bg-success-subtle text-success font-monospace">{{ $pmt->mpesa_receipt }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($pmt->status === 'successful')
                            <span class="badge bg-success"><i class="bi bi-check2"></i> Successful</span>
                        @elseif($pmt->status === 'pending')
                            <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Pending</span>
                        @elseif($pmt->status === 'processing')
                            <span class="badge bg-info"><i class="bi bi-arrow-repeat"></i> Processing</span>
                        @elseif($pmt->status === 'failed')
                            <span class="badge bg-danger" title="{{ $pmt->failure_reason }}"><i class="bi bi-x-circle"></i> Failed</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($pmt->status) }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted" style="padding:32px;">
                        <i class="bi bi-receipt" style="font-size:2rem;"></i>
                        <p style="margin-top:8px;">No subscription payment records found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($payments->hasPages())
    <div style="padding:16px;">
        {{ $payments->links() }}
    </div>
    @endif
</div>
@endsection
