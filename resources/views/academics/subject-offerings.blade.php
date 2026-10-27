@extends('layouts.app')
@section('title', 'Class & Stream Subject Offerings')
@section('breadcrumb', 'Academics › Subject Offerings')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>📚 Class & Stream Subject Offerings</h1>
        <p>Define which learning areas and subjects are offered to each Grade, Stream, and Pathway before allocating teachers</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('academics.teacher-subjects') }}" class="btn btn-outline">
            <i class="bi bi-person-check"></i> Teacher Allocations
        </a>
        <a href="{{ route('academics.subjects') }}" class="btn btn-outline">
            <i class="bi bi-journals"></i> Master Subjects
        </a>
        <button class="btn btn-primary" onclick="document.getElementById('modal-offering').style.display='flex'">
            <i class="bi bi-plus-circle"></i> Offer Subject to Class/Stream
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->has('duplicate'))
    <div class="alert alert-danger" style="border-left:4px solid #dc2626;">
        <strong>⛔ Duplicate Offering:</strong> {{ $errors->first('duplicate') }}
    </div>
@elseif($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

<!-- Summary Statistics -->
<div class="stats-grid stats-grid-4" style="margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-label">Total Offerings</div>
        <div class="stat-value">{{ $totalOfferings }}</div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-label">Allocated to Teachers</div>
        <div class="stat-value">{{ $allocatedCount }}</div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-label">Pending Teacher Allocation</div>
        <div class="stat-value">{{ $unallocatedCount }}</div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-label">Classes Covered</div>
        <div class="stat-value">{{ $classesCovered }}</div>
    </div>
</div>

<!-- Filters Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px">
        <form method="GET" action="{{ route('academics.subject-offerings') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:180px">
                <label class="form-label" style="font-size:0.8rem">Filter by Class</label>
                <select name="class_id" class="form-control" onchange="this.form.submit()">
                    <option value="">— All Classes —</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->curriculum }})
                        </option>
                    @endforeach
                </select>
            </div>
            @if($selectedClassId && $classes->firstWhere('id', $selectedClassId)?->streams->isNotEmpty())
            <div style="flex:1;min-width:180px">
                <label class="form-label" style="font-size:0.8rem">Filter by Stream</label>
                <select name="stream_id" class="form-control" onchange="this.form.submit()">
                    <option value="">— All Streams —</option>
                    @foreach($classes->firstWhere('id', $selectedClassId)->streams as $st)
                        <option value="{{ $st->id }}" {{ $selectedStreamId == $st->id ? 'selected' : '' }}>
                            {{ $st->name }} ({{ $st->pathway ?? 'general' }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div style="flex:1;min-width:180px">
                <label class="form-label" style="font-size:0.8rem">Academic Year</label>
                <select name="academic_year_id" class="form-control" onchange="this.form.submit()">
                    <option value="">— All Academic Years —</option>
                    @foreach($academicYears as $yr)
                        <option value="{{ $yr->id }}" {{ $selectedYearId == $yr->id ? 'selected' : '' }}>
                            {{ $yr->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if($selectedClassId || $selectedStreamId || $selectedYearId)
            <div>
                <a href="{{ route('academics.subject-offerings') }}" class="btn btn-outline" style="white-space:nowrap">
                    <i class="bi bi-x-circle"></i> Clear Filters
                </a>
            </div>
            @endif
        </form>
    </div>
</div>

<!-- Active Subject Offerings Table -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-journal-check"></i> Configured Class & Stream Subject Offerings</h5>
        <span class="badge badge-info">{{ $offerings->count() }} active offerings</span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Class / Grade</th>
                    <th>Stream</th>
                    <th>Pathway</th>
                    <th>Subject Code & Name</th>
                    <th>Curriculum</th>
                    <th>Allocated Teacher(s)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($offerings as $idx => $offering)
                @php
                    $activeTeachers = $offering->allocations->where('is_active', true);
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>
                        <strong style="color:var(--primary);font-size:0.95rem">{{ $offering->schoolClass?->name ?? '—' }}</strong>
                    </td>
                    <td>
                        @if($offering->stream)
                            <strong>{{ $offering->stream->name }}</strong>
                        @else
                            <span class="badge badge-gray" style="font-size:0.75rem">All Streams (Class-wide)</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $offering->pathway === 'STEM' ? 'primary' : ($offering->pathway === 'Arts & Sports' ? 'warning' : ($offering->pathway === 'Social Sciences' ? 'info' : 'gray')) }}" style="font-size:0.8rem">
                            {{ $offering->pathway ?? 'general' }}
                        </span>
                    </td>
                    <td>
                        <strong>{{ $offering->subject?->name ?? '—' }}</strong>
                        @if($offering->subject?->code)
                            <code style="font-size:0.75rem;margin-left:4px">[{{ $offering->subject->code }}]</code>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $offering->subject?->curriculum === 'CBC' ? 'primary' : 'teal' }}">
                            {{ $offering->subject?->curriculum ?? '—' }}
                        </span>
                    </td>
                    <td>
                        @if($activeTeachers->isNotEmpty())
                            @foreach($activeTeachers as $alloc)
                                <div style="display:inline-flex;align-items:center;gap:4px;margin-right:6px">
                                    <span class="badge badge-success" style="font-size:0.75rem">
                                        👤 {{ $alloc->teacher?->user?->name ?? 'Teacher' }}
                                    </span>
                                </div>
                            @endforeach
                        @else
                            <a href="{{ route('academics.teacher-subjects') }}" class="badge badge-outline" style="border:1px dashed #e8922a;color:#e8922a;text-decoration:none">
                                + Allocate Teacher
                            </a>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('academics.subject-offerings.destroy', $offering->id) }}" method="POST"
                              onsubmit="return confirm('Remove this subject offering? If exam marks exist, it will be safely deactivated to preserve student history.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#dc2626;" title="Remove Offering">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="no-data">
                        No subject offerings configured yet. Click "Offer Subject to Class/Stream" above to assign subjects to classes!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Offer Subject to Class/Stream -->
