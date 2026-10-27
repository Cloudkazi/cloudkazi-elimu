@extends('layouts.app')
@section('title', 'Academics Management')
@section('breadcrumb', 'Academics')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🎓 Academics Control Hub</h1>
        <p>Manage curriculum, classes, subjects, teacher allocations, and academic timetable</p>
    </div>
</div>

<div class="stats-grid stats-grid-4" style="margin-bottom:24px">
    <a href="{{ route('academics.classes') }}" style="text-decoration:none">
        <div class="stat-card stat-blue">
            <div class="stat-icon">🏫</div>
            <div class="stat-body">
                <div class="stat-label">Classes & Streams</div>
                <div class="stat-value">{{ $classes->count() }} Classes</div>
                <div class="stat-trend up">Manage enrollment</div>
            </div>
        </div>
    </a>
    <a href="{{ route('academics.subjects') }}" style="text-decoration:none">
        <div class="stat-card stat-green">
            <div class="stat-icon">📖</div>
            <div class="stat-body">
                <div class="stat-label">Subjects & Areas</div>
                <div class="stat-value">{{ $subjects->count() }} Subjects</div>
                <div class="stat-trend up">CBC & 8-4-4</div>
            </div>
        </div>
    </a>
    <a href="{{ route('academics.teachers') }}" style="text-decoration:none">
        <div class="stat-card stat-orange">
            <div class="stat-icon">👨‍🏫</div>
            <div class="stat-body">
                <div class="stat-label">Teaching Staff</div>
                <div class="stat-value">{{ $teachers->count() }} Faculty</div>
                <div class="stat-trend up">TSC & BOM Staff</div>
            </div>
        </div>
    </a>
    <a href="{{ route('timetable.index') }}" style="text-decoration:none">
        <div class="stat-card stat-purple">
            <div class="stat-icon">📅</div>
            <div class="stat-body">
                <div class="stat-label">Master Timetable</div>
                <div class="stat-value">Automated</div>
                <div class="stat-trend up">Conflict-free scheduler</div>
            </div>
        </div>
    </a>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px">
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-diagram-2"></i> Current Classes Breakdown</h5>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Curriculum</th>
                        <th>Streams</th>
                        <th>Students</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($classes as $c)
                    <tr>
                        <td><strong>{{ $c->name }}</strong></td>
                        <td><span class="badge badge-{{ $c->curriculum === 'CBC' ? 'primary' : 'teal' }}">{{ $c->curriculum }}</span></td>
                        <td>{{ $c->streams->count() }} streams</td>
                        <td><strong style="font-family:'Outfit'">{{ $c->students->count() }}</strong></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-lightning-charge"></i> Quick Navigation</h5>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            <a href="{{ route('academics.classes') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                <i class="bi bi-diagram-3" style="margin-right:8px"></i> Manage Classes & Streams
            </a>
            <a href="{{ route('academics.subjects') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                <i class="bi bi-journals" style="margin-right:8px"></i> Subject Catalog & Strands
            </a>
            <a href="{{ route('academics.teacher-subjects') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                <i class="bi bi-person-lines-fill" style="margin-right:8px"></i> Teacher Workload Allocation
            </a>
            <a href="{{ route('academics.curriculum') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                <i class="bi bi-book-half" style="margin-right:8px"></i> CBC & 8-4-4 Standards
            </a>
            <a href="{{ route('portal.practice.content') }}" class="btn btn-outline" style="text-align:left;justify-content:flex-start">
                <i class="bi bi-lightning-charge" style="margin-right:8px"></i> Student Practice Content (Questions & Chapters)
            </a>
            <a href="{{ route('timetable.index') }}" class="btn btn-primary" style="text-align:left;justify-content:flex-start">
                <i class="bi bi-calendar3-week" style="margin-right:8px"></i> Open Timetable Generator
            </a>
        </div>
    </div>
</div>
@endsection
