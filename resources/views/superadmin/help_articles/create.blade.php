@extends('layouts.app')
@section('title', 'Add Help Article — CloudKazi')
@section('breadcrumb', 'Platform Admin › AI Help Centre › Add Article')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-plus-circle text-primary"></i> Add New Help Article</h1>
        <p style="color:var(--text-muted);font-size:0.9rem;">Create a new knowledge base article. Accurate keywords improve AI matching accuracy significantly.</p>
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

<form method="POST" action="{{ route('superadmin.help-articles.store') }}" style="max-width:800px;">
    @csrf

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle text-primary"></i> Article Details</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-control" required>
                        <option value="">— Select a category —</option>
                        @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $key }} — {{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Choose the module this article belongs to.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Active (Visible in chatbot)</option>
                        <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Draft (Hidden)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Question / Title <span class="text-danger">*</span></label>
                    <input type="text" name="question" class="form-control" required
                        value="{{ old('question') }}"
                        placeholder="e.g. How do I register a new student?">
                    <div class="form-text">Write the question exactly as a user might ask it.</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Answer / Instructions <span class="text-danger">*</span></label>
                    <textarea name="answer" class="form-control" rows="7" required
                        placeholder="Provide a clear, step-by-step answer. Include navigation paths (e.g. Students > Admissions > Add Student)."
                        style="resize:vertical;">{{ old('answer') }}</textarea>
                    <div class="form-text">Plain text only. Concise, accurate, action-oriented answers work best.</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Keywords / Tags</label>
                    <input type="text" name="keywords" class="form-control"
                        value="{{ old('keywords') }}"
                        placeholder="e.g. register student, admission, enroll, new student, add learner">
                    <div class="form-text">Comma-separated keywords. More keywords = better AI match accuracy. Include synonyms and alternate phrasings.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save"></i> Save Article
        </button>
        <a href="{{ route('superadmin.help-articles.index') }}" class="btn btn-outline-secondary">
            Cancel
        </a>
    </div>
</form>
@endsection
