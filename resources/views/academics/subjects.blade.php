@extends('layouts.app')
@section('title', 'Subjects Catalog')
@section('breadcrumb', 'Academics › Subjects')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📖 Subjects & Learning Areas Catalog</h1>
        <p>Curriculum learning areas, subject codes, pathways, and weekly lesson frequency</p>
    </div>
    <div style="display:flex;gap:10px">
        <a href="{{ route('academics.subject-offerings') }}" class="btn btn-outline">
            <i class="bi bi-journal-check"></i> Class Subject Offerings
        </a>
        <button class="btn btn-primary" onclick="document.getElementById('modal-subject').style.display='flex'">
            <i class="bi bi-plus-circle"></i> Add Subject
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-journals"></i> All Learning Areas & Subjects</h5>
        <div style="display:flex;gap:10px">
            <span class="badge badge-primary">CBC: {{ $subjects->where('curriculum', 'CBC')->count() }}</span>
            <span class="badge badge-teal">8-4-4: {{ $subjects->where('curriculum', '8-4-4')->count() }}</span>
            <span class="badge badge-gray">Both: {{ $subjects->where('curriculum', 'Both')->count() }}</span>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Subject Name</th>
                    <th>Curriculum</th>
                    <th>Pathway / Category</th>
                    <th>Weekly Lessons</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $s)
                <tr>
                    <td><span class="badge badge-gray" style="font-family:monospace;font-size:0.85rem">{{ $s->code }}</span></td>
                    <td>
                        <strong style="color:var(--text-dark)">{{ $s->name }}</strong>
                    </td>
                    <td>
                        <span class="badge badge-{{ $s->curriculum === 'CBC' ? 'primary' : ($s->curriculum === '8-4-4' ? 'teal' : 'info') }}">
                            {{ $s->curriculum }}
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-outline" style="border:1px solid #cbd5e1">{{ $s->pathway ?? 'General' }}</span>
                    </td>
                    <td>
                        <strong>{{ $s->lessons_per_week }}</strong> lessons/wk
                    </td>
                    <td>
                        <span class="badge badge-success">Active</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">No subjects registered yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Subject -->
<div id="modal-subject" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-book-half"></i> Register Learning Area / Subject</h5>
            <button onclick="document.getElementById('modal-subject').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('academics.subjects.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Subject Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Integrated Science, Mathematics, English" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Subject Code *</label>
                    <input type="text" name="code" class="form-control" placeholder="e.g. ISC, MATH, ENG, KISW" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Curriculum *</label>
                    <select name="curriculum" class="form-control" required>
                        <option value="CBC">CBC (Competency Based)</option>
                        <option value="8-4-4">8-4-4 System</option>
                        <option value="Both">Both / Common</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Pathway / Domain *</label>
                    <select name="pathway" class="form-control" required>
                        <option value="general">General Studies</option>
                        <option value="STEM">STEM (Pure Sciences / Tech / Math)</option>
                        <option value="Social Sciences">Social Sciences & Humanities</option>
                        <option value="Arts & Sports">Arts & Sports Science</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Lessons Per Week *</label>
                    <input type="number" name="lessons_per_week" class="form-control" value="5" min="1" max="10" required>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-subject').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Subject</button>
            </div>
        </form>
    </div>
</div>
@endsection
