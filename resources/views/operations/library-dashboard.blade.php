@extends('layouts.app')
@section('title', 'Library Dashboard')
@section('breadcrumb', 'Operations › Library › Dashboard')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📚 School Library Dashboard</h1>
        <p>Comprehensive overview of book catalogs, circulations, active loans, and overdue tracking</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('library.issue.form') }}" class="btn btn-primary">
            <i class="bi bi-box-arrow-right"></i> Issue Book
        </a>
        <button class="btn btn-outline" onclick="document.getElementById('modal-book').style.display='flex'">
            <i class="bi bi-plus-circle"></i> Add Book
        </button>
        <button class="btn btn-outline" onclick="document.getElementById('modal-category').style.display='flex'">
            <i class="bi bi-tags"></i> Add Category
        </button>
        <a href="{{ route('library.returns') }}" class="btn btn-outline">
            <i class="bi bi-arrow-return-left"></i> Return Desk
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">📖</div>
        <div class="stat-body">
            <div class="stat-label">Book Titles</div>
            <div class="stat-value">{{ $totalTitles }}</div>
            <div class="stat-trend neutral">{{ $totalCopies }} Total Copies</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-label">Available Copies</div>
            <div class="stat-value">{{ $availableCopies }}</div>
            <div class="stat-trend up">On Shelves</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">🔄</div>
        <div class="stat-body">
            <div class="stat-label">Active Loans</div>
            <div class="stat-value">{{ $issuedCount }}</div>
            <div class="stat-trend neutral">With Students</div>
        </div>
    </div>
    <div class="stat-card stat-red">
        <div class="stat-icon">⚠️</div>
        <div class="stat-body">
            <div class="stat-label">Overdue Books</div>
            <div class="stat-value" style="color:var(--danger)">{{ $overdueCount }}</div>
            <div class="stat-trend {{ $overdueCount > 0 ? 'down' : 'neutral' }}">Require Return</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">🏷️</div>
        <div class="stat-body">
            <div class="stat-label">Categories</div>
            <div class="stat-value">{{ $categoriesCount }}</div>
            <div class="stat-trend neutral">Classifications</div>
        </div>
    </div>
</div>

@if($overdueCount > 0)
<div class="card" style="margin-bottom:20px;background:rgba(239, 68, 68, 0.08);border-left:4px solid var(--danger)">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;padding:14px 20px">
        <div style="display:flex;align-items:center;gap:12px">
            <div style="font-size:1.6rem;color:var(--danger)">⚠️</div>
            <div>
                <strong style="color:var(--danger);font-size:0.95rem">Attention: {{ $overdueCount }} books are currently overdue!</strong>
                <p style="margin:2px 0 0;font-size:0.82rem;color:var(--text-muted)">Please review overdue borrower records and request returns.</p>
            </div>
        </div>
        <a href="{{ route('library.returns') }}?overdue_only=1" class="btn btn-outline btn-sm" style="border-color:var(--danger);color:var(--danger)">
            View Overdue Books
        </a>
    </div>
