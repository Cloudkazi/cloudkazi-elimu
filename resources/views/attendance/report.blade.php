@extends('layouts.app')
@section('title', 'Attendance Reports')
@section('breadcrumb', 'Attendance › Reports')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📊 Attendance Analytical Report</h1>
        <p>Comprehensive attendance tracking, analytics, and roll call summaries</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('attendance.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back to Register
        </a>
        <button class="btn btn-outline" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Report
        </button>
    </div>
</div>

<!-- Stats Row -->
<div class="stats-grid stats-grid-4" style="margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📋</div>
        <div class="stat-body">
            <div class="stat-label">Total Roll Calls</div>
            <div class="stat-value">{{ number_format($totalMarked) }}</div>
            <div class="stat-trend neutral">Recorded</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-label">Present Count</div>
            <div class="stat-value">{{ number_format($totalPresent) }}</div>
            <div class="stat-trend up">{{ $attendanceRate }}% Overall Rate</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">⚠️</div>
        <div class="stat-body">
            <div class="stat-label">Late Arrivals</div>
            <div class="stat-value">{{ number_format($totalLate) }}</div>
            <div class="stat-trend down">Need Follow-up</div>
        </div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-icon">❌</div>
        <div class="stat-body">
            <div class="stat-label">Absent Students</div>
            <div class="stat-value">{{ number_format($totalAbsent) }}</div>
            <div class="stat-trend down">Total Truancy</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" action="{{ route('attendance.report') }}" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:16px;align-items:end">
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="form-group">
                <label class="form-label">Filter by Class</label>
                <select name="class_id" class="form-select">
                    <option value="">All Classes</option>
                    @foreach($classes as $c)
                    <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Records Table -->
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-table"></i> Roll Call History</h5>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student Name</th>
                    <th>Admission No</th>
                    <th>Class / Stream</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($rec->attendance_date)->format('d M Y') }}</td>
                    <td><strong>{{ $rec->student?->full_name ?? 'N/A' }}</strong></td>
                    <td>{{ $rec->student?->admission_number ?? 'N/A' }}</td>
                    <td>{{ $rec->student?->schoolClass?->name ?? '' }} ({{ $rec->stream?->name ?? 'A' }})</td>
                    <td>
                        @if($rec->status === 'present')
                        <span class="badge badge-success">Present</span>
                        @elseif($rec->status === 'late')
                        <span class="badge badge-warning">Late</span>
                        @elseif($rec->status === 'absent')
                        <span class="badge badge-danger">Absent</span>
                        @else
                        <span class="badge badge-secondary">{{ ucfirst($rec->status) }}</span>
                        @endif
                    </td>
                    <td>{{ $rec->remarks ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="no-data">No attendance records found for this period.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
    <div class="card-footer">
        {{ $records->links() }}
    </div>
    @endif
</div>
@endsection
