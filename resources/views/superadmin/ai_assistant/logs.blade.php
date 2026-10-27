@extends('layouts.app')
@section('title', 'Chatbot Logs — CloudKazi')
@section('breadcrumb', 'Platform Admin › System Settings › Chatbot Logs')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-chat-dots text-primary"></i> Chatbot Activity Logs</h1>
        <p style="color:var(--text-muted);font-size:0.9rem;">Every assistant question is stored for quality review. Input is sanitised; answers are matched to help articles or fallback text.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('superadmin.ai-assistant.settings') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-gear"></i> Assistant Settings
        </a>
    </div>
</div>

<form method="GET" action="{{ route('superadmin.ai-assistant.logs') }}" class="card p-3 mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label form-label-sm">Search</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Question, answer, or IP..." value="{{ $search }}">
        </div>
        <div class="col-md-3">
            <label class="form-label form-label-sm">Source</label>
            <select name="source" class="form-control form-control-sm">
                <option value="">— All sources —</option>
                @foreach(['knowledge_base' => 'Knowledge base', 'openai' => 'OpenAI', 'fallback' => 'Fallback'] as $key => $label)
                    <option value="{{ $key }}" {{ $source === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filter</button>
            <a href="{{ route('superadmin.ai-assistant.logs') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:0.86rem;">
            <thead class="table-light">
                <tr>
                    <th>When</th>
                    <th>Question</th>
                    <th>Answer</th>
                    <th>Source</th>
                    <th>Helpful</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-muted" style="white-space:nowrap;">{{ $log->created_at->format('M d, Y H:i') }}</td>
                        <td style="max-width:220px;">{{ Str::limit($log->question, 80) }}</td>
                        <td style="max-width:280px;">{{ Str::limit($log->answer, 100) }}</td>
                        <td>
                            <span class="badge {{ $log->source === 'knowledge_base' ? 'badge-success' : ($log->source === 'openai' ? 'badge-info' : 'badge-warning') }}">
                                {{ $log->source }}
                            </span>
                        </td>
                        <td>
                            @if(is_null($log->was_helpful))
                                <span class="text-muted">—</span>
                            @elseif($log->was_helpful)
                                <span class="text-success"><i class="bi bi-hand-thumbs-up"></i></span>
                            @else
                                <span class="text-danger"><i class="bi bi-hand-thumbs-down"></i></span>
                            @endif
                        </td>
                        <td class="text-muted">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No chatbot activity yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}</small>
            {{ $logs->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
