@extends('layouts.app')
@section('title', 'Discipline Management')
@section('breadcrumb', 'Operations › Discipline')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>⚖️ Student Discipline & Behavioral Tracking</h1>
        <p>Log behavioral incidents, restorative disciplinary actions, and parent notification status</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="document.getElementById('modal-disc').style.display='flex'">
            <i class="bi bi-plus-circle"></i> Log Disciplinary Incident
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-journal-x"></i> Recorded Incidents Roster</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Incident Title</th>
                    <th>Severity</th>
                    <th>Action Taken</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($incidents as $inc)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($inc->incident_date)->format('d M Y') }}</td>
                    <td><strong>{{ $inc->student?->full_name }}</strong></td>
                    <td>{{ $inc->student?->schoolClass?->name }}</td>
                    <td>{{ $inc->title }}</td>
                    <td>
                        <span class="badge badge-{{ $inc->severity === 'Critical' || $inc->severity === 'High' ? 'danger' : ($inc->severity === 'Medium' ? 'warning' : 'gray') }}">
                            {{ $inc->severity }}
                        </span>
                    </td>
                    <td>{{ $inc->action_taken ?: 'Under Review' }}</td>
                    <td>
                        <span class="badge badge-{{ $inc->status === 'resolved' ? 'success' : 'warning' }}">
                            {{ ucfirst($inc->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="no-data">Exemplary discipline! No student incidents recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($incidents->hasPages())
        <div class="card-footer">{{ $incidents->links() }}</div>
    @endif
</div>

<!-- Modal: Log Incident -->
<div id="modal-disc" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-exclamation-octagon"></i> Log Student Incident</h5>
            <button onclick="document.getElementById('modal-disc').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('discipline.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Student Involved *</label>
                    <select name="student_id" class="form-control" required>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->admission_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Incident Date *</label>
                        <input type="date" name="incident_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Severity *</label>
                        <select name="severity" class="form-control" required>
                            <option value="Low">Low (Minor Infraction)</option>
                            <option value="Medium">Medium (Disruptive)</option>
                            <option value="High">High (Severe)</option>
                            <option value="Critical">Critical (Gross Misconduct)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Incident Title / Nature *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Chronic tardiness, Damaged lab apparatus" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Detailed Description *</label>
                    <textarea name="description" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Corrective Action Taken</label>
                    <input type="text" name="action_taken" class="form-control" placeholder="e.g. Verbal warning, Restitution of equipment">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="open">Open</option>
                            <option value="in_review">In Review</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:center;margin-top:24px">
                        <label style="cursor:pointer">
                            <input type="checkbox" name="parent_notified" value="1" checked>
                            Parent Notified
                        </label>
                    </div>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-disc').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Incident Record</button>
            </div>
        </form>
    </div>
</div>
@endsection
