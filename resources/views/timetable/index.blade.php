@extends('layouts.app')
@section('title', 'Timetable Generator')
@section('breadcrumb', 'Academics › Timetable')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📅 Intelligent Timetable Engine</h1>
        <p>Collision-free lesson scheduling for teachers, streams, and specialized rooms</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <button class="btn btn-primary" type="button" data-toggle="settings-modal">
            <i class="bi bi-sliders"></i> Generation Settings
        </button>
        <form action="{{ route('timetable.generate') }}" method="POST" onsubmit="return confirm('Generate automatic timetable? This will schedule all class subjects without collisions.')">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-cpu"></i> Auto-Generate Timetable
            </button>
        </form>
        @if($activeTimetable)
        <form action="{{ route('timetable.repair', ['timetable_id' => $activeTimetable->id]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline">
                <i class="bi bi-wrench"></i> Repair Timetable
            </button>
        </form>
        @endif
        @if($selectedStream)
        <a href="{{ route('timetable.print', ['timetable_id' => $activeTimetable?->id ?? 1, 'stream_id' => $selectedStream->id]) }}" target="_blank" class="btn btn-outline">
            <i class="bi bi-printer"></i> Print Schedule
        </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

{{-- ── Timetable Management Panel ── --}}
@if($timetables->count() > 0)
<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-collection"></i> Timetables in System</h5>
        <span style="font-size:0.8rem;color:var(--text-muted)">Active timetable is highlighted</span>
    </div>
    <div class="table-responsive">
        <table style="font-size:0.88rem">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Term</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Allocations</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($timetables as $tt)
                <tr style="{{ $activeTimetable?->id === $tt->id ? 'background:#f0fdf4;font-weight:600' : '' }}">
                    <td>
                        {{ $tt->name }}
                        @if($activeTimetable?->id === $tt->id)
                            <span class="badge badge-success" style="margin-left:4px">Active</span>
                        @endif
                    </td>
                    <td>{{ $tt->term?->name ?? '—' }}</td>
                    <td>{{ $tt->academicYear?->name ?? '—' }}</td>
                    <td>
                        @php $statusColors = ['published'=>'success','draft'=>'warning','archived'=>'gray']; @endphp
                        <span class="badge badge-{{ $statusColors[$tt->status] ?? 'gray' }}">{{ ucfirst($tt->status) }}</span>
                    </td>
                    <td>{{ $tt->allocations()->count() }}</td>
                    <td style="display:flex;gap:6px;flex-wrap:wrap">
                        @if($tt->status !== 'published')
                        <form action="{{ route('timetable.publish', $tt) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm" style="color:#16a34a;border-color:#16a34a;">
                                <i class="bi bi-check-circle"></i> Publish
                            </button>
                        </form>
                        @endif
                        @if($tt->status !== 'archived')
                        <form action="{{ route('timetable.archive', $tt) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm">
                                <i class="bi bi-archive"></i> Archive
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('timetable.destroy', $tt) }}" method="POST"
                              onsubmit="return confirm('Permanently delete timetable and all its slots and allocations?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline btn-sm" style="color:#dc2626;border-color:#dc2626;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="stats-grid stats-grid-6" style="margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-label">Teacher Conflicts</div>
        <div class="stat-value">{{ $health['teacher_conflicts'] ?? 0 }}</div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-label">Stream Conflicts</div>
        <div class="stat-value">{{ $health['stream_conflicts'] ?? 0 }}</div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-label">Room Conflicts</div>
        <div class="stat-value">{{ $health['room_conflicts'] ?? 0 }}</div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-label">Unscheduled</div>
        <div class="stat-value">{{ $health['unscheduled_lessons'] ?? 0 }}</div>
    </div>
    <div class="stat-card stat-gray">
        <div class="stat-label">Teacher Overload</div>
        <div class="stat-value">{{ $health['teacher_overload'] ?? 0 }}</div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-label">Timetable Health</div>
        @php $healthStatusColor = $health['status_color'] ?? 'green'; @endphp
        <div class="stat-value" style="color:{{ $healthStatusColor === 'green' ? '#16a34a' : ($healthStatusColor === 'yellow' ? '#d97706' : '#dc2626') }};">{{ $health['score'] ?? 100 }}%</div>
    </div>
