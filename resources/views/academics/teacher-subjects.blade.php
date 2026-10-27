@extends('layouts.app')
@section('title', 'Teacher Subject Allocations')
@section('breadcrumb', 'Academics › Workload Allocations')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📑 Teacher Subject &amp; Workload Allocations</h1>
        <p>Assign educators to specific grade learning areas and stream periods</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="document.getElementById('modal-alloc').style.display='flex'">
            <i class="bi bi-plus-circle"></i> New Allocation
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->has('duplicate'))
    <div class="alert alert-danger" style="border-left:4px solid #dc2626;">
        <strong>⛔ Duplicate Allocation:</strong> {{ $errors->first('duplicate') }}
    </div>
@elseif($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

{{-- ── Allocation Summary Stats ── --}}
@php
    $validCount    = $allocations->filter(fn($a) => $a->teacher_id && $a->subject_id && $a->school_class_id && $a->lessons_per_week > 0)->count();
    $incompleteCount = $allocations->count() - $validCount;
    $totalLessons  = $allocations->sum('lessons_per_week');
    $classesCount  = $allocations->pluck('school_class_id')->filter()->unique()->count();
@endphp
<div class="stats-grid stats-grid-4" style="margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-label">Total Allocations</div>
        <div class="stat-value">{{ $allocations->count() }}</div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-label">Valid Allocations</div>
        <div class="stat-value">{{ $validCount }}</div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-label">Weekly Lessons</div>
        <div class="stat-value">{{ $totalLessons }}</div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-label">Classes Covered</div>
        <div class="stat-value">{{ $classesCount }}</div>
    </div>
</div>

@if($incompleteCount > 0)
    <div class="alert alert-warning" style="margin-bottom:16px;">
        <strong>⚠ {{ $incompleteCount }} incomplete allocation(s)</strong> — some entries are missing a teacher, subject, or lessons_per_week value.
        These will be <strong>skipped</strong> during timetable generation.
    </div>
@endif

@if(($inactiveCount ?? 0) > 0)
    <div class="alert alert-info" style="margin-bottom:16px;font-size:0.875rem;">
        <i class="bi bi-info-circle"></i>
        {{ $inactiveCount }} historical/inactive allocation record(s) exist (e.g. former duplicates marked inactive). They are hidden from this roster and do not affect timetable generation.
    </div>
@endif

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-journal-bookmark"></i> Active Workload Roster</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Teacher</th>
                    <th>Subject / Learning Area</th>
                    <th>Class &amp; Stream</th>
                    <th>Periods / Week</th>
                    <th>Curriculum</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allocations as $a)
                @php
                    $isValid = $a->teacher_id && $a->subject_id && $a->school_class_id && $a->lessons_per_week > 0;
                @endphp
                <tr style="{{ !$isValid ? 'opacity:0.65;background:#fef9c3;' : '' }}">
                    <td style="color:var(--text-muted);font-size:0.8rem">{{ $a->id }}</td>
                    <td>
                        <div style="font-weight:700">{{ $a->teacher?->user?->name ?? '⚠ Not assigned' }}</div>
                        <span style="font-size:0.75rem;color:var(--text-muted)">TSC: {{ $a->teacher?->tsc_number ?: 'BOM' }}</span>
                    </td>
                    <td>
                        <strong>{{ $a->subject?->name ?? '⚠ Missing' }}</strong>
                        <span class="badge badge-gray" style="margin-left:4px">{{ $a->subject?->code }}</span>
                    </td>
                    <td>
                        <span class="badge badge-primary">{{ $a->schoolClass?->name ?? '⚠ No class' }}</span>
                        @if($a->stream)
                            <span class="badge badge-outline" style="border:1px solid #cbd5e1">{{ $a->stream->name }}</span>
                        @else
                            <span style="font-size:0.8rem;color:var(--text-muted)">(All Streams)</span>
                        @endif
                    </td>
                    <td>
                        <strong style="font-size:1rem">{{ $a->lessons_per_week ?: '⚠ 0' }}</strong>
                        @if($a->lessons_per_week > 0) <span style="font-size:0.75rem;color:var(--text-muted)">lessons/wk</span> @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $a->subject?->curriculum === 'CBC' ? 'primary' : 'teal' }}">
                            {{ $a->subject?->curriculum ?? '—' }}
                        </span>
                    </td>
                    <td>
                        @if($isValid)
                            <span class="badge badge-success">✓ Valid</span>
                        @else
                            <span class="badge badge-danger">⚠ Incomplete</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('academics.allocations.destroy', $a->id) }}" method="POST"
                              onsubmit="return confirm('Delete this allocation?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#dc2626;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="no-data">No subject allocations defined yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Allocate Teacher -->
