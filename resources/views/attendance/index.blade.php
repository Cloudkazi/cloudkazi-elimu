@extends('layouts.app')
@section('title', 'Take Attendance')
@section('breadcrumb', 'Attendance')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>📋 Daily Attendance</h1>
        <p>Mark student attendance for {{ now()->format('l, d F Y') }}</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('attendance.report') }}" class="btn btn-outline">
            <i class="bi bi-bar-chart"></i> Attendance Report
        </a>
        <button class="btn btn-outline" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Register
        </button>
    </div>
</div>

<!-- Quick Stats -->
<div class="stats-grid stats-grid-5" style="margin-bottom:20px">
    <div class="stat-card stat-green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-label">Present</div>
            <div class="stat-value" id="count-present">{{ $todayCounts['present'] ?? 0 }}</div>
        </div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-icon">❌</div>
        <div class="stat-body">
            <div class="stat-label">Absent</div>
            <div class="stat-value" id="count-absent">{{ $todayCounts['absent'] ?? 0 }}</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">⏰</div>
        <div class="stat-body">
            <div class="stat-label">Late</div>
            <div class="stat-value" id="count-late">{{ $todayCounts['late'] ?? 0 }}</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">📝</div>
        <div class="stat-body">
            <div class="stat-label">Excused</div>
            <div class="stat-value" id="count-excused">{{ $todayCounts['excused'] ?? 0 }}</div>
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--text-muted)">
        <div class="stat-icon">⬜</div>
        <div class="stat-body">
            <div class="stat-label">Unmarked</div>
            <div class="stat-value" style="color:var(--text-muted)">{{ $todayCounts['unmarked'] ?? 0 }}</div>
        </div>
    </div>
</div>

