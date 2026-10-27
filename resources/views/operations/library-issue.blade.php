@extends('layouts.app')
@section('title', 'Library Circulation Desk')
@section('breadcrumb', 'Operations › Library Circulation')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🔄 Library Circulation: Book Issue & Return</h1>
        <p>Lend books to students, track due dates, and record returns</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="document.getElementById('modal-issue').style.display='flex'">
            <i class="bi bi-box-arrow-right"></i> Issue Book to Student
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-arrow-left-right"></i> Active Circulations & Issued Books</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Borrower Student</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentIssues as $iss)
                <tr>
                    <td>
                        <strong style="color:var(--primary)">{{ $iss->book?->title }}</strong>
                        <div style="font-size:0.75rem;color:var(--text-muted)">Author: {{ $iss->book?->author }}</div>
                    </td>
                    <td>
                        <div style="font-weight:600">{{ $iss->student?->full_name }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">{{ $iss->student?->admission_number }}</div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($iss->issue_date)->format('d M Y') }}</td>
                    <td>
                        @php
                            $isOverdue = $iss->status === 'issued' && \Carbon\Carbon::parse($iss->due_date)->isPast();
                        @endphp
                        <span style="color:{{ $isOverdue ? 'var(--danger)' : 'var(--text-dark)' }};font-weight:{{ $isOverdue ? '700' : '500' }}">
                            {{ \Carbon\Carbon::parse($iss->due_date)->format('d M Y') }}
                            @if($isOverdue) (Overdue) @endif
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $iss->status === 'returned' ? 'success' : ($isOverdue ? 'danger' : 'warning') }}">
                            {{ ucfirst($iss->status) }}
                        </span>
                    </td>
                    <td>
                        @if($iss->status === 'issued')
                        <form action="{{ route('library.return', $iss->id) }}" method="POST" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm">
                                <i class="bi bi-check2-circle"></i> Return Book
                            </button>
                        </form>
                        @else
                            <span style="color:var(--text-muted);font-size:0.8rem">Returned</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">No book circulation records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Issue Book -->
<div id="modal-issue" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-box-arrow-right"></i> Issue Book to Student</h5>
            <button onclick="document.getElementById('modal-issue').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('library.issue') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Book to Issue *</label>
                    <select name="book_id" class="form-control" required>
                        @foreach($books as $bk)
                            <option value="{{ $bk->id }}" {{ $bk->available_copies < 1 ? 'disabled' : '' }}>
                                {{ $bk->title }} ({{ $bk->available_copies }} copies left)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Student *</label>
                    <select name="student_id" class="form-control" required>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->admission_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Issue Date *</label>
                        <input type="date" name="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date *</label>
                        <input type="date" name="due_date" class="form-control" value="{{ date('Y-m-d', strtotime('+14 days')) }}" required>
                    </div>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-issue').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Complete Issue</button>
            </div>
        </form>
    </div>
</div>
@endsection