<div id="modal-offering"
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
                    padding:14px 20px;">

            <h5 style="margin:0;font-size:1.05rem;">
                <i class="bi bi-journal-plus"></i>
                Offer Subject to Class / Stream
            </h5>

            <button type="button"
                    onclick="document.getElementById('modal-offering').style.display='none'"
                    style="background:none;border:none;font-size:1.5rem;line-height:1;cursor:pointer;">
                &times;
            </button>
        </div>

        <form action="{{ route('academics.subject-offerings.store') }}" method="POST">
            @csrf

            <div class="card-body" style="padding:20px;">
                <div class="row g-3" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">

                    <!-- Target Class -->
                    <div style="grid-column: span 1">
                        <label class="form-label">Grade / Class *</label>
                        <select name="school_class_id" id="offering_class_id" class="form-control" required
                                onchange="onOfferingClassChange(this.value)">
                            <option value="">— Select Class —</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->curriculum }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Target Stream -->
                    <div style="grid-column: span 1">
                        <label class="form-label">Stream <small style="color:var(--text-muted)">(optional)</small></label>
                        <select name="stream_id" id="offering_stream_id" class="form-control"
                                onchange="onOfferingStreamChange(this)">
                            <option value="">All Streams (Class-wide)</option>
                        </select>
                    </div>

                    <!-- Academic Pathway -->
                    <div style="grid-column: span 1">
                        <label class="form-label">Academic Pathway *</label>
                        <select name="pathway" id="offering_pathway" class="form-control" required>
                            <option value="general">General (Standard / All)</option>
                            <option value="STEM">STEM (Sciences / Tech / Engineering / Math)</option>
                            <option value="Social Sciences">Social Sciences & Humanities</option>
                            <option value="Arts & Sports">Arts & Sports Science</option>
                        </select>
                        <small style="color:var(--text-muted);font-size:0.75rem">Auto-synced from stream or selectable</small>
                    </div>

                    <!-- Master Subject -->
                    <div style="grid-column: span 1">
                        <label class="form-label">Master Subject *</label>
                        <select name="subject_id" id="offering_subject_id" class="form-control" required>
                            <option value="">— Select Subject —</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}" data-curriculum="{{ $sub->curriculum }}" data-pathway="{{ $sub->pathway }}">
                                    {{ $sub->name }} ({{ $sub->code }}) — {{ $sub->pathway ?? 'general' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Academic Year -->
                    <div style="grid-column: span 1">
                        <label class="form-label">Academic Year <small style="color:var(--text-muted)">(optional)</small></label>
                        <select name="academic_year_id" id="offering_year_id" class="form-control"
                                onchange="onOfferingYearChange(this.value)">
                            <option value="">— All Academic Years —</option>
                            @foreach($academicYears as $yr)
                                <option value="{{ $yr->id }}">{{ $yr->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Term -->
                    <div style="grid-column: span 1">
                        <label class="form-label">Term <small style="color:var(--text-muted)">(optional)</small></label>
                        <select name="term_id" id="offering_term_id" class="form-control">
                            <option value="">— All Terms —</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="card-footer"
                 style="display:flex;
                        justify-content:flex-end;
                        gap:10px;
                        padding:14px 20px;">

                <button type="button"
                        class="btn btn-outline"
                        onclick="document.getElementById('modal-offering').style.display='none'">
                    Cancel
                </button>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Subject Offering
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const offeringStreamsData = @json($classes->mapWithKeys(fn($c) => [$c->id => $c->streams->map(fn($s) => ['id' => $s->id, 'name' => $s->name, 'pathway' => $s->pathway ?? 'general'])]));
    const offeringTermsData   = @json(($academicYears ?? collect())->mapWithKeys(fn($y) => [$y->id => $y->terms->map(fn($t) => ['id' => $t->id, 'name' => $t->name])]));

    function onOfferingClassChange(classId) {
        const streamSelect = document.getElementById('offering_stream_id');
        if (!streamSelect) return;
        streamSelect.innerHTML = '<option value="">All Streams (Class-wide)</option>';
        if (!classId || !offeringStreamsData[classId]) return;

        offeringStreamsData[classId].forEach(st => {
            const opt = document.createElement('option');
            opt.value = st.id;
            opt.textContent = `${st.name} (${st.pathway})`;
            opt.setAttribute('data-pathway', st.pathway);
            streamSelect.appendChild(opt);
        });
    }

    function onOfferingStreamChange(selectElem) {
        const selOption = selectElem.options[selectElem.selectedIndex];
        if (selOption && selOption.getAttribute('data-pathway')) {
            const pw = selOption.getAttribute('data-pathway');
            const pwSelect = document.getElementById('offering_pathway');
            if (pwSelect && pw && pw !== 'general') {
                pwSelect.value = pw;
            }
        }
    }

    function onOfferingYearChange(yearId) {
        const termSelect = document.getElementById('offering_term_id');
        if (!termSelect) return;
        termSelect.innerHTML = '<option value="">— All Terms —</option>';
        if (!yearId || !offeringTermsData[yearId]) return;

        offeringTermsData[yearId].forEach(term => {
            const opt = document.createElement('option');
            opt.value = term.id;
            opt.textContent = term.name;
            termSelect.appendChild(opt);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any())
            document.getElementById('modal-offering').style.display = 'flex';
        @endif
    });
</script>
@endsection
