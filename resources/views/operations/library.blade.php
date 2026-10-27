@extends('layouts.app')
@section('title', 'Library Management')
@section('breadcrumb', 'Operations › Library')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📚 School Library & Textbooks Catalog</h1>
        <p>Manage curriculum course books, reference materials, ISBN records, and copy tracking</p>
    </div>
   <div style="display:flex;gap:10px">
    <button class="btn btn-primary" onclick="document.getElementById('modal-book').style.display='flex'">
        <i class="bi bi-plus-circle"></i> Add New Book
    </button>

    <button class="btn btn-outline" onclick="document.getElementById('modal-category').style.display='flex'">
        <i class="bi bi-tags"></i> Add Category
    </button>

    <a href="{{ route('library.issue.form') }}" class="btn btn-outline">
        <i class="bi bi-box-arrow-right"></i> Issue Desk
    </a>
</div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" action="{{ route('library.books') }}" style="display:grid;grid-template-columns:2fr 1.5fr auto;gap:16px;align-items:end">
            <div class="form-group">
                <label class="form-label">Search Catalog</label>
                <input type="text" name="search" class="form-control" placeholder="Search by title, author, or ISBN..." value="{{ request('search') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Filter by Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
                @if(request()->filled('search') || request()->filled('category_id'))
                <a href="{{ route('library.books') }}" class="btn btn-outline">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-bookshelf"></i> Cataloged Library Collection ({{ $books->total() }})</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Title & Subtitle</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>ISBN</th>
                    <th>Location / Shelf</th>
                    <th>Available Copies</th>
                </tr>
            </thead>
            <tbody>
                @forelse($books as $b)
                <tr>
                    <td>
                        <strong style="color:var(--primary)">{{ $b->title }}</strong>
                    </td>
                    <td>{{ $b->author }}</td>
                    <td><span class="badge badge-primary">{{ $b->category?->name ?? 'General' }}</span></td>
                    <td><span style="font-family:monospace;font-size:0.85rem">{{ $b->isbn ?: 'N/A' }}</span></td>
                    <td>{{ $b->shelf_location ?: 'Main Stack' }}</td>
                    <td>
                        <span class="badge badge-{{ $b->available_copies > 0 ? 'success' : 'danger' }}">
                            {{ $b->available_copies }} / {{ $b->total_copies }} available
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">No books cataloged yet. Click "Add New Book".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($books->hasPages())
        <div class="card-footer">{{ $books->links() }}</div>
    @endif
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
                <div class="form-group">
                    <label class="form-label">Book Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Blossoms of the Savannah" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Author *</label>
                    <input type="text" name="author" class="form-control" placeholder="e.g. Henry Ole Kulet" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
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
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" class="form-control" placeholder="978-9966-...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Shelf Location</label>
                        <input type="text" name="shelf_location" class="form-control" placeholder="e.g. Shelf B4">
                    </div>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-book').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save to Catalog</button>
            </div>
        </form>
    </div>
</div>
<!-- Modal: Add Category -->
<div id="modal-category"
     style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;
            background:rgba(0,0,0,0.6);z-index:1000;
            align-items:center;justify-content:center">

    <div class="card"
         style="width:100%;max-width:480px;background:#fff;border-radius:12px">

        <div class="card-header"
             style="display:flex;justify-content:space-between;align-items:center">

            <h5>
                <i class="bi bi-tags"></i> Add Library Category
            </h5>

            <button type="button"
                    onclick="document.getElementById('modal-category').style.display='none'"
                    style="background:none;border:none;font-size:1.5rem;cursor:pointer">
                &times;
            </button>
        </div>

        <form action="{{ route('library.categories.store') }}" method="POST">
            @csrf

            <div class="card-body">

                <div class="form-group">
                    <label class="form-label">Category Name *</label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        placeholder="e.g. Mathematics Textbooks"
                        maxlength="100"
                        required>
                </div>

            </div>

            <div class="card-footer"
                 style="display:flex;justify-content:flex-end;gap:10px">

                <button
                    type="button"
                    class="btn btn-outline"
                    onclick="document.getElementById('modal-category').style.display='none'">
                    Cancel
                </button>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i>
                    Save Category
                </button>

            </div>
        </form>
    </div>
</div>

@endsection