</div>
@endif

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;margin-bottom:20px">
    <!-- Recent Circulations -->
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-arrow-left-right"></i> Recent Circulation Activity</h5>
            <a href="{{ route('library.history') }}" style="font-size:0.82rem;color:var(--primary);text-decoration:none">View All History ›</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Borrower</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th style="text-align:right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentIssues as $iss)
                    @php
                        $isOverdue = $iss->isOverdue();
                    @endphp
                    <tr style="{{ $isOverdue ? 'background:rgba(239, 68, 68, 0.05);' : '' }}">
                        <td>
                            <strong style="color:var(--primary)">{{ $iss->book?->title }}</strong>
                            <div style="font-size:0.75rem;color:var(--text-muted)">{{ $iss->book?->author }}</div>
                        </td>
                        <td>
                            <div style="font-weight:600">{{ $iss->student?->full_name }}</div>
                            <div style="font-size:0.75rem;color:var(--text-muted)">{{ $iss->student?->admission_number }}</div>
                        </td>
                        <td>
                            <span style="color:{{ $isOverdue ? 'var(--danger)' : 'inherit' }};font-weight:{{ $isOverdue ? '700' : 'normal' }}">
                                {{ \Carbon\Carbon::parse($iss->due_date)->format('d M Y') }}
                                @if($isOverdue)
                                    <small style="display:block;font-size:0.72rem;color:var(--danger)">(Overdue)</small>
                                @endif
                            </span>
                        </td>
                        <td>
                            @if($iss->status === 'returned')
                                <span class="badge badge-success">Returned</span>
                            @elseif($isOverdue)
                                <span class="badge badge-danger">Overdue</span>
                            @else
                                <span class="badge badge-primary">Issued</span>
                            @endif
                        </td>
                        <td style="text-align:right">
                            @if($iss->status === 'issued')
                            <form action="{{ route('library.return', $iss->id) }}" method="POST" style="display:inline">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm">
                                    <i class="bi bi-arrow-return-left"></i> Return
                                </button>
                            </form>
                            @else
                                <span style="font-size:0.8rem;color:var(--text-muted)">Completed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="no-data">No recent circulation records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Navigation & Categories -->
    <div style="display:flex;flex-direction:column;gap:20px">
        <!-- Quick Actions Card -->
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-lightning-charge"></i> Quick Navigation</h5>
            </div>
            <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <a href="{{ route('library.books') }}" class="btn btn-outline" style="text-align:left;display:flex;align-items:center;gap:8px">
                    <i class="bi bi-book"></i> Book Catalog
                </a>
                <a href="{{ route('library.categories') }}" class="btn btn-outline" style="text-align:left;display:flex;align-items:center;gap:8px">
                    <i class="bi bi-tags"></i> Categories
                </a>
                <a href="{{ route('library.issue.form') }}" class="btn btn-outline" style="text-align:left;display:flex;align-items:center;gap:8px">
                    <i class="bi bi-box-arrow-right"></i> Issue Desk
                </a>
                <a href="{{ route('library.returns') }}" class="btn btn-outline" style="text-align:left;display:flex;align-items:center;gap:8px">
                    <i class="bi bi-arrow-return-left"></i> Return Desk
                </a>
                <a href="{{ route('library.history') }}" class="btn btn-outline" style="text-align:left;display:flex;align-items:center;gap:8px">
                    <i class="bi bi-clock-history"></i> History
                </a>
                <a href="{{ route('library.reports') }}" class="btn btn-outline" style="text-align:left;display:flex;align-items:center;gap:8px">
                    <i class="bi bi-file-earmark-bar-graph"></i> Reports
                </a>
            </div>
        </div>

        <!-- Categories Breakdown -->
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <h5><i class="bi bi-tags"></i> Book Categories</h5>
                <a href="{{ route('library.categories') }}" style="font-size:0.82rem;color:var(--primary);text-decoration:none">Manage ›</a>
            </div>
            <div class="card-body" style="padding:0">
                @forelse($categories as $cat)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid #f1f5f9">
                    <div>
                        <strong style="font-size:0.88rem">{{ $cat->name }}</strong>
                        @if($cat->description)
                        <div style="font-size:0.75rem;color:var(--text-muted)">{{ Str::limit($cat->description, 40) }}</div>
                        @endif
                    </div>
                    <span class="badge badge-primary">{{ $cat->books_count }} {{ Str::plural('title', $cat->books_count) }}</span>
                </div>
                @empty
                <div style="padding:24px;text-align:center;color:var(--text-muted)">No categories configured.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Book -->
<div id="modal-book" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-book"></i> Catalog New Book</h5>
            <button onclick="document.getElementById('modal-book').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('library.books.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Book Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Blossoms of the Savannah" required>
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Author *</label>
                    <input type="text" name="author" class="form-control" placeholder="e.g. Henry Ole Kulet" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category_id" class="form-control" required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Copies *</label>
                        <input type="number" name="total_copies" class="form-control" value="20" min="1" required>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">ISBN (Optional)</label>
                        <input type="text" name="isbn" class="form-control" placeholder="978-...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Shelf Location</label>
                        <input type="text" name="shelf_location" class="form-control" placeholder="Shelf A-3">
                    </div>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-book').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Book</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Category -->
<div id="modal-category" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:440px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-tags"></i> Add Library Category</h5>
            <button onclick="document.getElementById('modal-category').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('library.categories.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Sciences, Literature" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description (Optional)</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Category notes..."></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-category').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Add Category</button>
            </div>
        </form>
    </div>
</div>
@endsection
