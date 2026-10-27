@extends('layouts.app')
@section('title', 'Book Categories')
@section('breadcrumb', 'Operations › Library › Categories')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🏷️ Library Book Categories</h1>
        <p>Organize and classify school textbooks, reference literature, and supplementary materials</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="document.getElementById('modal-category').style.display='flex'">
            <i class="bi bi-plus-circle"></i> Add New Category
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-tags"></i> All Library Categories ({{ $categories->count() }})</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Books Cataloged</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $cat)
                <tr>
                    <td>
                        <strong style="color:var(--primary)">{{ $cat->name }}</strong>
                    </td>
                    <td>
                        {{ $cat->description ?: 'No description provided' }}
                    </td>
                    <td>
                        <span class="badge badge-primary">
                            {{ $cat->books_count }} {{ Str::plural('Title', $cat->books_count) }}
                        </span>
                    </td>
                    <td>{{ $cat->created_at ? $cat->created_at->format('d M Y') : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="no-data">No categories found. Click "Add New Category".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Category -->
<div id="modal-category" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:460px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-tags"></i> Add Library Category</h5>
            <button onclick="document.getElementById('modal-category').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('library.categories.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Science, Mathematics, Literature" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description (Optional)</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Category notes or curriculum level..."></textarea>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-category').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Category</button>
            </div>
        </form>
    </div>
</div>
@endsection
