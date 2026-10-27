@extends('layouts.app')
@section('title', 'Examination Results Analysis')
@section('breadcrumb', 'Examinations › Results Analysis')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📊 Examination Results & Class Merit List</h1>
        <p>Terminal rankings, mean score statistics, and CBC competency achievement summaries</p>
    </div>
    <div style="display:flex;gap:10px">
        @if($selectedExam && $selectedClassId)
        <a href="{{ route('examinations.report-cards', ['exam_id' => $selectedExam->id, 'class_id' => $selectedClassId]) }}" class="btn btn-outline">
            <i class="bi bi-file-earmark-richtext"></i> Batch Report Cards
        </a>
        @endif
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="padding:16px">
        <form method="GET" action="{{ route('examinations.results') }}" style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <label class="form-label" style="font-size:0.8rem">Select Examination</label>
                <select name="exam_id" class="form-control" onchange="this.form.submit()">
                    @foreach($examinations as $ex)
                        <option value="{{ $ex->id }}" {{ ($selectedExam?->id == $ex->id) ? 'selected' : '' }}>
                            {{ $ex->name }} ({{ $ex->academicYear?->name ?? '2026' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1;min-width:200px">
                <label class="form-label" style="font-size:0.8rem">Select Class</label>
                <select name="class_id" class="form-control" onchange="this.form.submit()">
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ ($selectedClassId == $c->id) ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->curriculum }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Analyze Results</button>
        </form>
    </div>
</div>

<!-- Merit Ranking Table -->
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5>
            <i class="bi bi-trophy"></i> Merit List — {{ $selectedExam?->name ?? 'Selected Exam' }}
        </h5>
        <span class="badge badge-primary">{{ $summaries->total() }} Ranked Candidates</span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Adm No.</th>
                    <th>Student Name</th>
                    <th>Stream</th>
                    <th>Total Marks</th>
                    <th>Average (%)</th>
                    <th>Mean Grade / CBC Rubric</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summaries as $sum)
                <tr>
                    <td>
                        @if($sum->class_rank == 1)
                            <span class="badge badge-warning" style="font-size:0.9rem">🥇 1</span>
                        @elseif($sum->class_rank == 2)
                            <span class="badge badge-gray" style="font-size:0.9rem">🥈 2</span>
                        @elseif($sum->class_rank == 3)
                            <span class="badge badge-orange" style="font-size:0.9rem">🥉 3</span>
                        @else
                            <strong style="font-size:0.9rem;padding-left:8px">{{ $sum->class_rank }}</strong>
                        @endif
                    </td>
                    <td><span class="badge badge-gray">{{ $sum->student?->admission_number }}</span></td>
                    <td>
                        <strong style="color:var(--text-dark)">{{ $sum->student?->full_name }}</strong>
                    </td>
                    <td>{{ $sum->student?->stream?->name ?? '—' }}</td>
                    <td><strong>{{ number_format($sum->total_marks, 1) }}</strong></td>
                    <td>
                        <strong style="color:var(--primary);font-family:'Outfit',sans-serif;font-size:1.05rem">
                            {{ number_format($sum->average_score, 1) }}%
                        </strong>
                    </td>
                    <td>
                        @if($sum->mean_grade)
                            <span class="badge badge-{{ in_array($sum->mean_grade, ['A', 'A-', 'B+', 'EE', 'ME']) ? 'success' : 'primary' }}" style="font-size:0.85rem">
                                {{ $sum->mean_grade }}
                            </span>
                        @else
                            <span class="badge badge-gray">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('examinations.report-card', ['examId' => $selectedExam?->id ?? 1, 'studentId' => $sum->student_id]) }}" class="btn btn-outline btn-sm" title="Report Card">
                            <i class="bi bi-file-earmark-richtext"></i> Card
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="no-data">No results computed for this class yet. Enter marks first!</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($summaries->hasPages())
        <div class="card-footer">{{ $summaries->links() }}</div>
    @endif
</div>
@endsection
