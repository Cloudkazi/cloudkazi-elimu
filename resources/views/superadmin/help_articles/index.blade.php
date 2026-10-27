@extends('layouts.app')
@section('title', 'Help Articles — CloudKazi')
@section('breadcrumb', 'Platform Admin › AI Help Centre › Help Articles')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-journal-question text-primary"></i> Help Articles &amp; Knowledge Base</h1>
        <p style="color:var(--text-muted);font-size:0.9rem;">Manage the knowledge base used by the Elimu AI Assistant. Articles are searched by keywords, question, and category.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('superadmin.help-articles.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> Add New Article
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-3"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>
@endif

{{-- Stats strip --}}
<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <div class="card text-center py-3">
            <div style="font-size:1.8rem;font-weight:700;color:var(--primary);">{{ $articles->total() }}</div>
            <div style="font-size:0.82rem;color:var(--text-muted);">Total Articles</div>
        </div>
    </div>
    @foreach(['Admissions','Finance','Exams','System Settings'] as $cat)
        <div class="col-sm-3">
            <div class="card text-center py-3">
                <div style="font-size:1.8rem;font-weight:700;color:var(--primary);">{{ \App\Models\HelpArticle::where('category',$cat)->count() }}</div>
                <div style="font-size:0.82rem;color:var(--text-muted);">{{ $cat }}</div>
            </div>
        </div>
    @endforeach
</div>

{{-- Search & Filter Bar --}}
<form method="GET" action="{{ route('superadmin.help-articles.index') }}" class="card p-3 mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label form-label-sm">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search question, answer, or keywords..." value="{{ $search }}">
        </div>
        <div class="col-md-4">
            <label class="form-label form-label-sm">Category Filter</label>
            <select name="category" class="form-control form-control-sm">
                <option value="">— All Categories —</option>
                @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>{{ $key }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filter</button>
            <a href="{{ route('superadmin.help-articles.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </div>
</form>

{{-- Articles Table --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:0.88rem;">
            <thead class="table-light">
                <tr>
                    <th style="width:30px;">#</th>
                    <th style="width:120px;">Category</th>
                    <th>Question</th>
                    <th style="width:100px; text-align:center;">Views</th>
                    <th style="width:80px; text-align:center;">Status</th>
                    <th style="width:130px; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                    <tr>
                        <td class="text-muted">{{ $article->id }}</td>
                        <td>
                            <span class="badge rounded-pill" style="background:var(--primary-light);color:#fff;font-size:0.72rem;">
                                {{ $article->category }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $article->question }}</div>
                            <div class="text-muted mt-1" style="font-size:0.78rem;">
                                {{ Str::limit($article->answer, 90) }}
                            </div>
                            @if($article->keywords)
                                <div class="mt-1 d-flex flex-wrap gap-1">
                                    @foreach(array_slice(explode(',', $article->keywords), 0, 4) as $kw)
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.7rem;">{{ trim($kw) }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-eye" style="font-size:0.75rem;"></i> {{ number_format($article->view_count) }}
                            </span>
                        </td>
                        <td class="text-center">
                            @if($article->is_active)
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('superadmin.help-articles.edit', $article) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('superadmin.help-articles.destroy', $article) }}" class="d-inline"
                                  onsubmit="return confirm('Delete this help article? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-x" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                            No help articles found. <a href="{{ route('superadmin.help-articles.create') }}">Create the first one</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($articles->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing {{ $articles->firstItem() }}–{{ $articles->lastItem() }} of {{ $articles->total() }} articles</small>
            {{ $articles->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
