@extends('layouts.app')
@section('title', 'Edit Privacy Policy — CloudKazi')
@section('breadcrumb', 'Platform Admin › Legal Compliance › Privacy Policy')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-shield-lock text-primary"></i> Privacy Policy Editor</h1>
        <p style="color:var(--text-muted);font-size:0.9rem;">Edit the public Privacy Policy displayed on the CloudKazi Elimu platform. Changes take effect immediately.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('privacy.policy') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-eye"></i> Preview Public Page
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-3"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('superadmin.legal.privacy.update') }}" style="max-width:900px;">
    @csrf

    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Document Title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" required
                value="{{ old('title', $document->title ?? 'CloudKazi Elimu Privacy Policy') }}"
                placeholder="e.g. CloudKazi Elimu Privacy Policy">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Version</label>
            <input type="text" name="version" class="form-control"
                value="{{ old('version', $document->version ?? '2.1') }}" placeholder="e.g. 2.1">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Status</label>
            <select name="is_active" class="form-control">
                <option value="1" {{ old('is_active', $document->is_active ?? true) ? 'selected' : '' }}>Active (Published)</option>
                <option value="0" {{ !old('is_active', $document->is_active ?? true) ? 'selected' : '' }}>Draft (Hidden)</option>
            </select>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-file-richtext text-primary"></i>
            <h6 class="mb-0 fw-semibold">Policy Content (HTML)</h6>
            <span class="badge bg-info ms-auto">HTML Supported</span>
        </div>
        <div class="card-body p-0">
            <div class="p-3 border-bottom bg-light d-flex gap-2 flex-wrap align-items-center" style="font-size:0.82rem;">
                <span class="text-muted fw-semibold">Formatting hints:</span>
                <code>&lt;h2&gt;</code> <code>&lt;h3&gt;</code> <code>&lt;p&gt;</code> <code>&lt;ul&gt;&lt;li&gt;</code> <code>&lt;strong&gt;</code> <code>&lt;a href=""&gt;</code>
            </div>
            <textarea name="content" id="policy-content" class="form-control border-0 rounded-0" rows="30"
                placeholder="Enter full HTML content for the Privacy Policy..."
                style="font-family: 'Courier New', monospace; font-size:0.82rem; resize:vertical; min-height:500px;">{{ old('content', $document->content ?? '') }}</textarea>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger mb-3">
            @foreach($errors->all() as $err)
                <div><i class="bi bi-exclamation-circle"></i> {{ $err }}</div>
            @endforeach
        </div>
    @endif

    <div class="d-flex gap-2 align-items-center">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save"></i> Save Privacy Policy
        </button>
        <a href="{{ route('privacy.policy') }}" target="_blank" class="btn btn-outline-secondary">
            <i class="bi bi-box-arrow-up-right"></i> View Live Page
        </a>
        <span class="text-muted ms-2" style="font-size:0.8rem;">
            @if($document)
                Last updated: {{ $document->updated_at->format('M d, Y H:i') }}
            @endif
        </span>
    </div>
</form>
@endsection
