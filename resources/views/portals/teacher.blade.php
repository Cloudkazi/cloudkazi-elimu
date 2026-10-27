@extends('layouts.app')
@section('title', 'Teacher Portal')
@section('breadcrumb', 'Portals › Teacher Workspace')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>👩‍🏫 Teacher Workspace & Faculty Hub</h1>
        <p>Welcome, <strong>{{ $teacher?->user?->name }}</strong> — TSC: <strong>{{ $teacher?->tsc_number ?: 'Staff Member' }}</strong> | {{ $teacher?->specialization ?? 'Faculty Member' }}</p>
    </div>
    @if(auth()->user()?->isDemoAccount())
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <a href="{{ route('school.register') }}" class="btn btn-success" id="teacher-btn-register-school" style="background:#059669;color:#ffffff;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-building-add"></i> Register School
        </a>
        <form method="POST" action="{{ route('logout') }}" id="teacher-logout-form-demo" style="margin:0;display:inline;">
            @csrf
            <button type="submit" class="btn btn-danger" id="teacher-btn-logout" style="background:#dc2626;color:#ffffff;font-weight:600;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
        <span class="badge" style="background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.3);padding:8px 12px;font-size:0.82rem;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-eye"></i> Demo Mode (Read-Only)
        </span>
    </div>
    @else
    <div style="display:flex;gap:10px">
        <a href="{{ route('examinations.marks') }}" class="btn btn-primary">
            <i class="bi bi-pencil-square"></i> Enter Marks
        </a>
        <a href="{{ route('attendance.index') }}" class="btn btn-outline">
            <i class="bi bi-calendar-check"></i> Roll Call
        </a>
    </div>
    @endif
</div>

<div class="stats-grid stats-grid-3" style="margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📖</div>
        <div class="stat-body">
            <div class="stat-label">Assigned Subjects</div>
            <div class="stat-value">{{ $allocations->count() }} Classes</div>
            <div class="stat-trend up">Weekly Teaching Workload</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">⏰</div>
        <div class="stat-body">
            <div class="stat-label">Total Teaching Periods</div>
            <div class="stat-value">{{ $allocations->sum('lessons_per_week') }} Periods/Wk</div>
            <div class="stat-trend up">Balanced schedule</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">📚</div>
        <div class="stat-body">
            <div class="stat-label">Published Notes</div>
            <div class="stat-value">{{ $learningMaterials->count() }} Materials</div>
            <div class="stat-trend up">Digital lesson resources</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px">
    <!-- My Classes & Subjects -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-journal-bookmark"></i> My Assigned Classes & Learning Areas</h5>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Subject / Learning Area</th>
                        <th>Class & Stream</th>
                        <th>Periods / Wk</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allocations as $al)
                    <tr>
                        <td><strong>{{ $al->subject?->name }}</strong> ({{ $al->subject?->code }})</td>
                        <td><span class="badge badge-primary">{{ $al->schoolClass?->name }} {{ $al->stream?->name }}</span></td>
                        <td><strong>{{ $al->lessons_per_week }}</strong> lessons</td>
                        <td>
                            <a href="{{ route('examinations.marks', ['class_id' => $al->school_class_id, 'subject_id' => $al->subject_id]) }}" class="btn btn-outline btn-sm">
                                <i class="bi bi-pencil"></i> Marks
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="no-data">No teaching allocations assigned yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions & Notices -->
    <div style="display:flex;flex-direction:column;gap:20px">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-lightning-charge"></i> Faculty Shortcuts</h5>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                <a href="{{ route('attendance.index') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                    <i class="bi bi-check2-square" style="margin-right:8px"></i> Daily Attendance Register
                </a>
                <a href="{{ route('examinations.marks') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                    <i class="bi bi-pencil" style="margin-right:8px"></i> Enter Exam Marks & CBC Rubrics
                </a>
                <a href="{{ route('academics.timetable') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                    <i class="bi bi-calendar3" style="margin-right:8px"></i> View Master Timetable
                </a>
                <a href="{{ route('operations.materials') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                    <i class="bi bi-upload" style="margin-right:8px"></i> Upload Homework Assignment
                </a>
                <a href="{{ route('portal.practice.content') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                    <i class="bi bi-lightning-charge" style="margin-right:8px"></i> Manage Practice Questions & Chapters
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-megaphone"></i> Announcements</h5>
            </div>
            <div class="card-body" style="padding:0">
                @foreach($announcements as $ann)
                <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9">
                    <strong style="font-size:0.85rem;color:var(--primary)">{{ $ann->title }}</strong>
                    <p style="margin:4px 0 0 0;font-size:0.75rem;color:var(--text-muted)">{{ Str::limit($ann->content, 90) }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
