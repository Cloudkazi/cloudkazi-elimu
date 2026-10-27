@extends('layouts.app')
@section('title', 'Return Books Desk')
@section('breadcrumb', 'Operations › Library › Return Books')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>↩️ Library Returns Desk</h1>
        <p>Monitor active book loans, track due dates, handle overdue books, and record returns</p>
    </div>
    <div>
        <a href="{{ route('library.issue.form') }}" class="btn btn-primary">
            <i class="bi bi-box-arrow-right"></i> Issue Book
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" action="{{ route('library.returns') }}" style="display:grid;grid-template-columns:2fr 1fr auto;gap:16px;align-items:end">
            <div class="form-group">
                <label class="form-label">Search Book or Student</label>
                <input type="text" name="search" class="form-control" placeholder="Search by title, student name, or admission..." value="{{ request('search') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Filter Status</label>
                <select name="overdue_only" class="form-select">
                    <option value="0" {{ !request('overdue_only') ? 'selected' : '' }}>All Active Loans</option>
                    <option value="1" {{ request('overdue_only') ? 'selected' : '' }}>⚠️ Overdue Only</option>
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
                @if(request()->filled('search') || request('overdue_only'))
                <a href="{{ route('library.returns') }}" class="btn btn-outline">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-arrow-return-left"></i> Books Awaiting Return ({{ $activeIssues->total() }})</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Borrower Student</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Loan Status</th>
                    <th style="text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activeIssues as $iss)
                @php
                    $isOverdue = $iss->isOverdue();
                @endphp
                <tr style="{{ $isOverdue ? 'background:rgba(239, 68, 68, 0.08); border-left: 4px solid var(--danger);' : '' }}">
                    <td>
                        <strong style="color:var(--primary)">{{ $iss->book?->title }}</strong>
                        <div style="font-size:0.75rem;color:var(--text-muted)">Author: {{ $iss->book?->author }}</div>
                    </td>
                    <td>
                        <div style="font-weight:600">{{ $iss->student?->full_name }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">Adm: {{ $iss->student?->admission_number }}</div>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($iss->issue_date)->format('d M Y') }}</td>
                    <td>
                        <span style="color:{{ $isOverdue ? 'var(--danger)' : 'inherit' }}; font-weight:{{ $isOverdue ? '700' : '500' }}">
                            {{ \Carbon\Carbon::parse($iss->due_date)->format('d M Y') }}
                            @if($isOverdue)
                                <small style="display:block;font-size:0.72rem;color:var(--danger)">⚠️ Passed Due Date</small>
                            @endif
                        </span>
                    </td>
                    <td>
                        @if($isOverdue)
                            <span class="badge badge-danger"><i class="bi bi-exclamation-triangle"></i> Overdue</span>
                        @else
                            <span class="badge badge-primary"><i class="bi bi-clock-history"></i> Active Loan</span>
                        @endif
                    </td>
                    <td style="text-align:right">
                        <form action="{{ route('library.return', $iss->id) }}" method="POST" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Confirm return for {{ addslashes($iss->book?->title) }}?')">
                                <i class="bi bi-check2-circle"></i> Record Return
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="no-data" style="text-align:center;padding:40px 20px">
                        <div style="font-size:2rem;margin-bottom:8px">🎉</div>
                        <p style="margin:0;color:var(--text-muted)">No active book loans match the selected criteria.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($activeIssues->hasPages())
    <div class="card-footer">
        {{ $activeIssues->links() }}
    </div>
    @endif
</div>
@endsection
