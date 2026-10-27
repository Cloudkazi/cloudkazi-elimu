@extends('layouts.app')
@section('title', 'Warden Portal — Dashboard')
@section('breadcrumb', 'Warden Portal › Dashboard')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🏠 Hostel Warden Portal</h1>
        <p>Welcome back, {{ auth()->user()?->name ?? 'Warden' }}. Here's your boarding overview for today.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('hostel.index') }}" class="btn btn-primary">
            <i class="bi bi-house"></i> Manage Hostel & Boarding
        </a>
        <a href="{{ route('students.index') }}" class="btn btn-outline">
            <i class="bi bi-person-badge"></i> View Students
        </a>
    </div>
</div>

{{-- ===== STAT CARDS ===== --}}
<div class="stats-grid stats-grid-4" style="margin-bottom:24px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">🏠</div>
        <div class="stat-body">
            <div class="stat-label">Total Dormitories</div>
            <div class="stat-value">{{ $totalDormitories }}</div>
            <div class="stat-trend neutral">Active facilities</div>
        </div>
    </div>
    <div class="stat-card stat-indigo">
        <div class="stat-icon">🛏️</div>
        <div class="stat-body">
            <div class="stat-label">Total Beds</div>
            <div class="stat-value">{{ $totalCapacity }}</div>
            <div class="stat-trend neutral">Total capacity</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-label">Occupied Beds</div>
            <div class="stat-value">{{ $occupiedBeds }}</div>
            <div class="stat-trend {{ $occupiedBeds > 0 ? 'up' : 'neutral' }}">
                {{ $totalCapacity > 0 ? round($occupiedBeds / $totalCapacity * 100) : 0 }}% occupancy
            </div>
        </div>
    </div>
    <div class="stat-card stat-teal">
        <div class="stat-icon">🔓</div>
        <div class="stat-body">
            <div class="stat-label">Available Beds</div>
            <div class="stat-value">{{ $availableBeds }}</div>
            <div class="stat-trend {{ $availableBeds > 0 ? 'up' : 'down' }}">
                {{ $availableBeds > 0 ? 'Spaces free' : 'Full' }}
            </div>
        </div>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px">
    <div class="stat-card stat-purple">
        <div class="stat-icon">👩‍🎓</div>
        <div class="stat-body">
            <div class="stat-label">Boarding Students</div>
            <div class="stat-value">{{ $totalBoarders }}</div>
            <div class="stat-trend neutral">Fee profile: boarder</div>
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid #0d9488">
        <div class="stat-icon">🛏</div>
        <div class="stat-body">
            <div class="stat-label">Allocated Beds</div>
            <div class="stat-value">{{ $allocatedStudents }}</div>
            <div class="stat-trend up">Room & bed assigned</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">⚠️</div>
        <div class="stat-body">
            <div class="stat-label">Unallocated Boarders</div>
            <div class="stat-value">{{ $unallocatedBoarders }}</div>
            <div class="stat-trend {{ $unallocatedBoarders > 0 ? 'down' : 'up' }}">
                {{ $unallocatedBoarders > 0 ? 'Need bed assignment' : 'All assigned' }}
            </div>
        </div>
    </div>
</div>

