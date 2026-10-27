@extends('layouts.app')
@section('title', 'Enter Marks')
@section('breadcrumb', 'Examinations › Enter Marks')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>✏️ Enter Examination Marks</h1>
        <p>Record student scores for exams and assessments</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('examinations.index') }}" class="btn btn-outline">
            <i class="bi bi-list-check"></i> Exams List
        </a>
        <a href="{{ route('examinations.results') }}" class="btn btn-outline">
            <i class="bi bi-bar-chart"></i> View Results
        </a>
        <a href="{{ route('examinations.report-cards') }}" class="btn btn-primary">
            <i class="bi bi-file-earmark-richtext"></i> Report Cards
        </a>
    </div>
</div>

<!-- Select Exam + Class -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h5><i class="bi bi-funnel"></i> Select Exam & Class</h5></div>
    <div class="card-body">
        <form method="GET" action="{{ route('examinations.marks') }}"
              style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end">
            <div>
                <label class="form-label">Examination</label>
                <select name="exam_id" class="form-select" required onchange="this.form.submit()">
                    @if(empty($exams) || $exams->isEmpty())
                        <option value="">No examinations available</option>
                    @else
                        <option value="">Select Exam...</option>
                        @foreach($exams as $ex)
                        <option value="{{ $ex->id }}" {{ ($selectedExam?->id == $ex->id || request('exam_id') == $ex->id) ? 'selected' : '' }}>
                            {{ $ex->name }} ({{ $ex->term?->name ?? 'Term' }}, {{ $ex->academicYear?->name ?? 'Year' }})
                        </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div>
                <label class="form-label">Class</label>
                <select name="class_id" class="form-select" required onchange="this.form.submit()">
                    @if(empty($classOptions))
                        <option value="">No classes available</option>
                    @else
                        <option value="">Select Class...</option>
                        @foreach($classOptions as $opt)
                        <option value="{{ $opt['value'] }}" {{ $selectedClassValue == $opt['value'] ? 'selected' : '' }}>
                            {{ $opt['label'] }}
                        </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div>
                <label class="form-label">Subject</label>
                <select name="subject_id" class="form-select" onchange="this.form.submit()">
                    @if(empty($subjects) || $subjects->isEmpty())
                        <option value="">No subjects assigned to this class</option>
                    @else
                        <option value="">All Subjects</option>
                        @foreach($subjects as $sub)
                        <option value="{{ $sub->id }}" {{ ($selectedSubjectId == $sub->id || request('subject_id') == $sub->id) ? 'selected' : '' }}>
                            {{ $sub->name }}
                        </option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Load Students
                </button>
            </div>
        </form>
    </div>
</div>

@if(empty($exams) || $exams->isEmpty())
<div class="card">
    <div class="card-body no-data" style="padding:60px">
        <i class="bi bi-calendar-x"></i>
        <div style="font-size:1rem;font-weight:600;margin-top:8px">No examinations available</div>
        <div style="color:var(--text-muted);margin-top:4px">Please schedule an examination session first from the Exams List.</div>
    </div>
