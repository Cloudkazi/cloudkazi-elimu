@extends('layouts.app')
@section('title', 'Platform HQ Dashboard — CloudKazi')
@section('breadcrumb', 'Platform Admin › SaaS Overview')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;color:var(--mc-heading, #0f172a);"><i class="bi bi-shield-check text-primary"></i> CloudKazi Platform HQ</h1>
        <p style="color:var(--mc-text-muted, #475569);font-size:0.9rem;">Multi-tenant SaaS oversight, subscription metrics, population allocation, and tenant health</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('superadmin.schools.create') }}" class="btn btn-primary">
            <i class="bi bi-building-add"></i> Onboard School
        </a>
        <a href="{{ route('superadmin.packages.create') }}" class="btn btn-outline">
            <i class="bi bi-plus-circle"></i> Create Package
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:18px;">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
    </div>
@endif
@if(session('warning'))
    <div class="alert alert-warning" style="margin-bottom:18px;">
        <i class="bi bi-exclamation-triangle"></i> {{ session('warning') }}
    </div>
@endif

<!-- Row 1: Tenant Lifecycle Stat Cards -->
<div class="stats-grid stats-grid-4" style="margin-bottom:18px;">
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="bi bi-buildings"></i></div>
        <div class="stat-body">
            <div class="stat-label">Total Schools</div>
            <div class="stat-value">{{ $totalSchools }}</div>
            <div class="stat-trend up">{{ $activeSchools }} Active / {{ $trialSchools }} Trial</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="stat-body">
            <div class="stat-label">Active Subscriptions</div>
            <div class="stat-value">{{ $activeSubscriptions }}</div>
            <div class="stat-trend up">{{ $gracePeriodSchools }} in Grace Period</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-body">
            <div class="stat-label">SaaS Revenue Collected</div>
            <div class="stat-value">KES {{ number_format($totalRevenue) }}</div>
            <div class="stat-trend up">Subscription payments</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-body">
            <div class="stat-label">Pending / Unpaid</div>
            <div class="stat-value">{{ $outstanding }}</div>
            <div class="stat-trend text-muted">{{ $pendingSchools }} pending schools</div>
        </div>
    </div>
</div>

<!-- Row 2: Account Status & Population Aggregates -->
<div class="stats-grid stats-grid-4" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-label text-muted">Expired / Suspended</div>
            <div class="stat-value" style="font-size:1.4rem;">
                <span class="text-danger">{{ $expiredSchools }}</span> / 
                <span class="text-warning">{{ $suspendedSchools }}</span>
            </div>
            <div class="stat-trend text-muted">{{ $cancelledSchools }} Cancelled</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-label text-muted">Platform Population (Aggregate)</div>
            <div class="stat-value" style="font-size:1.4rem;">{{ number_format($totalStudents) }}</div>
            <div class="stat-trend up">Students across all schools</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-label text-muted">Teaching Staff (Aggregate)</div>
            <div class="stat-value" style="font-size:1.4rem;">{{ number_format($totalTeachers) }}</div>
            <div class="stat-trend up">Teachers across all schools</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-body">
            <div class="stat-label text-muted">Billing Module</div>
            <div class="stat-value" style="font-size:1.2rem;">
                <a href="{{ route('superadmin.billing') }}" class="btn btn-sm btn-outline" style="margin-top:4px;">
                    <i class="bi bi-receipt"></i> View SaaS Ledger
                </a>
            </div>
            <div class="stat-trend text-muted">Platform-level records only</div>
        </div>
    </div>
</div>

