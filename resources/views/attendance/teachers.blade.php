@extends('layouts.app')
@section('title', 'Staff Attendance')
@section('breadcrumb', 'Attendance › Staff')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>👩‍🏫 Teacher & Staff Attendance Register</h1>
        <p>Record daily clock-in, clock-out, presence, and leave for teaching faculty</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('attendance.index') }}" class="btn btn-outline">
            <i class="bi bi-person-check"></i> Student Attendance
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-clock-history"></i> Faculty Roll Call ({{ \Carbon\Carbon::parse($date)->format('d M Y') }})</h5>
        <form method="GET" action="{{ route('attendance.teachers') }}" style="display:flex;gap:10px;align-items:center">
            <input type="date" name="date" class="form-control" value="{{ $date }}">
            <button type="submit" class="btn btn-sm btn-outline">Go</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($teachers as $t)
                @php $att = $attendances->get($t->id); @endphp
                <tr>
                    <td>
                        <strong>{{ $t->user?->name ?? 'Staff' }}</strong>
                        <div style="font-size:0.75rem;color:var(--text-muted)">{{ $t->user?->email }}</div>
                    </td>
                    <td>{{ $t->department?->name ?? 'General Faculty' }}</td>
                    <td>
                        @if($att && $att->status === 'present')
                        <span class="badge badge-success">Present</span>
                        @elseif($att && $att->status === 'late')
                        <span class="badge badge-warning">Late</span>
                        @elseif($att && $att->status === 'absent')
                        <span class="badge badge-danger">Absent</span>
                        @elseif($att && $att->status === 'on_leave')
                        <span class="badge badge-secondary">On Leave</span>
                        @else
                        <span class="badge" style="background:#e2e8f0;color:#64748b">Not Recorded</span>
                        @endif
                    </td>
                    <td>{{ $att?->check_in_time ?? '—' }}</td>
                    <td>{{ $att?->check_out_time ?? '—' }}</td>
                    <td>
                        <form method="POST" action="{{ route('attendance.teachers.store') }}" style="display:flex;gap:6px;align-items:center">
                            @csrf
                            <input type="hidden" name="teacher_id" value="{{ $t->id }}">
                            <input type="hidden" name="attendance_date" value="{{ $date }}">
                            <select name="status" class="form-select form-select-sm" style="width:auto">
                                <option value="present" {{ ($att?->status ?? '') === 'present' ? 'selected' : '' }}>Present</option>
                                <option value="late" {{ ($att?->status ?? '') === 'late' ? 'selected' : '' }}>Late</option>
                                <option value="absent" {{ ($att?->status ?? '') === 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="on_leave" {{ ($att?->status ?? '') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Save</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">No teachers registered.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