{{-- ===== MAIN CONTENT GRID ===== --}}
<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;align-items:start">

    {{-- Recent Hostel Allocations --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-list-check"></i> Recent Bed Allocations</h5>
            <a href="{{ route('hostel.index') }}" style="font-size:0.8rem;color:var(--primary)">View All →</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Adm No.</th>
                        <th>Class</th>
                        <th>Dormitory</th>
                        <th>Bed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAllocations as $alloc)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div class="avatar-text avatar-sm" style="background:linear-gradient(135deg,
                                    {{ ($alloc->student?->gender ?? '') === 'Female' ? '#db2777,#9d174d' : '#2563eb,#1d4ed8' }})">
                                    {{ strtoupper(substr($alloc->student?->first_name ?? 'S', 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:0.85rem">{{ $alloc->student?->full_name ?? '—' }}</div>
                                    <div style="font-size:0.72rem;color:var(--text-muted)">{{ $alloc->student?->gender ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-gray">{{ $alloc->student?->admission_number ?? '—' }}</span></td>
                        <td>
                            <span class="badge badge-primary" style="font-size:0.72rem">
                                {{ $alloc->student?->schoolClass?->class_name ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:600;font-size:0.85rem">{{ $alloc->dormitory?->name ?? '—' }}</div>
                            <div style="font-size:0.72rem;color:var(--text-muted)">{{ $alloc->dormitory?->gender ?? '' }}</div>
                        </td>
                        <td><span class="badge badge-teal">{{ $alloc->bed_number ?? 'Unassigned' }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="no-data">No bed allocations recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Right column: Dormitory Facility Summary + Notices --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- Dormitory Summary --}}
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-house"></i> Dormitory Overview</h5>
                <a href="{{ route('hostel.index') }}" style="font-size:0.8rem;color:var(--primary)">Manage →</a>
            </div>
            <div class="card-body" style="padding-top:8px">
                @forelse($dormitories as $dorm)
                <div style="padding:10px 0;border-bottom:1px solid #f1f5f9">
                    <div style="display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div style="font-weight:600;font-size:0.9rem">{{ $dorm->name }}</div>
                            <div style="font-size:0.75rem;color:var(--text-muted)">
                                <span class="badge badge-{{ $dorm->gender === 'Male' ? 'primary' : 'teal' }}" style="font-size:0.7rem">{{ $dorm->gender }}</span>
                                &nbsp;Capacity: <strong>{{ $dorm->capacity }}</strong> beds
                            </div>
                        </div>
                        @php
                            $used = $dorm->students->count();
                            $pct  = $dorm->capacity > 0 ? round($used / $dorm->capacity * 100) : 0;
                        @endphp
                        <div style="text-align:right">
                            <div style="font-size:0.85rem;font-weight:700;color:{{ $pct >= 90 ? '#dc2626' : ($pct >= 60 ? '#f59e0b' : '#059669') }}">
                                {{ $used }}/{{ $dorm->capacity }}
                            </div>
                            <div style="font-size:0.7rem;color:var(--text-muted)">{{ $pct }}% full</div>
                        </div>
                    </div>
                    {{-- Mini progress bar --}}
                    <div style="height:4px;background:#f1f5f9;border-radius:2px;margin-top:6px;overflow:hidden">
                        <div style="height:100%;width:{{ $pct }}%;background:{{ $pct >= 90 ? '#dc2626' : ($pct >= 60 ? '#f59e0b' : '#059669') }};border-radius:2px;transition:width 0.3s"></div>
                    </div>
                </div>
                @empty
                <div class="no-data">No dormitories configured yet.</div>
                @endforelse
            </div>
        </div>

        {{-- Recent Notices --}}
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-megaphone"></i> Recent Notices</h5>
                <a href="{{ route('operations.notices') }}" style="font-size:0.8rem;color:var(--primary)">All Notices →</a>
            </div>
            <div class="card-body" style="padding:0">
                @forelse($announcements as $ann)
                <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9">
                    <div style="font-weight:600;font-size:0.85rem;color:var(--primary);margin-bottom:2px">
                        {{ $ann->title }}
                    </div>
                    <div style="font-size:0.8rem;color:var(--text-dark);margin-bottom:4px;line-height:1.4">
                        {{ Str::limit($ann->content, 90) }}
                    </div>
                    <div style="font-size:0.72rem;color:var(--text-muted)">
                        {{ \Carbon\Carbon::parse($ann->published_at ?? $ann->created_at)->diffForHumans() }}
                        @if($ann->target_audience)
                        &bull; <span class="badge badge-outline" style="font-size:0.68rem;border:1px solid #cbd5e1">{{ $ann->target_audience }}</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="no-data">No notices posted yet.</div>
                @endforelse
            </div>
        </div>

    </div>
</div>

@endsection