<!-- Row 3: SaaS Visual Analytics Charts -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <div class="card" style="border:1px solid var(--border);border-radius:12px;background:var(--card-bg, #fff);box-shadow:0 2px 6px rgba(0,0,0,0.04);">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);">
            <h5 style="margin:0;font-size:0.95rem;font-weight:700;color:var(--mc-heading, #0f172a);">
                <i class="bi bi-pie-chart text-info"></i> Tenant Lifecycle Distribution
            </h5>
            <span class="badge bg-light text-dark">{{ $totalSchools }} Total Tenants</span>
        </div>
        <div class="card-body" style="display:flex;justify-content:center;align-items:center;min-height:240px;padding:20px;">
            <canvas id="tenantStatusChart" style="max-height:220px;width:100%;"></canvas>
        </div>
    </div>
    <div class="card" style="border:1px solid var(--border);border-radius:12px;background:var(--card-bg, #fff);box-shadow:0 2px 6px rgba(0,0,0,0.04);">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--border);">
            <h5 style="margin:0;font-size:0.95rem;font-weight:700;color:var(--mc-heading, #0f172a);">
                <i class="bi bi-bar-chart-line text-primary"></i> Active Subscriptions by Package
            </h5>
            <a href="{{ route('superadmin.packages') }}" class="btn btn-outline btn-sm">Manage Packages</a>
        </div>
        <div class="card-body" style="display:flex;justify-content:center;align-items:center;min-height:240px;padding:20px;">
            <canvas id="packageDistChart" style="max-height:220px;width:100%;"></canvas>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:20px;">
    <!-- Subscriptions Expiring Soon -->
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <h5 style="margin:0;font-size:1rem;font-weight:600;">
                <i class="bi bi-clock-history text-warning"></i> Subscriptions Expiring Soon (Next 60 Days)
            </h5>
            <a href="{{ route('superadmin.subscriptions') }}" class="btn btn-outline btn-sm">All Subscriptions</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>School</th>
                        <th>Package</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Days Left</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expiringSoon as $sub)
                    <tr>
                        <td>
                            <strong>{{ $sub->school?->name ?? 'Unknown School' }}</strong>
                            <div class="text-muted" style="font-size:0.75rem;">Code: {{ $sub->school?->code }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ $sub->package?->name ?? $sub->plan_name ?? 'Standard' }}</span>
                        </td>
                        <td>{{ $sub->end_date ? $sub->end_date->format('d M Y') : 'N/A' }}</td>
                        <td>
                            @if($sub->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @elseif($sub->status === 'trial')
                                <span class="badge bg-info">Trial</span>
                            @elseif($sub->status === 'grace_period')
                                <span class="badge bg-warning">Grace</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($sub->status) }}</span>
                            @endif
                        </td>
                        <td>
                            @php $rem = $sub->daysRemaining(); @endphp
                            <span class="badge {{ $rem <= 7 ? 'bg-danger' : ($rem <= 30 ? 'bg-warning text-dark' : 'bg-primary') }}">
                                {{ $rem }} days
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('superadmin.schools.account', $sub->school_id) }}" class="btn btn-sm btn-outline">
                                Account
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted" style="padding:24px;">
                            <i class="bi bi-check-circle" style="font-size:1.5rem;color:var(--success);"></i>
                            <p style="margin-top:8px;">No active subscriptions expiring in the next 60 days.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Platform Activity / Audit Trail -->
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <h5 style="margin:0;font-size:1rem;font-weight:600;">
                <i class="bi bi-journal-text text-primary"></i> Recent Platform Activity
            </h5>
            <a href="{{ route('superadmin.audit-logs') }}" class="btn btn-outline btn-sm">Full Logs</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Performed By</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentActivity as $act)
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark font-monospace" style="font-size:0.75rem;">
                                {{ $act->action }}
                            </span>
                        </td>
                        <td style="font-size:0.85rem;">{{ Str::limit($act->description, 50) }}</td>
                        <td style="font-size:0.85rem;">{{ $act->user?->name ?? 'System' }}</td>
                        <td style="font-size:0.75rem;color:var(--text-muted);">
                            {{ $act->created_at ? $act->created_at->diffForHumans() : 'N/A' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted" style="padding:24px;">
                            No platform activity logged yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Tenant Status Doughnut
    const statusCtx = document.getElementById('tenantStatusChart');
    if (statusCtx && typeof Chart !== 'undefined') {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($statusLabels) !!},
                datasets: [{
                    data: {!! json_encode($statusCounts) !!},
                    backgroundColor: [
                        '#10b981', // active - green
                        '#3b82f6', // trial - blue
                        '#f59e0b', // grace period - yellow
                        '#ef4444', // expired - red
                        '#64748b'  // suspended - slate
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 12, font: { size: 12 } }
                    }
                }
            }
        });
    }

    // 2. Package Distribution Bar
    const pkgCtx = document.getElementById('packageDistChart');
    if (pkgCtx && typeof Chart !== 'undefined') {
        new Chart(pkgCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($pkgLabels) !!},
                datasets: [{
                    label: 'Active Schools',
                    data: {!! json_encode($pkgCounts) !!},
                    backgroundColor: '#6366f1',
                    borderRadius: 6,
                    maxBarThickness: 45
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(200, 200, 200, 0.15)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
