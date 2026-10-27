@extends('layouts.app')
@section('title', 'SaaS Subscriptions — Platform Super Admin')
@section('breadcrumb', 'Platform Admin › Subscriptions')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-credit-card-2-front text-primary"></i> SaaS Tenant Subscriptions</h1>
        <p style="color:var(--text-secondary);font-size:0.9rem;">Monitor tenant subscription lifecycles, renewal dates, payment statuses, and capacity limits</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:18px;">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
    </div>
@endif

<!-- Filter -->
<div class="card" style="margin-bottom:20px;padding:16px;">
    <form method="GET" action="{{ route('superadmin.subscriptions') }}" style="display:flex;gap:12px;align-items:center;">
        <div style="min-width:180px;">
            <select name="status" class="form-select">
                <option value="">All Subscription Statuses</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="trial" {{ request('status') == 'trial' ? 'selected' : '' }}>Trial</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="grace_period" {{ request('status') == 'grace_period' ? 'selected' : '' }}>Grace Period</option>
                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        @if(request()->filled('status'))
            <a href="{{ route('superadmin.subscriptions') }}" class="btn btn-outline">Reset</a>
        @endif
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>School</th>
                    <th>Package Tier</th>
                    <th>Annual License</th>
                    <th>Start Date</th>
                    <th>Expiry Date</th>
                    <th>Days Left</th>
                    <th>Payment Status</th>
                    <th>Subscription Status</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                <tr>
                    <td>
                        <strong>
                            <a href="{{ route('superadmin.schools.account', $sub->school_id) }}" style="color:var(--text-primary);text-decoration:none;">
                                {{ $sub->school?->name ?? 'Unknown School' }}
                            </a>
                        </strong>
                        <div class="text-muted" style="font-size:0.75rem;">Code: {{ $sub->school?->code }}</div>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary">
                            {{ $sub->package?->name ?? $sub->plan_name ?? 'Standard' }}
                        </span>
                    </td>
                    <td>
                        <strong>KES {{ number_format($sub->annual_fee ?? $sub->amount ?? 0) }}</strong>
                    </td>
                    <td>
                        {{ $sub->start_date ? $sub->start_date->format('d M Y') : 'N/A' }}
                    </td>
                    <td>
                        <strong>{{ $sub->end_date ? $sub->end_date->format('d M Y') : 'N/A' }}</strong>
                    </td>
                    <td>
                        @php $rem = $sub->daysRemaining(); @endphp
                        <span class="badge {{ $rem <= 7 ? 'bg-danger' : ($rem <= 30 ? 'bg-warning text-dark' : 'bg-primary') }}">
                            {{ $rem }} days
                        </span>
                    </td>
                    <td>
                        @if($sub->payment_status === 'successful')
                            <span class="badge bg-success"><i class="bi bi-check2"></i> Paid</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> {{ ucfirst($sub->payment_status ?? 'Pending') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($sub->status === 'active')
                            <span class="badge bg-success">Active</span>
                        @elseif($sub->status === 'trial')
                            <span class="badge bg-info">Trial</span>
                        @elseif($sub->status === 'grace_period')
                            <span class="badge bg-warning text-dark">Grace Period</span>
                        @elseif($sub->status === 'suspended')
                            <span class="badge bg-danger">Suspended</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($sub->status) }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <a href="{{ route('superadmin.schools.account', $sub->school_id) }}" class="btn btn-sm btn-outline">
                            Manage
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted" style="padding:32px;">
                        No subscriptions recorded.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($subscriptions->hasPages())
    <div style="padding:16px;">
        {{ $subscriptions->links() }}
    </div>
    @endif
</div>
@endsection
