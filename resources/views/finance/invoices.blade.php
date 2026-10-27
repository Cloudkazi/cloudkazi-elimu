@extends('layouts.app')
@section('title', 'Student Invoices & Ledgers')
@section('breadcrumb', 'Finance › Invoices')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📑 Student Invoices & Statements</h1>
        <p>Monitor student term invoices, payments, waivers, and outstanding arrears</p>
    </div>
    <div>
        <a href="{{ route('finance.payments') }}" class="btn btn-primary">
            <i class="bi bi-cash-stack"></i> Record Payment
        </a>
    </div>
</div>

<div class="stats-grid stats-grid-3" style="margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📑</div>
        <div class="stat-body">
            <div class="stat-label">Total Invoiced</div>
            <div class="stat-value">KES {{ number_format($totalInvoiced) }}</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-label">Total Collected</div>
            <div class="stat-value">KES {{ number_format($totalCollected) }}</div>
        </div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-icon">⚠️</div>
        <div class="stat-body">
            <div class="stat-label">Total Balance Due</div>
            <div class="stat-value">KES {{ number_format($totalBalance) }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-file-earmark-spreadsheet"></i> Student Invoices Roster</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Invoice No.</th>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Term</th>
                    <th>Total Invoiced</th>
                    <th>Paid Amount</th>
                    <th>Balance</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr>
                    <td>
                        <strong style="font-family:monospace;color:var(--primary)">{{ $inv->invoice_number }}</strong>
                    </td>
                    <td>
                        <div style="font-weight:600">{{ $inv->student?->full_name }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">{{ $inv->student?->admission_number }}</div>
                    </td>
                    <td>{{ $inv->student?->schoolClass?->name }}</td>
                    <td><span class="badge badge-gray">{{ $inv->term?->name ?? 'Term 1' }}</span></td>
                    <td><strong>KES {{ number_format($inv->total_amount, 2) }}</strong></td>
                    <td style="color:var(--success)">KES {{ number_format($inv->paid_amount, 2) }}</td>
                    <td>
                        <strong style="color:{{ $inv->balance > 0 ? 'var(--danger)' : 'var(--text-muted)' }}">
                            KES {{ number_format($inv->balance, 2) }}
                        </strong>
                    </td>
                    <td>
                        <span class="badge badge-{{ $inv->status === 'paid' ? 'success' : ($inv->status === 'partially_paid' ? 'warning' : 'danger') }}">
                            {{ ucwords(str_replace('_', ' ', $inv->status)) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="no-data">No student invoices generated yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
        <div class="card-footer">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection
