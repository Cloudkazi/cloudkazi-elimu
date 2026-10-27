@extends('layouts.app')
@section('title', 'Examinations Management')
@section('breadcrumb', 'Examinations')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📝 Examinations & Assessment Sessions</h1>
        <p>Schedule exam series, manage CBC formative rubrics, and compute terminal rankings</p>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-primary" onclick="document.getElementById('modal-exam').style.display='flex'">
            <i class="bi bi-plus-circle"></i> Schedule Examination
        </button>
        <a href="{{ route('examinations.results') }}" class="btn btn-outline">
            <i class="bi bi-bar-chart"></i> View Results
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-list-check"></i> Examination Series & Assessments</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Examination Name</th>
                    <th>Term & Year</th>
                    <th>Curriculum</th>
                    <th>Exam Type</th>
                    <th>Dates</th>
                    <th>Submissions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($examinations as $exam)
                <tr>
                    <td>
                        <strong style="color:var(--primary);font-size:0.95rem">{{ $exam->name }}</strong>
                    </td>
                    <td>
                        <div>{{ $exam->academicYear?->name ?? '2026' }}</div>
                        <span class="badge badge-gray">{{ $exam->term?->name ?? 'Term 1' }}</span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $exam->curriculum_type === 'CBC' ? 'primary' : ($exam->curriculum_type === '8-4-4' ? 'teal' : 'info') }}">
                            {{ $exam->curriculum_type }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-outline" style="border:1px solid #cbd5e1">
                            {{ ucfirst(str_replace('_', ' ', $exam->exam_type)) }}
                        </span>
                    </td>
                    <td>
                        <div style="font-size:0.8rem">
                            {{ \Carbon\Carbon::parse($exam->start_date)->format('M d') }} – {{ \Carbon\Carbon::parse($exam->end_date)->format('M d, Y') }}
                        </div>
                    </td>
                    <td>
                        <div style="font-size:0.85rem">
                            <strong>{{ $exam->marks844_count + $exam->cbc_assessments_count }}</strong> marks entered
                        </div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">
                            {{ $exam->report_summaries_count }} report summaries
                        </div>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('examinations.marks.entry', $exam->id) }}" class="btn btn-primary btn-sm" title="Enter Marks">
                                <i class="bi bi-input-cursor"></i> Marks
                            </a>
                            <a href="{{ route('examinations.results', ['exam_id' => $exam->id]) }}" class="btn btn-outline btn-sm" title="Analysis">
                                <i class="bi bi-bar-chart"></i>
                            </a>
                            <a href="{{ route('examinations.report-cards', ['exam_id' => $exam->id]) }}" class="btn btn-outline btn-sm" title="Report Cards">
                                <i class="bi bi-file-earmark-richtext"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="no-data">No examination sessions created yet. Click "Schedule Examination".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Schedule Exam -->
<div id="modal-exam" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-calendar-plus"></i> Schedule New Examination</h5>
            <button onclick="document.getElementById('modal-exam').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('examinations.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Examination Title *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Term 1 Mid-Term Assessment 2026" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Academic Year *</label>
                        <select name="academic_year_id" class="form-control" required>
                            @foreach($years as $y)
                                <option value="{{ $y->id }}" {{ $y->is_current ? 'selected' : '' }}>{{ $y->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Term *</label>
                        <select name="term_id" class="form-control" required>
                            @foreach($terms as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Curriculum Track *</label>
                        <select name="curriculum_type" class="form-control" required>
                            <option value="CBC">CBC (Competency Based)</option>
                            <option value="8-4-4">8-4-4 Secondary</option>
                            <option value="Both">Both / Whole School</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Exam Type *</label>
                        <select name="exam_type" class="form-control" required>
                            <option value="opener">Opener Exam</option>
                            <option value="midterm" selected>Mid-Term Exam</option>
                            <option value="endterm">End of Term Exam</option>
                            <option value="mock">Mock / Trial Exam</option>
                            <option value="cbc_assessment">CBC Formative Assessment</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date *</label>
                        <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-d', strtotime('+5 days')) }}" required>
                    </div>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-exam').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Schedule Session</button>
            </div>
        </form>
    </div>
</div>
@endsection
