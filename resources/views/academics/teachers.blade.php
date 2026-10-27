@extends('layouts.app')
@section('title', 'Teaching Staff')
@section('breadcrumb', 'Academics › Teachers')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>👨‍🏫 Teaching Faculty Directory</h1>
        <p>Faculty credentials, TSC registration numbers, qualifications, and active workload</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('academics.teacher-subjects') }}" class="btn btn-outline">
            <i class="bi bi-journal-bookmark"></i> Workload Allocations
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-people"></i> Appointed Teachers ({{ $teachers->count() }})</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Teacher Name</th>
                    <th>TSC Number</th>
                    <th>Email & Phone</th>
                    <th>Qualifications</th>
                    <th>Specialization</th>
                    <th>Assigned Lessons/Wk</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teachers as $t)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="avatar-text avatar-sm" style="background:linear-gradient(135deg,#059669,#047857)">
                                {{ strtoupper(substr($t->user?->name ?? 'T', 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight:700;color:var(--text-dark)">{{ $t->user?->name }}</div>
                                <div style="font-size:0.75rem;color:var(--text-muted)">Staff ID: EMP-{{ str_pad($t->id, 3, '0', STR_PAD_LEFT) }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-gray" style="font-family:monospace;font-size:0.85rem">
                            {{ $t->tsc_number ?: 'BOM-STAFF' }}
                        </span>
                    </td>
                    <td>
                        <div style="font-size:0.85rem">{{ $t->user?->email }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">{{ $t->user?->phone ?? '—' }}</div>
                    </td>
                    <td>{{ $t->qualification ?? 'B.Ed (Arts / Science)' }}</td>
                    <td>
                        <span class="badge badge-primary">{{ $t->specialization ?? 'General Teaching' }}</span>
                    </td>
                    <td>
                        @php
                            $totalLessons = $t->subjectAllocations->sum('lessons_per_week');
                        @endphp
                        <strong style="font-size:1rem;color:{{ $totalLessons > 28 ? 'var(--danger)' : 'var(--primary)' }}">
                            {{ $totalLessons }} periods
                        </strong>
                        <div style="font-size:0.7rem;color:var(--text-muted)">{{ $t->subjectAllocations->count() }} classes</div>
                    </td>
                    <td>
                        <span class="badge badge-{{ $t->status === 'active' ? 'success' : 'warning' }}">
                            {{ ucfirst($t->status ?? 'active') }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="no-data">No teachers recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
