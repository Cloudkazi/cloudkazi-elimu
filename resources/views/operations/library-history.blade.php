@extends('layouts.app')
@section('title', 'Circulation History')
@section('breadcrumb', 'Operations › Library › Issued Books History')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📜 Issued Books History & Circulation Register</h1>
        <p>Complete historical log of all book lending, returns, due dates, and circulation records</p>
    </div>
    <div>
        <a href="{{ route('library.reports') }}" class="btn btn-outline">
            <i class="bi bi-file-earmark-bar-graph"></i> View Reports
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" action="{{ route('library.history') }}" style="display:grid;grid-template-columns:2fr 1.5fr auto;gap:16px;align-items:end">
            <div class="form-group">
                <label class="form-label">Search Records</label>
                <input type="text" name="search" class="form-control" placeholder="Search by book title, student name, or admission..." value="{{ request('search') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Filter by Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>Currently Issued</option>
                    <option value="returned" {{ request('status') === 'returned' ? 'selected' : '' }}>Returned</option>
                    <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
                @if(request()->filled('search') || request()->filled('status'))
                <a href="{{ route('library.history') }}" class="btn btn-outline">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-clock-history"></i> Circulation History ({{ $history->total() }})</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Borrower Student</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $iss)
                @php
                    $isOverdue = $iss->isOverdue();
                @endphp
                <tr style="{{ $isOverdue ? 'background:rgba(239, 68, 68, 0.06);' : '' }}">
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
                        <span style="color:{{ $isOverdue ? 'var(--danger)' : 'inherit' }}; font-weight:{{ $isOverdue ? '700' : 'normal' }}">
                            {{ \Carbon\Carbon::parse($iss->due_date)->format('d M Y') }}
                        </span>
                    </td>
                    <td>
                        @if($iss->return_date)
                            {{ \Carbon\Carbon::parse($iss->return_date)->format('d M Y') }}
                        @else
                            <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    <td>
                        @if($iss->status === 'returned')
                            <span class="badge badge-success"><i class="bi bi-check2"></i> Returned</span>
                        @elseif($isOverdue)
                            <span class="badge badge-danger"><i class="bi bi-exclamation-triangle"></i> Overdue</span>
                        @else
                            <span class="badge badge-primary"><i class="bi bi-clock"></i> Issued</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">No circulation history records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($history->hasPages())
    <div class="card-footer">
        {{ $history->links() }}
    </div>
    @endif
</div>
@endsection