</div>
@elseif(isset($selectedExam) && isset($selectedClass))
<!-- Marks Entry Grid -->
<div class="card">
    <div class="card-header">
        <h5>
            <i class="bi bi-input-cursor"></i>
            {{ $selectedExam->name }} — {{ $selectedClass->name }} {{ $selectedStream ? '— ' . $selectedStream->name : '' }}
        </h5>
        <div style="display:flex;gap:10px;align-items:center">
            <span class="badge badge-info">{{ $students->count() }} students</span>
            <span class="badge badge-teal">Out of {{ $outOf ?? 100 }}</span>
        </div>
    </div>

    <form action="{{ route('examinations.store-marks') }}" method="POST">
        @csrf
        <input type="hidden" name="exam_id" value="{{ $selectedExam->id }}">
        <input type="hidden" name="class_id" value="{{ $selectedClassValue }}">
        <input type="hidden" name="subject_id" value="{{ $selectedSubjectId ?? '' }}">

        <div class="table-responsive">
            <table style="min-width:700px">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Adm No.</th>
                        @foreach($examSubjects ?? [] as $subject)
                        <th style="min-width:110px">
                            {{ $subject->name }}
                            <div style="font-weight:400;font-size:0.7rem;color:rgba(255,255,255,0.7)">/{{ $subject->max_score ?? 100 }}</div>
                        </th>
                        @endforeach
                        <th>Total</th>
                        <th>Grade</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @if(empty($examSubjects) || $examSubjects->isEmpty())
                    <tr>
                        <td colspan="20" class="no-data">
                            <i class="bi bi-journal-x"></i>
                            No subjects are configured for this class{{ $selectedStream ? '/' . $selectedStream->name : '' }}.
                            <a href="{{ route('academics.subject-offerings') }}" class="btn btn-sm btn-primary" style="margin-left:10px">
                                <i class="bi bi-plus-circle"></i> Configure Subject Offerings
                            </a>
                        </td>
                    </tr>
                    @else
                    @forelse($students as $i => $student)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div style="font-weight:600">{{ $student->full_name }}</div>
                        </td>
                        <td><code style="font-size:0.8rem">{{ $student->admission_number }}</code></td>

                        @foreach($examSubjects ?? [] as $subject)
                        @php
                            $existingScore = $existingMarks[$student->id][$subject->id] ?? null;
                        @endphp
                        <td>
                            <input type="number"
                                name="marks[{{ $student->id }}][{{ $subject->id }}]"
                                class="form-control score-input"
                                data-student="{{ $student->id }}"
                                data-max="{{ $subject->max_score ?? 100 }}"
                                min="0" max="{{ $subject->max_score ?? 100 }}"
                                step="0.5"
                                value="{{ $existingScore?->score !== null ? (float)$existingScore->score : '' }}"
                                placeholder="—"
                                style="width:90px;padding:6px 10px;text-align:center">
                        </td>
                        @endforeach

                        <td>
                            <strong class="student-total" data-student="{{ $student->id }}">
                                {{ $studentTotals[$student->id] ?? '—' }}
                            </strong>
                        </td>
                        <td>
                            <span class="badge badge-primary student-grade" data-student="{{ $student->id }}">
                                {{ $studentGrades[$student->id] ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <input type="text" name="remarks[{{ $student->id }}]"
                                class="form-control" style="width:160px;padding:5px 10px;font-size:0.8rem"
                                placeholder="Remarks..."
                                value="{{ $studentRemarks[$student->id] ?? '' }}">
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="20" class="no-data">
                            <i class="bi bi-people"></i> No students are currently enrolled in this class.
                        </td>
                    </tr>
                    @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center">
            <span style="font-size:0.875rem;color:var(--text-muted)">
                Auto-calculates total and grade. Click Save to commit all marks to results and report cards.
            </span>
            <div style="display:flex;gap:10px">
                <button type="submit" name="action" value="save_draft" class="btn btn-outline">
                    <i class="bi bi-floppy"></i> Save Draft
                </button>
                <button type="submit" name="action" value="save_publish" class="btn btn-primary">
                    <i class="bi bi-check-all"></i> Save & Publish
                </button>
            </div>
        </div>
    </form>
</div>

@else
<div class="card">
    <div class="card-body no-data" style="padding:60px">
        <i class="bi bi-pencil-square"></i>
        <div style="font-size:1rem;font-weight:600;margin-top:8px">Select an exam and class to enter marks</div>
        <div style="color:var(--text-muted);margin-top:4px">Use the filter above to load students for mark entry</div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
// Live grade calculation matching AcademicAssessmentService
const gradeMap = [
    [80,'A'], [75,'A-'], [70,'B+'], [65,'B'], [60,'B-'],
    [55,'C+'], [50,'C'], [45,'C-'], [40,'D+'], [35,'D'], [30,'D-'], [0,'E']
];
function calcGrade(percent) {
    for (const [min, grade] of gradeMap) {
        if (percent >= min) return grade;
    }
    return 'E';
}

document.querySelectorAll('.score-input').forEach(input => {
    input.addEventListener('input', function() {
        const studentId = this.dataset.student;
        const max = parseFloat(this.dataset.max) || 100;
        // clamp
        if (parseFloat(this.value) > max) this.value = max;
        if (parseFloat(this.value) < 0) this.value = 0;

        // Calculate row total
        const rowInputs = document.querySelectorAll(`.score-input[data-student="${studentId}"]`);
        let total = 0, maxTotal = 0, count = 0;
        rowInputs.forEach(i => {
            if (i.value !== '' && !isNaN(parseFloat(i.value))) {
                total += parseFloat(i.value) || 0;
                maxTotal += parseFloat(i.dataset.max) || 100;
                count++;
            }
        });
        const percent = maxTotal > 0 ? (total / maxTotal * 100).toFixed(1) : 0;

        const totalEl = document.querySelector(`.student-total[data-student="${studentId}"]`);
        if (totalEl) totalEl.textContent = count > 0 ? `${total}/${maxTotal}` : '—';

        const gradeEl = document.querySelector(`.student-grade[data-student="${studentId}"]`);
        if (gradeEl) gradeEl.textContent = count > 0 ? calcGrade(percent) : '—';
    });
});
</script>
@endpush
