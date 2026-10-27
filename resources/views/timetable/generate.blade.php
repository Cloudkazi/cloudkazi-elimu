@extends('layouts.app')
@section('title', 'Generate Timetable')
@section('breadcrumb', 'Academics › Generate Timetable')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📅 Generate Timetable</h1>
        <p>Configure the generation constraints before creating a collision-free class timetable.</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('timetable.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back to Timetable
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ── Pre-flight Readiness Panel ── --}}
<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-clipboard-check"></i> Pre-Generation Readiness Check</h5>
        <button type="button" class="btn btn-outline btn-sm" onclick="loadReadiness()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>
    <div class="card-body" id="readiness-panel">
        <div style="text-align:center;color:var(--text-muted);padding:20px">
            <i class="bi bi-hourglass-split" style="font-size:2rem"></i>
            <p>Loading readiness report…</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-sliders"></i> Generation Settings</h5>
    </div>
    <form action="{{ route('timetable.generate') }}" method="POST">
        @csrf

        <div class="card-body" style="display:grid;grid-template-columns:repeat(2,minmax(220px,1fr));gap:16px;">
            <div class="form-group">
                <label class="form-label">Academic year</label>
                <input type="text" class="form-control" value="{{ $currentYear?->name ?? 'Not configured' }}" disabled>
            </div>

            <div class="form-group">
                <label class="form-label">Academic term</label>
                <input type="text" class="form-control" value="{{ $currentTerm?->name ?? 'Not configured' }}" disabled>
            </div>

            <div class="form-group">
                <label class="form-label">Max lessons per teacher/day</label>
                <input type="number" name="max_lessons_per_teacher_per_day" class="form-control" value="{{ $generationSettings->max_lessons_per_teacher_per_day ?? 6 }}" min="1" max="12">
            </div>

            <div class="form-group">
                <label class="form-label">Max consecutive lessons/teacher</label>
                <input type="number" name="max_consecutive_lessons_per_teacher" class="form-control" value="{{ $generationSettings->max_consecutive_lessons_per_teacher ?? 2 }}" min="1" max="6">
            </div>

            <div class="form-group">
                <label class="form-label">Max lessons per stream/day</label>
                <input type="number" name="max_lessons_per_stream_per_day" class="form-control" value="{{ $generationSettings->max_lessons_per_stream_per_day ?? 6 }}" min="1" max="12">
            </div>

            <div class="form-group">
                <label class="form-label">Preferred scheduling strategy</label>
                <select name="preferred_scheduling_strategy" class="form-control">
                    @foreach(['Balanced', 'Teacher First', 'Subject First'] as $strategy)
                        <option value="{{ $strategy }}" {{ ($generationSettings->preferred_scheduling_strategy ?? 'Balanced') === $strategy ? 'selected' : '' }}>{{ $strategy }}</option>
                    @endforeach
                </select>
            </div>

            <label class="form-check"><input type="checkbox" name="allow_double_lessons" value="1" {{ ($generationSettings->allow_double_lessons ?? true) ? 'checked' : '' }}> Allow double lessons</label>
            <label class="form-check"><input type="checkbox" name="practical_subjects_consecutive" value="1" {{ ($generationSettings->practical_subjects_consecutive ?? false) ? 'checked' : '' }}> Practical subjects consecutive</label>
            <label class="form-check"><input type="checkbox" name="core_subjects_distributed" value="1" {{ ($generationSettings->core_subjects_distributed ?? true) ? 'checked' : '' }}> Distribute core subjects</label>
            <label class="form-check"><input type="checkbox" name="enforce_teacher_availability" value="1" {{ ($generationSettings->enforce_teacher_availability ?? true) ? 'checked' : '' }}> Enforce teacher availability</label>
            <label class="form-check"><input type="checkbox" name="enforce_room_availability" value="1" {{ ($generationSettings->enforce_room_availability ?? true) ? 'checked' : '' }}> Enforce room availability</label>
            <label class="form-check"><input type="checkbox" name="conflict_free_generation" value="1" {{ ($generationSettings->conflict_free_generation ?? true) ? 'checked' : '' }}> Conflict-free generation</label>
        </div>

        <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <div>
                <strong>Ready data:</strong>
                {{ $streams->count() }} streams,
                {{ $teachers->count() }} teachers,
                {{ $subjects->count() }} subjects,
                {{ $classrooms->count() }} classrooms,
                {{ $rooms->count() }} rooms
            </div>

            <button type="submit" class="btn btn-primary" id="generate-btn">
                <i class="bi bi-cpu"></i> Generate Timetable
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function loadReadiness() {
    const panel = document.getElementById('readiness-panel');
    panel.innerHTML = '<div style="text-align:center;color:var(--text-muted);padding:20px"><i class="bi bi-hourglass-split" style="font-size:2rem"></i><p>Checking readiness…</p></div>';

    fetch('{{ route("timetable.readiness") }}', {
        headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'}
    })
    .then(r => r.json())
    .then(data => {
        const issues = data.issues || [];
        const required = data.required_lessons || 0;
        const available = data.available_teaching_slots || 0;
        const allocCount = data.teacher_subject_allocations || 0;

        let statusColor = '#16a34a';
        let statusIcon  = '✅';
        let statusLabel = 'Ready to generate';

        if (allocCount === 0) {
            statusColor = '#dc2626'; statusIcon = '❌'; statusLabel = 'No allocations found';
        } else if (issues.length > 0) {
            statusColor = '#d97706'; statusIcon = '⚠'; statusLabel = issues.length + ' issue(s) detected';
        } else if (required > available) {
            statusColor = '#d97706'; statusIcon = '⚠'; statusLabel = 'More lessons than slots';
        }

        let html = `
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-bottom:16px">
            <div style="background:#f8fafc;border-radius:8px;padding:14px;text-align:center">
                <div style="font-size:0.78rem;color:var(--text-muted)">Allocations</div>
                <div style="font-size:1.8rem;font-weight:700">${allocCount}</div>
            </div>
            <div style="background:#f8fafc;border-radius:8px;padding:14px;text-align:center">
                <div style="font-size:0.78rem;color:var(--text-muted)">Required Lessons/wk</div>
                <div style="font-size:1.8rem;font-weight:700">${required}</div>
            </div>
            <div style="background:#f8fafc;border-radius:8px;padding:14px;text-align:center">
                <div style="font-size:0.78rem;color:var(--text-muted)">Available Slots</div>
                <div style="font-size:1.8rem;font-weight:700">${available}</div>
            </div>
            <div style="background:#f8fafc;border-radius:8px;padding:14px;text-align:center">
                <div style="font-size:0.78rem;color:var(--text-muted)">Teachers</div>
                <div style="font-size:1.8rem;font-weight:700">${data.teachers || 0}</div>
            </div>
        </div>
        <div style="padding:12px;background:${statusColor}10;border-left:4px solid ${statusColor};border-radius:4px;margin-bottom:12px">
            <strong style="color:${statusColor}">${statusIcon} ${statusLabel}</strong>
        </div>`;

        if (issues.length > 0) {
            html += `<details style="margin-top:8px"><summary style="cursor:pointer;font-weight:600;color:#d97706">⚠ ${issues.length} issue(s) — click to expand</summary><ul style="margin:8px 0 0 16px">`;
            issues.forEach(i => { html += `<li style="font-size:0.85rem;margin-bottom:4px">${i}</li>`; });
            html += '</ul></details>';
        }

        // Streams breakdown
        const reqs = data.requirements || {};
        const streamKeys = Object.keys(reqs);
        if (streamKeys.length > 0) {
            html += `<details style="margin-top:12px"><summary style="cursor:pointer;font-weight:600">📋 Stream breakdown (${streamKeys.length} streams scheduled)</summary>
            <table style="width:100%;font-size:0.82rem;margin-top:8px;border-collapse:collapse">
                <thead><tr style="background:#f8fafc"><th style="padding:6px;text-align:left">Stream</th><th style="padding:6px;text-align:left">Class</th><th style="padding:6px">Subjects</th><th style="padding:6px">Lessons/wk</th></tr></thead><tbody>`;
            streamKeys.forEach(sid => {
                const streamReqs = reqs[sid];
                const streamName = streamReqs[0].stream_name;
                const className  = streamReqs[0].class_name;
                const total = streamReqs.reduce((s, r) => s + r.required_lessons, 0);
                html += `<tr style="border-bottom:1px solid #e2e8f0">
                    <td style="padding:6px"><strong>${streamName}</strong></td>
                    <td style="padding:6px">${className}</td>
                    <td style="padding:6px;text-align:center">${streamReqs.length}</td>
                    <td style="padding:6px;text-align:center">${total}</td>
                </tr>`;
            });
            html += '</tbody></table></details>';
        }

        panel.innerHTML = html;
    })
    .catch(err => {
        panel.innerHTML = '<div class="alert alert-danger">Failed to load readiness report: ' + err.message + '</div>';
    });
}

document.addEventListener('DOMContentLoaded', loadReadiness);
</script>
@endpush
@endsection