<!-- Modal: Allocate Teacher -->
<div id="modal-alloc"
     style="display:none;
            position:fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background:rgba(0,0,0,0.6);
            z-index:999;
            align-items:center;
            justify-content:center;
            padding:20px;">

    <div class="card"
         style="width:100%;
                max-width:620px;
                max-height:90vh;
                overflow-y:auto;
                background:#fff;
                border-radius:12px;
                margin:auto;">

        <div class="card-header"
             style="display:flex;
                    justify-content:space-between;
                    align-items:center;
                    padding:12px 18px;">

            <h5 style="margin:0;font-size:1rem;">
                <i class="bi bi-person-check"></i>
                Assign Subject to Teacher
            </h5>

            <button type="button"
                    onclick="document.getElementById('modal-alloc').style.display='none'"
                    style="background:none;
                           border:none;
                           font-size:1.5rem;
                           cursor:pointer;
                           line-height:1;">
                &times;
            </button>
        </div>

        <form action="{{ route('academics.allocations.store') }}" method="POST">
            @csrf

            <div class="card-body" style="padding:18px;">

                <div class="row g-3">

                    <!-- Teacher -->
                    <div class="col-md-6">
                        <label class="form-label">
                            Teacher *
                        </label>

                        <select name="teacher_id"
                                class="form-control"
                                required>
                            <option value="">-- Select Teacher --</option>

                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">
                                    {{ $t->user?->name }}
                                    ({{ $t->tsc_number ?? 'BOM' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-6">
                        <label class="form-label">
                            Class *
                        </label>

                        <select name="school_class_id"
                                id="alloc_class_id"
                                class="form-control"
                                required
                                onchange="updateStreamsForClass(this.value)">

                            <option value="">-- Select Class --</option>

                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->name }} ({{ $c->curriculum }})
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <!-- Stream -->
                    <div class="col-md-6">
                        <label class="form-label">
                            Stream
                        </label>

                        <select name="stream_id"
                                id="alloc_stream_id"
                                class="form-control">

                            <option value="">
                                All Streams (Class-wide)
                            </option>

                        </select>

                        <small style="color:var(--text-muted);font-size:0.72rem;">
                            Select a specific stream or leave as
                            "All Streams".
                        </small>
                    </div>

                    <!-- Subject -->
                    <div class="col-md-6">
                        <label class="form-label">
                            Subject *
                        </label>

                        <select name="subject_id"
                                id="alloc_subject_id"
                                class="form-control"
                                required>

                            <option value="">
                                -- Select Subject --
                            </option>

                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}">
                                    {{ $s->name }}
                                    ({{ $s->code }} - {{ $s->curriculum }})
                                </option>
                            @endforeach

                        </select>
                    </div>

                    <!-- Academic Year -->
                    <div class="col-md-6">
                        <label class="form-label">Academic Year <small style="color:var(--text-muted)">(optional)</small></label>
                        <select name="academic_year_id" id="alloc_year_id" class="form-control"
                                onchange="updateTermsForYear(this.value)">
                            <option value="">— Any Year —</option>
                            @foreach($academicYears ?? [] as $yr)
                                <option value="{{ $yr->id }}">{{ $yr->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Term -->
                    <div class="col-md-6">
                        <label class="form-label">Term <small style="color:var(--text-muted)">(optional)</small></label>
                        <select name="term_id" id="alloc_term_id" class="form-control">
                            <option value="">— Any Term —</option>
                        </select>
                    </div>

                    <!-- Lessons Per Week -->
                    <div class="col-md-6">
                        <label class="form-label">
                            Lessons Per Week *
                        </label>

                        <input type="number"
                               name="lessons_per_week"
                               class="form-control"
                               value="5"
                               min="1"
                               max="10"
                               required>

                        <small style="color:var(--text-muted);font-size:0.72rem;">
                            Number of lessons per week.
                        </small>
                    </div>

                </div>
            </div>

            <div class="card-footer"
                 style="display:flex;
                        justify-content:flex-end;
                        gap:8px;
                        padding:12px 18px;">

                <button type="button"
                        class="btn btn-outline"
                        onclick="document.getElementById('modal-alloc').style.display='none'">
                    Cancel
                </button>

                <button type="submit"
                        class="btn btn-primary">
                    Save Allocation
                </button>

            </div>

        </form>

    </div>
</div>

<script>
    const classStreamsMap = @json($classes->mapWithKeys(fn($c) => [$c->id => $c->streams->map(fn($s) => ['id' => $s->id, 'name' => $s->name])]));
    const yearTermsMap   = @json(($academicYears ?? collect())->mapWithKeys(fn($y) => [$y->id => $y->terms->map(fn($t) => ['id' => $t->id, 'name' => $t->name])]));

    function updateStreamsForClass(classId) {
        const streamSelect = document.getElementById('alloc_stream_id');
        if (!streamSelect) return;
        streamSelect.innerHTML = '<option value="">All Streams (Class-wide)</option>';
        if (!classId || !classStreamsMap[classId]) return;

        classStreamsMap[classId].forEach(stream => {
            const opt = document.createElement('option');
            opt.value = stream.id;
            opt.textContent = stream.name;
            streamSelect.appendChild(opt);
        });
    }

    function updateTermsForYear(yearId) {
        const termSelect = document.getElementById('alloc_term_id');
        if (!termSelect) return;
        termSelect.innerHTML = '<option value="">— Any Term —</option>';
        if (!yearId || !yearTermsMap[yearId]) return;

        yearTermsMap[yearId].forEach(term => {
            const opt = document.createElement('option');
            opt.value = term.id;
            opt.textContent = term.name;
            termSelect.appendChild(opt);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const classSelect = document.getElementById('alloc_class_id');
        if (classSelect && classSelect.value) {
            updateStreamsForClass(classSelect.value);
        }

        // Auto-open modal if there were validation errors (including duplicate)
        @if($errors->any())
            document.getElementById('modal-alloc').style.display = 'flex';
        @endif
    });
</script>
@endsection