<!-- Class & Date Filter -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" action="{{ route('attendance.index') }}"
              style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:12px;align-items:end">
            <div>
                <label class="form-label">Select Class</label>
                <select name="class_id" class="form-select" id="class-select" onchange="this.form.submit()">
                    <option value="">Choose Class...</option>
                    @foreach($classes ?? [] as $class)
                    <option value="{{ $class->id }}"
                        data-has-streams="{{ $class->streams->isNotEmpty() ? '1' : '0' }}"
                        {{ (isset($selectedClassId) && $selectedClassId == $class->id) ? 'selected' : '' }}>
                        {{ $class->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Stream <span style="color:var(--text-muted);font-size:0.8em">(optional)</span></label>
                <select name="stream_id" class="form-select" id="stream-select">
                    <option value="">All Streams</option>
                    @if(isset($selectedClass) && $selectedClass->streams->isNotEmpty())
                        @foreach($selectedClass->streams as $stream)
                        <option value="{{ $stream->id }}" {{ (isset($selectedStreamId) && $selectedStreamId == $stream->id) ? 'selected' : '' }}>
                            {{ $stream->name }}{{ $stream->pathway && $stream->pathway !== 'general' ? ' (' . $stream->pathway . ')' : '' }}
                        </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div>
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control"
                    value="{{ request('date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}">
            </div>
            <div>
                <label class="form-label">Session</label>
                <select name="session" class="form-select">
                    <option value="morning" {{ request('session','morning') === 'morning' ? 'selected' : '' }}>Morning</option>
                    <option value="afternoon" {{ request('session') === 'afternoon' ? 'selected' : '' }}>Afternoon</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Load</button>
            </div>
        </form>
    </div>
</div>

@if(isset($selectedClass))
<!-- Attendance Register -->
<div class="card">
    <div class="card-header">
        <h5>
            <i class="bi bi-check2-all"></i>
            {{ $selectedClass->name }}{{ isset($selectedStreamId) && $selectedStreamId ? ' — ' . ($selectedClass->streams->firstWhere('id', $selectedStreamId)?->name ?? '') : '' }} — Attendance Register
            <span class="badge badge-info" style="margin-left:8px">{{ $students->count() ?? 0 }} students</span>
            @if(($todayCounts['unmarked'] ?? 0) > 0)
            <span class="badge" style="margin-left:6px;background:var(--text-muted);color:#fff">{{ $todayCounts['unmarked'] }} unmarked</span>
            @endif
        </h5>
        <div style="display:flex;gap:8px">
            <button class="btn btn-success btn-sm mark-all-btn" data-val="present">
                ✅ All Present
            </button>
            <button class="btn btn-outline btn-sm mark-all-btn" data-val="absent" style="color:var(--danger);border-color:var(--danger)">
                ❌ All Absent
            </button>
        </div>
    </div>

    <form action="{{ route('attendance.store') }}" method="POST" id="attendance-form">
        @csrf
        <input type="hidden" name="class_id" value="{{ $selectedClass->id }}">
        <input type="hidden" name="date" value="{{ request('date', date('Y-m-d')) }}">
        <input type="hidden" name="session" value="{{ request('session', 'morning') }}">

        <div class="card-body" style="padding:0">
            <div style="padding:10px 16px;background:var(--bg);border-bottom:1px solid var(--border);
                display:grid;grid-template-columns:50px 1fr 120px 280px 1fr;gap:12px;align-items:center;
                font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--text-muted)">
                <div>#</div>
                <div>Student Name</div>
                <div>Adm No.</div>
                <div>Status</div>
                <div>Remarks</div>
            </div>
            @forelse($students ?? [] as $i => $student)
            <div style="padding:8px 16px;border-bottom:1px solid var(--border-light);
                display:grid;grid-template-columns:50px 1fr 120px 280px 1fr;gap:12px;align-items:center;
                transition:background 0.15s">
                <div style="color:var(--text-muted);font-size:0.875rem">{{ $i + 1 }}</div>
                <div style="display:flex;align-items:center;gap:10px">
                    <div class="avatar-text avatar-sm" style="background:linear-gradient(135deg,
                        {{ $student->gender === 'Female' ? '#db2777,#9d174d' : '#2563eb,#1d4ed8' }});font-size:11px">
                        {{ strtoupper(substr($student->first_name, 0, 1)) }}
                    </div>
                    <span style="font-weight:600;font-size:0.875rem">{{ $student->full_name }}</span>
                </div>
                <div style="font-size:0.8rem"><code>{{ $student->admission_number }}</code></div>
                <div>
                    <div style="display:flex;gap:6px">
                        @php
                            $existing = $existingAttendance[$student->id] ?? null;
                            $statuses = [
                                'present' => ['✅','success'],
                                'absent'  => ['❌','danger'],
                                'late'    => ['⏰','warning'],
                                'excused' => ['📝','purple'],
                            ];
                        @endphp
                        @foreach($statuses as $status => [$icon, $color])
                        <label style="cursor:pointer">
                            <input type="radio" name="attendance[{{ $student->id }}][status]"
                                value="{{ $status }}" style="display:none"
                                class="att-status-radio" data-student="{{ $student->id }}"
                                {{ ($existing?->status ?? 'present') === $status ? 'checked' : '' }}>
                            <span class="att-radio-label badge badge-{{ $color }}"
                                  data-status="{{ $status }}"
                                  style="cursor:pointer;opacity:{{ ($existing?->status ?? 'present') === $status ? '1' : '0.3' }}">
                                {{ $icon }} {{ ucfirst($status) }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <input type="text" name="attendance[{{ $student->id }}][remarks]"
                        class="form-control" style="padding:5px 10px;font-size:0.8rem"
                        placeholder="Optional remarks..."
                        value="{{ $existing?->remarks ?? '' }}">
                </div>
            </div>
            @empty
            <div class="no-data" style="padding:40px">
                <i class="bi bi-people"></i>
                No students found in this class.
            </div>
            @endforelse
        </div>

        <div class="card-footer" style="display:flex;justify-content:flex-end;gap:12px">
            <span style="flex:1;color:var(--text-muted);font-size:0.875rem;align-self:center">
                Marking for <strong>{{ $selectedClass->class_name }}</strong> on
                <strong>{{ request('date', date('d M Y')) }}</strong>
            </span>
            <button type="submit" name="save_type" value="save" class="btn btn-primary btn-lg">
                <i class="bi bi-save"></i> Save Attendance
            </button>
        </div>
    </form>
</div>

@else
<div class="card">
    <div class="card-body no-data" style="padding:60px">
        <i class="bi bi-calendar-check"></i>
        <div style="font-size:1rem;font-weight:600;margin-top:8px">Select a class to take attendance</div>
        <div style="color:var(--text-muted);margin-top:4px">Choose a class from the filter above to start marking attendance</div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
// Attendance radio visual toggle
document.querySelectorAll('.att-status-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        const row = this.closest('div[style*="grid-template-columns"]');
        const labels = row.querySelectorAll('.att-radio-label');
        labels.forEach(l => l.style.opacity = '0.3');
        const checkedLabel = row.querySelector(`[data-status="${this.value}"]`);
        if (checkedLabel) checkedLabel.style.opacity = '1';
    });
});

// Mark all
document.querySelectorAll('.mark-all-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const val = this.dataset.val;
        document.querySelectorAll(`.att-status-radio[value="${val}"]`).forEach(r => {
            r.checked = true;
            r.dispatchEvent(new Event('change'));
        });
    });
});
</script>
@endpush
