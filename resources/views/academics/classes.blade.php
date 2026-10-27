@extends('layouts.app')
@section('title', 'Classes & Streams')
@section('breadcrumb', 'Academics › Classes')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🏫 Classes & Streams Management</h1>
        <p>Configure classes, grade levels, streams, capacity and assign class teachers</p>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-primary" onclick="document.getElementById('modal-class').style.display='flex'">
            <i class="bi bi-plus-circle"></i> Add Class
        </button>
        <button class="btn btn-outline" onclick="document.getElementById('modal-stream').style.display='flex'">
            <i class="bi bi-diagram-2"></i> Add Stream
        </button>
    </div>
</div>

<div class="stats-grid stats-grid-4" style="margin-bottom:24px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📚</div>
        <div class="stat-body">
            <div class="stat-label">Total Classes</div>
            <div class="stat-value">{{ $classes->count() }}</div>
            <div class="stat-trend up"><i class="bi bi-mortarboard"></i> Levels active</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">🔀</div>
        <div class="stat-body">
            <div class="stat-label">Total Streams</div>
            <div class="stat-value">{{ $classes->sum(fn($c) => $c->streams->count()) }}</div>
            <div class="stat-trend up"><i class="bi bi-diagram-3"></i> Sub-divisions</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">👥</div>
        <div class="stat-body">
            <div class="stat-label">Total Enrolled</div>
            <div class="stat-value">{{ $classes->sum(fn($c) => $c->students->count()) }}</div>
            <div class="stat-trend up"><i class="bi bi-people"></i> Active students</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">🎓</div>
        <div class="stat-body">
            <div class="stat-label">CBC Classes</div>
            <div class="stat-value">{{ $classes->where('curriculum', 'CBC')->count() }}</div>
            <div class="stat-trend up"><i class="bi bi-check-all"></i> Competency Based</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-building"></i> Class Breakdown & Streams</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Class / Grade</th>
                    <th>Level</th>
                    <th>Curriculum</th>
                    <th>Streams</th>
                    <th>Total Students</th>
                    <th>Class Teachers</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $c)
                <tr>
                    <td>
                        <div style="font-weight:700;font-size:1rem;color:var(--primary)">{{ $c->name }}</div>
                        <span style="font-size:0.75rem;color:var(--text-muted)">Level {{ $c->numeric_level }}</span>
                    </td>
                    <td>
                        <span class="badge badge-gray">{{ ucwords(str_replace('_', ' ', $c->level)) }}</span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $c->curriculum === 'CBC' ? 'primary' : 'teal' }}">{{ $c->curriculum }}</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            @forelse($c->streams as $str)
                                <span class="badge badge-outline" style="border:1px solid #cbd5e1;padding:4px 8px;display:inline-flex;align-items:center;gap:4px">
                                    <strong>{{ $str->name }}</strong>
                                    <span class="badge badge-{{ $str->pathway === 'STEM' ? 'primary' : ($str->pathway === 'Arts & Sports' ? 'warning' : ($str->pathway === 'Social Sciences' ? 'info' : 'gray')) }}" style="font-size:0.65rem;padding:2px 5px">
                                        {{ $str->pathway ?? 'general' }}
                                    </span>
                                    <small style="color:var(--text-muted)">({{ $str->capacity }} cap)</small>
                                </span>
                            @empty
                                <span style="color:var(--text-muted);font-size:0.8rem">No streams added</span>
                            @endforelse
                        </div>
                    </td>
                    <td>
                        <strong style="font-family:'Outfit',sans-serif;font-size:1.05rem">{{ $c->students->count() }}</strong>
                    </td>
                    <td>
                        @php
                            $teachersList = $c->streams->map(fn($s) => $s->classTeacher?->name)->filter()->join(', ');
                        @endphp
                        <span style="font-size:0.85rem;color:var(--text-muted)">{{ $teachersList ?: 'Unassigned' }}</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('students.index', ['class_id' => $c->id]) }}" class="btn btn-outline btn-sm" title="View Students">
                                <i class="bi bi-people"></i>
                            </a>
                            <a href="{{ route('timetable.index', ['stream_id' => $c->streams->first()?->id]) }}" class="btn btn-outline btn-sm" title="Timetable">
                                <i class="bi bi-calendar3"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="no-data">No classes configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Class -->
<div id="modal-class" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:500px;background:#fff;border-radius:12px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1)">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-plus-circle"></i> Create New Class</h5>
            <button onclick="document.getElementById('modal-class').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('academics.classes.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Class Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Grade 7, Form 1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Curriculum *</label>
                    <select name="curriculum" class="form-control" required>
                        <option value="CBC">CBC (Competency Based Curriculum)</option>
                        <option value="8-4-4">8-4-4 System</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">School Level *</label>
                    <select name="level" class="form-control" required>
                        <option value="junior_school">Junior School (Grades 7 - 9)</option>
                        <option value="senior_school">Senior School (Grades 10 - 12)</option>
                        <option value="secondary_844">Secondary (Form 1 - Form 4)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Numeric Hierarchy Level *</label>
                    <input type="number" name="numeric_level" class="form-control" value="7" required>
                    <small style="color:var(--text-muted)">Used for auto-promotions (e.g. 7 advances to 8)</small>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-class').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Class</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Stream -->
<div id="modal-stream" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:500px;background:#fff;border-radius:12px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1)">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-diagram-2"></i> Add Class Stream</h5>
            <button onclick="document.getElementById('modal-stream').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('academics.streams.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Parent Class *</label>
                    <select name="school_class_id" class="form-control" required>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->curriculum }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Stream Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Amber, Blue, Green, East" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Academic Pathway *</label>
                    <select name="pathway" class="form-control" required>
                        <option value="general">General (Standard / All)</option>
                        <option value="STEM">STEM (Pure Sciences / Tech / Engineering / Math)</option>
                        <option value="Social Sciences">Social Sciences & Humanities</option>
                        <option value="Arts & Sports">Arts & Sports Science</option>
                    </select>
                    <small style="color:var(--text-muted)">Different streams in the same grade can take different pathways</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Student Capacity *</label>
                    <input type="number" name="capacity" class="form-control" value="45" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Class Teacher</label>
                    <select name="class_teacher_id" class="form-control">
                        <option value="">-- Select Teacher --</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->user_id }}">{{ $t->user?->name }} ({{ $t->tsc_number ?? 'Staff' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-stream').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Stream</button>
            </div>
        </form>
    </div>
</div>
@endsection
