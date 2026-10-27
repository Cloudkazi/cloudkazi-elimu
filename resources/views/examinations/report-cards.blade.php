@extends('layouts.app')
@section('title', 'Report Cards Distribution')
@section('breadcrumb', 'Examinations › Report Cards')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📑 Academic Report Cards Center</h1>
        <p>Preview, download, and print student terminal performance reports for parents</p>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px">
        <form method="GET" action="{{ route('examinations.report-cards') }}" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <label class="form-label" style="font-size:0.8rem">Examination</label>
                <select name="exam_id" class="form-control" onchange="this.form.submit()">
                    @foreach($examinations as $ex)
                        <option value="{{ $ex->id }}" {{ ($selectedExamId == $ex->id) ? 'selected' : '' }}>
                            {{ $ex->name }} ({{ $ex->academicYear?->name ?? '2026' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1;min-width:200px">
                <label class="form-label" style="font-size:0.8rem">Class</label>
                <select name="class_id" class="form-control" onchange="this.form.submit()">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ ($selectedClassId == $c->id) ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->curriculum }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter Students</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-file-earmark-person"></i> Enrolled Students in Class</h5>
        <span class="badge badge-primary">{{ $students->total() }} Students</span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Adm No.</th>
                    <th>Student Name</th>
                    <th>Class & Stream</th>
                    <th>Curriculum</th>
                    <th>Average Score</th>
                    <th>Mean Grade</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $s)
                @php
                    $summary = $s->reportSummaries->first();
                @endphp
                <tr>
                    <td><span class="badge badge-gray">{{ $s->admission_number }}</span></td>
                    <td><strong style="color:var(--text-dark)">{{ $s->full_name }}</strong></td>
                    <td>{{ $s->schoolClass?->name }} ({{ $s->stream?->name ?? '—' }})</td>
                    <td>
                        <span class="badge badge-{{ $s->curriculum === 'CBC' ? 'primary' : 'teal' }}">{{ $s->curriculum }}</span>
                    </td>
                    <td>
                        @if($summary)
                            <strong style="color:var(--primary);font-family:'Outfit'">{{ number_format($summary->average_score, 1) }}%</strong>
                        @else
                            <span style="color:var(--text-muted);font-size:0.8rem">Not Computed</span>
                        @endif
                    </td>
                    <td>
                        @if($summary?->mean_grade)
                            <span class="badge badge-success">{{ $summary->mean_grade }}</span>
                        @else
                            <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('examinations.report-card', ['examId' => $selectedExamId ?? 1, 'studentId' => $s->id]) }}" class="btn btn-primary btn-sm" target="_blank">
                            <i class="bi bi-printer"></i> View / Print Report Card
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="no-data">No students found in this class.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($students->hasPages())
        <div class="card-footer">{{ $students->links() }}</div>
    @endif
</div>
@endsection
