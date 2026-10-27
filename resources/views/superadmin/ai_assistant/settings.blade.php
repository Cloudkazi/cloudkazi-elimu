@extends('layouts.app')
@section('title', 'AI Assistant Settings — CloudKazi')
@section('breadcrumb', 'Platform Admin › System Settings › AI Assistant')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-robot text-primary"></i> AI Assistant Settings</h1>
        <p style="color:var(--text-muted);font-size:0.9rem;">Enable or disable the public chatbot, set the welcome message, and prepare OpenAI for a later switch-over.</p>
    </div>
    <div class="page-header-actions">
        <a href="{{ route('superadmin.ai-assistant.logs') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-chat-dots"></i> View Chat Logs
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success mb-3"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger mb-3">
        @foreach($errors->all() as $err)
            <div><i class="bi bi-exclamation-circle"></i> {{ $err }}</div>
        @endforeach
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <div class="card text-center py-3">
            <div style="font-size:1.8rem;font-weight:700;color:var(--primary);">{{ number_format($totalQuestionsAsked) }}</div>
            <div style="font-size:0.82rem;color:var(--text-muted);">Questions Asked</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card text-center py-3">
            <div style="font-size:1.8rem;font-weight:700;color:var(--success);">{{ number_format($answeredFromKB) }}</div>
            <div style="font-size:0.82rem;color:var(--text-muted);">Knowledge Base Hits</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card text-center py-3">
            <div style="font-size:1.8rem;font-weight:700;color:var(--warning);">{{ number_format($fallbackCount) }}</div>
            <div style="font-size:0.82rem;color:var(--text-muted);">Unresolved</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card text-center py-3">
            <div style="font-size:1.8rem;font-weight:700;color:var(--teal);">{{ number_format($helpfulCount) }}</div>
            <div style="font-size:0.82rem;color:var(--text-muted);">Marked Helpful</div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('superadmin.ai-assistant.settings.update') }}" style="max-width:860px;">
    @csrf

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-toggle-on text-primary"></i> Widget behaviour</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Chatbot status</label>
                    <select name="chatbot_enabled" class="form-control">
                        <option value="1" {{ ($settings['chatbot_enabled'] ?? '1') == '1' ? 'selected' : '' }}>Enabled (visible on public pages)</option>
                        <option value="0" {{ ($settings['chatbot_enabled'] ?? '1') == '0' ? 'selected' : '' }}>Disabled</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Welcome message</label>
                    <textarea name="chatbot_welcome_message" class="form-control" rows="3" required maxlength="500">{{ old('chatbot_welcome_message', $settings['chatbot_welcome_message']) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Suggested question 1</label>
                    <input type="text" name="chatbot_prompt_1" class="form-control" value="{{ old('chatbot_prompt_1', $settings['chatbot_prompt_1']) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Suggested question 2</label>
                    <input type="text" name="chatbot_prompt_2" class="form-control" value="{{ old('chatbot_prompt_2', $settings['chatbot_prompt_2']) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Suggested question 3</label>
                    <input type="text" name="chatbot_prompt_3" class="form-control" value="{{ old('chatbot_prompt_3', $settings['chatbot_prompt_3']) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Suggested question 4</label>
                    <input type="text" name="chatbot_prompt_4" class="form-control" value="{{ old('chatbot_prompt_4', $settings['chatbot_prompt_4']) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-cpu text-primary"></i> Optional OpenAI (future)</h6>
        </div>
        <div class="card-body">
            <p class="text-muted" style="font-size:0.88rem;">Answers currently come from <code>help_articles</code> via <code>AIHelpService::answerQuestion()</code>. Enable OpenAI only when you are ready to fall back to the API after a knowledge-base miss.</p>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">OpenAI fallback</label>
                    <select name="openai_enabled" class="form-control">
                        <option value="0" {{ ($settings['openai_enabled'] ?? '0') == '0' ? 'selected' : '' }}>Off (knowledge base only)</option>
                        <option value="1" {{ ($settings['openai_enabled'] ?? '0') == '1' ? 'selected' : '' }}>On (after KB miss)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Model</label>
                    <input type="text" name="openai_model" class="form-control" value="{{ old('openai_model', $settings['openai_model']) }}" placeholder="gpt-4o-mini">
                </div>
                <div class="col-md-4">
                    <label class="form-label">API key</label>
                    <input type="password" name="openai_api_key" class="form-control" value="{{ old('openai_api_key', $settings['openai_api_key']) }}" autocomplete="off" placeholder="sk-...">
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-save"></i> Save AI Assistant Settings
    </button>
</form>
@endsection
