@extends('layouts.app')
@section('title', 'Edit Help Article — CloudKazi')
@section('breadcrumb', 'Platform Admin › AI Help Centre › Edit Article')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-pencil-square text-primary"></i> Edit Help Article</h1>
        <p style="color:var(--text-muted);font-size:0.9rem;">Update knowledge-base content used by the Elimu AI Assistant and Help Center.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('superadmin.help-articles.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Articles
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger mb-3">
        @foreach($errors->all() as $err)
            <div><i class="bi bi-exclamation-circle"></i> {{ $err }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('superadmin.help-articles.update', $article) }}" style="max-width:800px;">
    @csrf
    @method('PUT')

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle text-primary"></i> Article Details</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-control" required>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category', $article->category) === $key ? 'selected' : '' }}>{{ $key }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" {{ (string) old('is_active', $article->is_active ? '1' : '0') === '1' ? 'selected' : '' }}>Active (Visible in chatbot)</option>
                        <option value="0" {{ (string) old('is_active', $article->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>Draft (Hidden)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Question / Title <span class="text-danger">*</span></label>
                    <input type="text" name="question" class="form-control" required
                        value="{{ old('question', $article->question) }}"
                        placeholder="e.g. How do I register a new student?">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Answer / Instructions <span class="text-danger">*</span></label>
                    <textarea name="answer" class="form-control" rows="7" required
                        placeholder="Provide a clear, step-by-step answer."
                        style="resize:vertical;">{{ old('answer', $article->answer) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Keywords / Tags</label>
                    <input type="text" name="keywords" class="form-control"
                        value="{{ old('keywords', $article->keywords) }}"
                        placeholder="e.g. register student, admission, enroll">
                    <div class="form-text">Comma-separated keywords. Include synonyms for better matching.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save"></i> Update Article
        </button>
        <a href="{{ route('superadmin.help-articles.index') }}" class="btn btn-outline-secondary">
            Cancel
        </a>
    </div>
</form>
@endsection