</div>

<!-- Selector Filter Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px">
        <form method="GET" action="{{ route('timetable.index') }}" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:180px">
                <label class="form-label" style="font-size:0.8rem">View</label>
                <select name="view" class="form-control" onchange="this.form.submit()">
                    <option value="school" {{ ($viewType ?? 'school') === 'school' ? 'selected' : '' }}>School Overview</option>
                    <option value="stream" {{ ($viewType ?? 'school') === 'stream' ? 'selected' : '' }}>Single Stream</option>
                    <option value="teacher" {{ ($viewType ?? 'school') === 'teacher' ? 'selected' : '' }}>Single Teacher</option>
                </select>
            </div>
            <div style="flex:1;min-width:200px">
                <label class="form-label" style="font-size:0.8rem">Class</label>
                <select name="class_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ ($selectedClass?->id == $class->id) ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1;min-width:220px">
                <label class="form-label" style="font-size:0.8rem">Stream</label>
                <select name="stream_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Streams</option>
                    @foreach($streams as $st)
                        <option value="{{ $st->id }}" {{ ($selectedStream?->id == $st->id) ? 'selected' : '' }}>
                            {{ $st->schoolClass?->name }} — Stream {{ $st->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1;min-width:220px">
                <label class="form-label" style="font-size:0.8rem">Teacher</label>
                <select name="teacher_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Select Teacher --</option>
                    @foreach($teachers as $th)
                        <option value="{{ $th->id }}" {{ ($selectedTeacher?->id == $th->id) ? 'selected' : '' }}>
                            {{ $th->user?->name }} ({{ $th->tsc_number ?? 'BOM' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1;min-width:140px">
                <label class="form-label" style="font-size:0.8rem">Day</label>
                <select name="day" class="form-control" onchange="this.form.submit()">
                    <option value="">All Week</option>
                    @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $day)
                        <option value="{{ $day }}" {{ request('day') === $day ? 'selected' : '' }}>{{ $day }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-outline">Filter</button>
        </form>
    </div>
</div>

<!-- Timetable Grid -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5>
            <i class="bi bi-table"></i>
            @if(($viewType ?? 'school') === 'teacher' && request('teacher_id') && $selectedTeacher)
                Schedule for Teacher: <strong>{{ $selectedTeacher->user?->name }}</strong>
            @elseif(($viewType ?? 'school') === 'stream' && request('stream_id') && $selectedStream)
                Weekly Timetable: <strong>{{ $selectedStream?->schoolClass?->name }} ({{ $selectedStream?->name }})</strong>
            @else
                School Overview: <strong>All Classes</strong>
            @endif
        </h5>
        <span class="badge badge-success">Published Schedule</span>
    </div>

    @php
        $days = $days ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        if (empty($periods)) {
            $timetableSlots = $activeTimetable ? $activeTimetable->slots->where('day_of_week', 'Monday')->sortBy('period_number') : collect();
            if ($timetableSlots->isEmpty() && $activeTimetable) {
                $timetableSlots = $activeTimetable->slots->groupBy('period_number')->map->first()->sortBy('period_number');
            }

            $periods = [];
            if ($timetableSlots->isNotEmpty()) {
                foreach ($timetableSlots as $slot) {
                    $timeLabel = \Carbon\Carbon::parse($slot->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($slot->end_time)->format('H:i');
                    $periods[$slot->period_number] = [
                        'time' => $timeLabel,
                        'name' => $slot->is_break ? ($slot->break_label ?: 'Break') : 'Lesson ' . $slot->period_number,
                        'break' => (bool)$slot->is_break,
                    ];
                }
            } else {
                $periods = [
                    1 => ['time' => '08:00 - 08:45', 'name' => 'Lesson 1', 'break' => false],
                    2 => ['time' => '08:45 - 09:30', 'name' => 'Lesson 2', 'break' => false],
                    3 => ['time' => '09:30 - 10:15', 'name' => 'Lesson 3', 'break' => false],
                    4 => ['time' => '10:15 - 10:45', 'name' => 'Tea Break', 'break' => true],
                    5 => ['time' => '10:45 - 11:30', 'name' => 'Lesson 4', 'break' => false],
                    6 => ['time' => '11:30 - 12:15', 'name' => 'Lesson 5', 'break' => false],
                    7 => ['time' => '12:15 - 13:00', 'name' => 'Lesson 6', 'break' => false],
                    8 => ['time' => '13:00 - 14:00', 'name' => 'Lunch Break', 'break' => true],
                    9 => ['time' => '14:00 - 14:45', 'name' => 'Lesson 7', 'break' => false],
                    10 => ['time' => '14:45 - 15:30', 'name' => 'Lesson 8', 'break' => false],
                    11 => ['time' => '15:30 - 16:30', 'name' => 'Games & Clubs', 'break' => true],
                ];
            }
        }

        $matrix = (($viewType ?? 'school') === 'teacher' && $selectedTeacher) ? $teacherMatrix : (($viewType ?? 'school') === 'stream' && $selectedStream ? $streamMatrix : $schoolMatrix);
        $schoolClassGroups = [];

        if (($viewType ?? 'school') === 'school') {
            foreach ($classes as $class) {
                $schoolClassGroups[$class->name] = ['days' => []];
            }

            foreach ($days as $day) {
                foreach ($periods as $pNum => $pMeta) {
                    if (!empty($pMeta['break'])) {
                        continue;
                    }

                    $entries = $schoolMatrix[$day][$pNum] ?? [];
                    foreach ($entries as $entry) {
                        $className = trim((string)($entry['class_name'] ?? ''));
                        if ($className !== '') {
                            $schoolClassGroups[$className]['days'][$day][$pNum][] = $entry;
                        }
                    }
                }
            }

            if ($selectedClass) {
                $schoolClassGroups = array_intersect_key($schoolClassGroups, [$selectedClass->name => true]);
            }
        }
    @endphp

    @if(($allocationSummary['teacher_subject_allocations'] ?? 0) === 0)
        <div class="alert alert-warning" style="margin:16px 0;">
            <strong>No teacher-subject allocations were found for the current school.</strong>
            The timetable engine requires real workload rows from the teacher_subject_allocations table before it can schedule lessons.
        </div>
    @elseif($activeTimetable && $activeTimetable->allocations()->count() === 0)
        <div class="alert alert-info" style="margin:16px 0;">
            <strong>Valid teaching workload detected.</strong>
            This school has {{ $allocationSummary['teacher_subject_allocations'] ?? 0 }} teacher-subject allocations and {{ $allocationSummary['required_weekly_lessons'] ?? 0 }} required weekly lessons.
            The timetable has not been generated yet, so no timetable output rows exist until the draft is created.
        </div>
    @endif

    @if(($viewType ?? 'school') === 'school')
        @foreach($schoolClassGroups as $className => $classSchedule)
            <div class="card" style="margin-top:20px; overflow:hidden;">
                <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <h5><i class="bi bi-building"></i> Class: <strong>{{ $className }}</strong></h5>
                    @php
                        $lessonCount = 0;
                        foreach ($days as $d) {
                            foreach ($periods as $pn => $pm) {
                                if (empty($pm['break']) && !empty($classSchedule['days'][$d][$pn])) {
                                    $lessonCount += count($classSchedule['days'][$d][$pn]);
                                }
                            }
                        }
                    @endphp
                    <span class="badge badge-{{ $lessonCount > 0 ? 'success' : 'secondary' }}">
                        {{ $lessonCount > 0 ? $lessonCount . ' ' . Str::plural('lesson', $lessonCount) . ' scheduled' : 'No lessons scheduled' }}
                    </span>
                </div>
                <div class="table-responsive">
                    <table style="border-collapse:collapse;width:100%;text-align:center">
                        <thead>
                            <tr style="background:#f8fafc">
                                <th style="width:120px;text-align:left;padding:12px">Day</th>
                                @foreach($periods as $pNum => $pMeta)
                                    <th style="padding:10px 6px;font-size:0.75rem;border:1px solid #e2e8f0;background:{{ !empty($pMeta['break']) ? '#f1f5f9' : '#fff' }}">
                                        <div style="font-weight:700">{{ $pMeta['name'] }}</div>
                                        <div style="font-weight:400;color:var(--text-muted);font-size:0.7rem">{{ $pMeta['time'] }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($days as $day)
                                <tr>
                                    <td style="font-weight:700;background:#f8fafc;border:1px solid #e2e8f0;text-align:left;padding:12px">{{ $day }}</td>
                                    @foreach($periods as $pNum => $pMeta)
                                        @if(!empty($pMeta['break']))
                                            <td style="background:#f8fafc;border:1px solid #e2e8f0;font-size:0.75rem;font-weight:600;color:var(--text-muted);letter-spacing:1px">
                                                {{ $pMeta['name'] }}
                                            </td>
                                        @else
                                            @php
                                                $classEntries = $classSchedule['days'][$day][$pNum] ?? [];
                                            @endphp
                                            <td style="border:1px solid #e2e8f0;padding:8px 4px;min-height:55px;vertical-align:top">
                                                @if(!empty($classEntries))
                                                    @foreach($classEntries as $entry)
                                                        @php
                                                            $subjectLabel = trim((string)($entry['subject_name'] ?? '')) ?: trim((string)($entry['subject_code'] ?? ''));
                                                            $teacherLabel = trim((string)($entry['teacher_name'] ?? ''));
                                                            $streamLabel = trim((string)($entry['stream_name'] ?? ''));
                                                            $roomLabel = trim((string)($entry['room_name'] ?? ($entry['classroom_name'] ?? '')));
                                                        @endphp
                                                        @if($subjectLabel !== '')
                                                            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:6px 6px;margin-bottom:6px;text-align:left">
                                                                <div style="font-weight:700;font-size:0.82rem;color:#1e40af">{{ $subjectLabel }}</div>
                                                                @if($teacherLabel !== '')
                                                                    <div style="font-size:0.74rem;color:#1e293b;font-weight:600">{{ $teacherLabel }}</div>
                                                                @endif
                                                                @if($streamLabel !== '' || $roomLabel !== '')
                                                                    <div style="font-size:0.68rem;color:#64748b">
                                                                        {{ $streamLabel !== '' ? 'Stream: ' . $streamLabel : '' }}
                                                                        {{ ($streamLabel !== '' && $roomLabel !== '') ? ' · ' : '' }}
                                                                        {{ $roomLabel !== '' ? $roomLabel : '' }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <span style="color:#cbd5e1;font-size:0.75rem">—</span>
                                                @endif
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @else
        <div class="table-responsive">
            <table style="border-collapse:collapse;width:100%;text-align:center">
                <thead>
                    <tr style="background:#f8fafc">
                        <th style="width:100px;text-align:left;padding:12px">Day</th>
                        @foreach($periods as $pNum => $pMeta)
                            <th style="padding:10px 6px;font-size:0.75rem;border:1px solid #e2e8f0;background:{{ !empty($pMeta['break']) ? '#f1f5f9' : '#fff' }}">
                                <div style="font-weight:700">{{ $pMeta['name'] }}</div>
                                <div style="font-weight:400;color:var(--text-muted);font-size:0.7rem">{{ $pMeta['time'] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($days as $day)
                    <tr>
                        <td style="font-weight:700;background:#f8fafc;border:1px solid #e2e8f0;text-align:left;padding:12px">
                            {{ $day }}
                        </td>
                        @foreach($periods as $pNum => $pMeta)
                            @if(!empty($pMeta['break']))
                                <td style="background:#f8fafc;border:1px solid #e2e8f0;font-size:0.75rem;font-weight:600;color:var(--text-muted);letter-spacing:1px">
                                    {{ $pMeta['name'] }}
                                </td>
                            @else
                                @php
                                    $slot = $matrix[$day][$pNum] ?? null;
                                    $slotEntries = is_array($slot) && array_key_exists('subject_name', $slot) ? [$slot] : ($slot ?? []);
                                @endphp
                                <td style="border:1px solid #e2e8f0;padding:8px 4px;min-height:55px;vertical-align:top">
                                    @if(!empty($slotEntries))
                                        @foreach($slotEntries as $entry)
                                            @php
                                                $subjectLabel = trim((string)($entry['subject_name'] ?? '')) ?: trim((string)($entry['subject_code'] ?? ''));
                                                $teacherLabel = trim((string)($entry['teacher_name'] ?? ''));
                                                $classLabel = trim((string)($entry['class_name'] ?? ''));
                                                $streamLabel = trim((string)($entry['stream_name'] ?? ''));
                                                $roomLabel = trim((string)($entry['room_name'] ?? ($entry['classroom_name'] ?? '')));
                                            @endphp
                                            @if($subjectLabel !== '')
                                                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;padding:6px 6px;margin-bottom:6px;text-align:left">
                                                    <div style="font-weight:700;font-size:0.82rem;color:#1e40af">{{ $subjectLabel }}</div>
                                                    @if($teacherLabel !== '')
                                                        <div style="font-size:0.74rem;color:#1e293b;font-weight:600">{{ $teacherLabel }}</div>
                                                    @endif
                                                    @if($classLabel !== '' || $streamLabel !== '' || $roomLabel !== '')
                                                        <div style="font-size:0.68rem;color:#64748b">
                                                            {{ $classLabel }}{{ $streamLabel !== '' ? ' (' . $streamLabel . ')' : '' }}
                                                            {{ (($classLabel !== '' || $streamLabel !== '') && $roomLabel !== '') ? ' · ' : '' }}
                                                            {{ $roomLabel }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span style="color:#cbd5e1;font-size:0.75rem">—</span>
                                            @endif
                                        @endforeach
                                    @else
                                        <span style="color:#cbd5e1;font-size:0.75rem">—</span>
                                    @endif
                                </td>
                            @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
<!-- Generation Settings Modal -->
<div id="settings-modal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="card" style="width:min(720px,92vw); border-radius:16px; background:#fff;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h5><i class="bi bi-sliders"></i> Timetable Generation Settings</h5>
            <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('settings-modal').style.display='none'">Close</button>
        </div>
        <form action="{{ route('timetable.settings.save') }}" method="POST">
            @csrf
            <input type="hidden" name="timetable_id" value="{{ $activeTimetable?->id ?? 1 }}">
            <div class="card-body" style="display:grid; grid-template-columns:repeat(2,minmax(220px,1fr)); gap:16px;">
                <div class="form-group">
                    <label class="form-label">Max lessons per teacher/day</label>
                    <input type="number" name="max_lessons_per_teacher_per_day" class="form-control" value="{{ $generationSettings->max_lessons_per_teacher_per_day ?? 6 }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Max consecutive lessons/teacher</label>
                    <input type="number" name="max_consecutive_lessons_per_teacher" class="form-control" value="{{ $generationSettings->max_consecutive_lessons_per_teacher ?? 2 }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Max lessons per stream/day</label>
                    <input type="number" name="max_lessons_per_stream_per_day" class="form-control" value="{{ $generationSettings->max_lessons_per_stream_per_day ?? 6 }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Preferred strategy</label>
                    <select name="preferred_scheduling_strategy" class="form-control">
                        @foreach(['Balanced','Teacher First','Subject First'] as $strategy)
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
            <div class="card-footer" style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('settings-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('[data-toggle="settings-modal"]').forEach((btn) => {
        btn.addEventListener('click', function() {
            const modal = document.getElementById('settings-modal');
            if (modal) modal.style.display = 'flex';
        });
    });
</script>
@endpush

@endsection
