@extends('layouts.app')
@section('title', 'Tenant Schools — Platform Super Admin')
@section('breadcrumb', 'Platform Admin › Tenant Schools')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-buildings text-primary"></i> Tenant Schools</h1>
        <p style="color:var(--text-secondary);font-size:0.9rem;">Manage school accounts, subscription tiers, population licenses, and module access</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('superadmin.schools.create') }}" class="btn btn-primary">
            <i class="bi bi-building-add"></i> Onboard School
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:18px;">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
    </div>
@endif

<!-- Search & Status Filters -->
<div class="card" style="margin-bottom:20px;padding:16px;">
    <form method="GET" action="{{ route('superadmin.schools') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <div style="flex:1;min-width:220px;">
            <input type="text" name="search" class="form-control" placeholder="Search by school name or code..." value="{{ request('search') }}">
        </div>
        <div style="min-width:180px;">
            <select name="status" class="form-select">
                <option value="">All Account Statuses</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="trial" {{ request('status') == 'trial' ? 'selected' : '' }}>Trial</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="grace_period" {{ request('status') == 'grace_period' ? 'selected' : '' }}>Grace Period</option>
                <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('superadmin.schools') }}" class="btn btn-outline">Reset</a>
        @endif
    </form>
</div>

<!-- Schools Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>School</th>
                    <th>Code</th>
                    <th>Package</th>
                    <th>Account Status</th>
                    <th>Subscription</th>
                    <th>Expiry</th>
                    <th>Student License</th>
                    <th>Teacher License</th>
                    <th>Features</th>
                    <th>Payment Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schools as $school)
                @php
                    $sub = $school->subscription;
                    $studentLimit = \App\Services\PopulationAllocationService::getStudentLimit($school->id);
                    $teacherLimit = \App\Services\PopulationAllocationService::getTeacherLimit($school->id);
                    $currentStudents = $school->getStudentCount();
                    $currentTeachers = $school->getTeacherCount();
                    $featureCount = $school->features()->where('enabled', true)->count();
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:0.95rem;">
                            <a href="{{ route('superadmin.schools.account', $school->id) }}" style="color:var(--text-primary);text-decoration:none;">
                                {{ $school->name }}
                            </a>
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">{{ $school->county ?? 'Kenya' }} • {{ $school->curriculum_type }}</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark font-monospace">{{ $school->code }}</span>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary">
                            {{ $sub?->package?->name ?? $sub?->plan_name ?? 'None' }}
                        </span>
                    </td>
                    <td>
                        @if($school->status === 'active')
                            <span class="badge bg-success">Active</span>
                        @elseif($school->status === 'trial')
                            <span class="badge bg-info">Trial</span>
                        @elseif($school->status === 'suspended')
                            <span class="badge bg-danger">Suspended</span>
                        @elseif($school->status === 'grace_period')
                            <span class="badge bg-warning">Grace Period</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($school->status) }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $sub?->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($sub?->status ?? 'None') }}
                        </span>
                    </td>
                    <td>
                        @if($sub?->end_date)
                            <div style="font-size:0.85rem;">{{ $sub->end_date->format('d M Y') }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">{{ $sub->daysRemaining() }} days left</div>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-size:0.85rem;font-weight:600;">{{ $currentStudents }} / {{ $studentLimit ?? '∞' }}</div>
                        @if($studentLimit)
                            <div class="progress" style="height:4px;width:80px;margin-top:4px;">
                                @php $pct = min(100, round(($currentStudents / $studentLimit) * 100)); @endphp
                                <div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : ($pct >= 75 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ $pct }}%"></div>
                            </div>
                        @endif
                    </td>
                    <td>
                        <div style="font-size:0.85rem;">{{ $currentTeachers }} / {{ $teacherLimit ?? '∞' }}</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">{{ $featureCount }} active</span>
                    </td>
                    <td>
                        @if($sub?->payment_status === 'successful')
                            <span class="badge bg-success"><i class="bi bi-check2"></i> Paid</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> {{ ucfirst($sub?->payment_status ?? 'Pending') }}</span>
                        @endif
                    </td>
                    <td style="text-align:right;">
                        <div class="dropdown" style="display:inline-block;">
                            <a href="{{ route('superadmin.schools.account', $school->id) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-gear"></i> Manage Account
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center text-muted" style="padding:32px;">
                        <i class="bi bi-building-exclamation" style="font-size:2rem;"></i>
                        <p style="margin-top:8px;">No schools found matching your search criteria.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($schools->hasPages())
    <div style="padding:16px;">
        {{ $schools->links() }}
    </div>
    @endif
</div>
@endsection
