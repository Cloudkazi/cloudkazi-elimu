@extends('layouts.app')
@section('title', 'Academic Performance')
@section('breadcrumb', 'Portals › Academic Performance')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>Academic Performance</h1>
        <p>{{ $student ? $student->full_name . ' · ' . ($student->schoolClass?->name ?? '') : 'Select a linked child' }} — results from recorded assessments only.</p>
    </div>
    <a href="{{ $parentMode ? route('portal.parent') : route('portal.student') }}" class="btn btn-outline">Back to portal</a>
</div>

@if($parentMode && $children->isNotEmpty())
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px">
    @foreach($children as $child)
    <a class="btn btn-sm {{ $student && $student->id === $child->id ? 'btn-primary' : 'btn-outline' }}"
       href="{{ route('portal.parent.performance', ['student_id' => $child->id, 'year_id' => $selectedYearId, 'term_id' => $selectedTermId, 'exam_id' => $selectedExamId]) }}">
        {{ $child->full_name }} · {{ $child->schoolClass?->name }}
    </a>
    @endforeach
</div>
@endif

@if(!$student)
    <div class="card"><div class="card-body">No linked student is available for this account.</div></div>
@else
<form method="GET" class="card" style="margin-bottom:20px">
    @if($parentMode)<input type="hidden" name="student_id" value="{{ $student->id }}">@endif
    <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end">
        <div><label class="form-label" for="year_id">Academic year</label>
            <select name="year_id" id="year_id" class="form-select">
                @foreach($years as $year)<option value="{{ $year->id }}" @selected((int)$selectedYearId === (int)$year->id)>{{ $year->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="form-label" for="term_id">Term</label>
            <select name="term_id" id="term_id" class="form-select"><option value="">All terms</option>
                @foreach($terms as $term)<option value="{{ $term->id }}" @selected((int)$selectedTermId === (int)$term->id)>{{ $term->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="form-label" for="exam_id">Examination</label>
            <select name="exam_id" id="exam_id" class="form-select"><option value="">All examinations</option>
                @foreach($exams as $exam)<option value="{{ $exam->id }}" @selected((int)$selectedExamId === (int)$exam->id)>{{ $exam->name }} · {{ $exam->term?->name }}</option>@endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Apply filters</button>
    </div>
</form>

<div class="stats-grid stats-grid-4" style="margin-bottom:20px">
    <div class="stat-card stat-blue"><div class="stat-icon">📚</div><div class="stat-body"><div class="stat-label">{{ $scoreMode ? 'Recorded assessments' : 'Recorded competency assessments' }}</div><div class="stat-value">{{ $scoreMode ? $numericResults->count() : $cbcResults->count() }}</div></div></div>
    @if($scoreMode)
    <div class="stat-card stat-green"><div class="stat-icon">📈</div><div class="stat-body"><div class="stat-label">Mean score</div><div class="stat-value">{{ $numericResults->isNotEmpty() && $numericResults->sum('out_of') > 0 ? number_format($numericResults->sum('score') / $numericResults->sum('out_of') * 100, 1) . '%' : '—' }}</div></div></div>
    @else
    <div class="stat-card stat-green"><div class="stat-icon">🌱</div><div class="stat-body"><div class="stat-label">Overall level</div><div class="stat-value">{{ $summaries->last()['mean_level'] ?? '—' }}</div></div></div>
    @endif
    <div class="stat-card stat-purple"><div class="stat-icon">⭐</div><div class="stat-body"><div class="stat-label">Strong subjects</div><div class="stat-value" style="font-size:.95rem">{{ $strongSubjects->isNotEmpty() ? $strongSubjects->pluck('name')->join(', ') : '—' }}</div></div></div>
    <div class="stat-card stat-orange"><div class="stat-icon">🎯</div><div class="stat-body"><div class="stat-label">Focus subjects</div><div class="stat-value" style="font-size:.95rem">{{ $supportSubjects->isNotEmpty() ? $supportSubjects->pluck('name')->join(', ') : '—' }}</div></div></div>
</div>

<section class="card" style="margin-bottom:20px">
    <div class="card-header"><h2 style="font-size:1rem;margin:0">Summative assessments and trends</h2></div>
    <div class="table-responsive"><table>
        <thead><tr><th>Assessment</th><th>Academic year</th><th>Term</th>@if($scoreMode)<th>Mean marks</th><th>Total points</th>@else<th>Competency levels recorded</th>@endif<th>Subjects</th></tr></thead>
        <tbody>
        @forelse($summaries as $summary)
            <tr>
                <td>{{ $summary['exam']?->name ?? 'Assessment' }}</td>
                <td>{{ $summary['exam']?->academicYear?->name ?? '—' }}</td>
                <td>{{ $summary['exam']?->term?->name ?? '—' }}</td>
                @if($scoreMode)
                    <td>{{ $summary['mean'] === null ? '—' : number_format($summary['mean'], 1) . '%' }}</td>
                    <td>{{ $summary['points'] === null ? '—' : number_format($summary['points'], 1) }}</td>
                @else
                    <td>
                        @foreach(['EE','ME','AE','BE'] as $level)
                            @if(($summary['levels'][$level] ?? 0) > 0)<span class="badge badge-secondary">{{ $level }}: {{ $summary['levels'][$level] }}</span>@endif
                        @endforeach
                    </td>
                @endif
                <td>{{ $summary['subjects'] }}</td>
            </tr>
        @empty
            <tr><td colspan="6">No published assessment results match these filters.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if($scoreMode && $summaries->isNotEmpty())
    <div class="card-body" aria-label="Mean-score trend chart">
        <div style="display:flex;align-items:flex-end;gap:8px;min-height:120px;border-bottom:1px solid #cbd5e1;padding:0 4px">
            @foreach($summaries as $summary)
                @if($summary['mean'] !== null)
                <div title="{{ $summary['exam']?->name }}: {{ $summary['mean'] }}%" style="flex:1;min-width:28px;text-align:center">
                    <div style="font-size:.7rem">{{ $summary['mean'] }}%</div>
                    <div style="height:{{ max(4, min(100, $summary['mean'])) }}px;background:#2563eb;border-radius:5px 5px 0 0"></div>
                    <small>{{ Str::limit($summary['exam']?->name ?? 'Exam', 12) }}</small>
                </div>
                @endif
            @endforeach
        </div>
        <small>Trend uses recorded numerical marks only; missing assessments are not treated as zero.</small>
    </div>
    @endif
</section>

<section class="card">
    <div class="card-header"><h2 style="font-size:1rem;margin:0">{{ $scoreMode ? 'Subject performance' : 'Formative competency progress' }}</h2></div>
    <div class="table-responsive"><table>
        <thead>
            @if($scoreMode)<tr><th>Examination</th><th>Subject</th><th>Score</th><th>Out of</th><th>Grade</th><th>Remarks</th></tr>
            @else<tr><th>Examination</th><th>Subject</th><th>Strand</th><th>Sub-strand</th><th>Level</th><th>Description</th><th>Teacher remarks</th><th>Assessment date</th></tr>@endif
        </thead>
        <tbody>
        @if($scoreMode)
            @forelse($numericResults as $result)
            <tr><td>{{ $result->examination?->name }} · {{ $result->examination?->term?->name }}</td><td>{{ $result->subject?->name }}</td><td>{{ number_format($result->score, 1) }}</td><td>{{ number_format($result->out_of, 1) }}</td><td>{{ $result->grade ?? '—' }}</td><td>{{ $result->remarks ?? '—' }}</td></tr>
            @empty<tr><td colspan="6">No numerical results have been recorded for this selection.</td></tr>@endforelse
        @else
            @php($descriptions = ['EE' => 'Exceeds Expectations', 'ME' => 'Meets Expectations', 'AE' => 'Approaches Expectations', 'BE' => 'Below Expectations'])
            @forelse($cbcResults as $result)
            <tr><td>{{ $result->examination?->name }} · {{ $result->examination?->term?->name }}</td><td>{{ $result->subject?->name }}</td><td>{{ $result->strand }}</td><td>{{ $result->sub_strand ?? '—' }}</td><td><strong>{{ $result->achievement_level }}</strong></td><td>{{ $descriptions[$result->achievement_level] ?? $result->achievement_level }}</td><td>{{ $result->rubric_feedback ?? '—' }}</td><td>{{ $result->created_at?->format('d M Y') }}</td></tr>
            @empty<tr><td colspan="8">No competency assessments have been recorded for this selection.</td></tr>@endforelse
        @endif
        </tbody>
    </table></div>
</section>
@endif
@endsection
