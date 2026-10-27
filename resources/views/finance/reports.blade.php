@extends('layouts.app')
@section('title', 'Financial Reports & Audits')
@section('breadcrumb', 'Finance › Reports')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📈 Financial Audit & Revenue Analytics</h1>
        <p>Institutional income statements, payment channel breakdowns, and fee collection efficiency</p>
    </div>
</div>

<div class="stats-grid stats-grid-3" style="margin-bottom:24px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">💰</div>
        <div class="stat-body">
            <div class="stat-label">Total Invoiced Billing</div>
            <div class="stat-value">KES {{ number_format($totalInvoiced) }}</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">🏦</div>
        <div class="stat-body">
            <div class="stat-label">Total Collections Cleared</div>
            <div class="stat-value">KES {{ number_format($totalCollected) }}</div>
        </div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-icon">⚠️</div>
        <div class="stat-body">
            <div class="stat-label">Cumulative Arrears</div>
            <div class="stat-value">KES {{ number_format($totalBalance) }}</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:20px;margin-bottom:20px">
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-pie-chart"></i> Revenue by Payment Channel</h5>
        </div>
        <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:14px">
                @php
                    $methods = [
                        'mpesa' => ['name' => 'M-Pesa Daraja', 'color' => '#00A651', 'icon' => 'phone'],
                        'bank' => ['name' => 'Direct Bank Deposit', 'color' => '#2563eb', 'icon' => 'bank'],
                        'cash' => ['name' => 'Cash / Bursar Counter', 'color' => '#e8922a', 'icon' => 'cash'],
                        'cheque' => ['name' => 'Bankers Cheque', 'color' => '#8b5cf6', 'icon' => 'card-text'],
                    ];
                @endphp
                @foreach($methods as $key => $meta)
                    @php $amt = $byMethod[$key] ?? 0; @endphp
                    <div>
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                            <span><i class="bi bi-{{ $meta['icon'] }}" style="color:{{ $meta['color'] }};margin-right:6px"></i> {{ $meta['name'] }}</span>
                            <strong>KES {{ number_format($amt) }}</strong>
                        </div>
                        <div style="background:#e2e8f0;height:8px;border-radius:4px;overflow:hidden">
                            <div style="background:{{ $meta['color'] }};height:100%;width:{{ $totalCollected > 0 ? min(100, round(($amt / $totalCollected) * 100)) : 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-clock-history"></i> Latest Cleared Transactions</h5>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Receipt</th>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Channel</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $p)
                    <tr>
                        <td><strong style="font-family:monospace">{{ $p->receipt_number }}</strong></td>
                        <td>{{ $p->student?->full_name }}</td>
                        <td><strong>KES {{ number_format($p->amount) }}</strong></td>
                        <td><span class="badge badge-primary">{{ strtoupper($p->payment_method) }}</span></td>
                        <td>{{ \Carbon\Carbon::parse($p->payment_date)->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="no-data">No transactions recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
