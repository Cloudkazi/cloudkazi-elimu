@extends('layouts.app')
@section('title', 'Library Reports')
@section('breadcrumb', 'Operations › Library › Reports')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📊 School Library Analytics & Reports</h1>
        <p>Circulation volume, inventory health, top borrowed titles, and overdue analysis</p>
    </div>
    <div>
        <a href="{{ route('library.index') }}" class="btn btn-outline">
            <i class="bi bi-speedometer2"></i> Library Dashboard
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📚</div>
        <div class="stat-body">
            <div class="stat-label">Cataloged Titles</div>
            <div class="stat-value">{{ $totalBooks }}</div>
            <div class="stat-trend neutral">{{ $totalCopies }} Total Copies</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">🔄</div>
        <div class="stat-body">
            <div class="stat-label">All-Time Loans</div>
            <div class="stat-value">{{ $totalIssuedAllTime }}</div>
            <div class="stat-trend up">{{ $totalReturned }} Returned</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">📖</div>
        <div class="stat-body">
            <div class="stat-label">Active Loans</div>
            <div class="stat-value">{{ $currentIssued }}</div>
            <div class="stat-trend neutral">In Circulation</div>
        </div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-icon">⚠️</div>
        <div class="stat-body">
            <div class="stat-label">Overdue Books</div>
            <div class="stat-value" style="color:var(--danger)">{{ $currentOverdue }}</div>
            <div class="stat-trend {{ $currentOverdue > 0 ? 'down' : 'neutral' }}">Require Follow-up</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:20px;margin-bottom:20px">
    <!-- Top Borrowed Books -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-trophy"></i> Most Borrowed Books</h5>
        </div>
        <div class="card-body" style="padding:0">
            @forelse($topBooks as $index => $bk)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid #f1f5f9">
                <div style="display:flex;align-items:center;gap:12px">
                    <span style="font-weight:700;color:var(--primary);font-size:1.1rem;min-width:24px">#{{ $index + 1 }}</span>
                    <div>
                        <strong style="font-size:0.9rem">{{ $bk->title }}</strong>
                        <div style="font-size:0.75rem;color:var(--text-muted)">Author: {{ $bk->author }}</div>
                    </div>
                </div>
                <span class="badge badge-primary">{{ $bk->issues_count }} {{ Str::plural('loan', $bk->issues_count) }}</span>
            </div>
            @empty
            <div style="padding:24px;text-align:center;color:var(--text-muted)">No borrowing activity recorded yet.</div>
            @endforelse
        </div>
    </div>

    <!-- Overdue Borrowers Register -->
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-exclamation-triangle"></i> Overdue Borrowers List ({{ $overdueIssues->count() }})</h5>
            <a href="{{ route('library.returns') }}?overdue_only=1" style="font-size:0.82rem;color:var(--primary)">Open Return Desk ›</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Borrower</th>
                        <th>Book Title</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overdueIssues as $ov)
                    @php
                        $daysOverdue = max(1, (int) \Carbon\Carbon::parse($ov->due_date)->diffInDays(now()));
                    @endphp
                    <tr style="background:rgba(239, 68, 68, 0.05)">
                        <td>
                            <strong style="font-size:0.88rem">{{ $ov->student?->full_name }}</strong>
                            <div style="font-size:0.75rem;color:var(--text-muted)">Adm: {{ $ov->student?->admission_number }}</div>
                        </td>
                        <td>
                            <div style="font-size:0.88rem;color:var(--primary);font-weight:600">{{ $ov->book?->title }}</div>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($ov->due_date)->format('d M Y') }}</td>
                        <td>
                            <span class="badge badge-danger">{{ $daysOverdue }} {{ Str::plural('day', $daysOverdue) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="no-data" style="text-align:center;padding:30px">
                            <span style="color:var(--success)">✔ Excellent! No books are currently overdue.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
