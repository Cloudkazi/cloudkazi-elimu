@extends('layouts.app')
@section('title', 'Learning Materials')
@section('breadcrumb', 'Operations › Learning Materials')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📚 Learning Materials & Digital Curriculum Notes</h1>
        <p>Distribute lesson notes, homework assignments, revision past papers, and CBC learning guides</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('portal.practice.content') }}" class="btn btn-outline">
            <i class="bi bi-lightning-charge"></i> Practice Questions & Chapters
        </a>
        <button class="btn btn-primary" onclick="document.getElementById('modal-mat').style.display='flex'">
            <i class="bi bi-upload"></i> Publish Material
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-journal-text"></i> Published Learning Resources ({{ $materials->total() }})</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Title & Resource</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Teacher / Author</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $m)
                <tr>
                    <td>
                        <strong style="color:var(--primary)">{{ $m->title }}</strong>
                        <div style="font-size:0.8rem;color:var(--text-muted)">{{ $m->description }}</div>
                    </td>
                    <td><span class="badge badge-primary">{{ $m->schoolClass?->name }}</span></td>
                    <td>{{ $m->subject?->name }}</td>
                    <td><span class="badge badge-gray">{{ ucfirst(str_replace('_', ' ', $m->type)) }}</span></td>
                    <td>{{ $m->teacher?->user?->name ?? 'Curriculum Dept' }}</td>
                    <td>{{ $m->due_date ? \Carbon\Carbon::parse($m->due_date)->format('d M Y') : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">No learning materials uploaded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($materials->hasPages())
        <div class="card-footer">{{ $materials->links() }}</div>
    @endif
</div>

<!-- Modal: Add Material -->
<div id="modal-mat" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-cloud-upload"></i> Publish Learning Resource</h5>
            <button onclick="document.getElementById('modal-mat').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('materials.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Material Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Form 3 Biology Genetics Study Notes" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Target Class *</label>
                        <select name="school_class_id" class="form-control" required>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subject *</label>
                        <select name="subject_id" class="form-control" required>
                            @foreach($subjects as $sb)
                                <option value="{{ $sb->id }}">{{ $sb->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-control" required>
                            <option value="notes">Lesson Notes</option>
                            <option value="assignment">Homework Assignment</option>
                            <option value="past_paper">Past Paper</option>
                            <option value="revision_material">Revision Guide</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date (If assignment)</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Instructions / Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-mat').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Publish</button>
            </div>
        </form>
    </div>
</div>
@endsection
