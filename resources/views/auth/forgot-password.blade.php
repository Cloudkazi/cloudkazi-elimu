@extends('layouts.guest')
@section('title', 'Forgot Password — CloudKazi Elimu')

@section('content')
<div class="auth-page">

    <!-- Left Panel (Brand + Information) -->
    <div class="auth-left">
        <div>
            <div class="auth-brand">
                <div class="auth-brand-logo">🎓</div>
                <h1>CloudKazi Elimu</h1>
                <p>School Management Information System<br>Built for Kenyan Schools</p>
            </div>

            <div class="auth-features">
                <div class="auth-feature-item">
                    <div class="feat-icon">🔑</div>
                    <div>
                        <strong>Account Recovery Assistance</strong><br>
                        <span style="opacity:.8">Self-service password resets for administrative &amp; teacher staff</span>
                    </div>
                </div>
                <div class="auth-feature-item">
                    <div class="feat-icon">👨‍👩‍👧</div>
                    <div>
                        <strong>Parents &amp; Guardians</strong><br>
                        <span style="opacity:.8">Your password is your registered mobile phone number</span>
                    </div>
                </div>
                <div class="auth-feature-item">
                    <div class="feat-icon">🎓</div>
                    <div>
                        <strong>Students</strong><br>
                        <span style="opacity:.8">Your default password is your unique admission number</span>
                    </div>
                </div>
                <div class="auth-feature-item">
                    <div class="feat-icon">🛡️</div>
                    <div>
                        <strong>Institutional Security</strong><br>
                        <span style="opacity:.8">Multi-tenant role-based access control</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel (Reset Form) -->
    <div class="auth-right">
        <div class="auth-form-header">
            <div class="form-logo">🔐</div>
            <h2>Password Recovery</h2>
            <p>Enter your email or contact your school administrator</p>
        </div>

        @if(session('status'))
            <div class="alert alert-info" style="width:100%">
                <i class="bi bi-info-circle-fill"></i>
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" style="width:100%">
                <i class="bi bi-exclamation-triangle-fill"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST" style="width:100%">
            @csrf

            <div class="form-group mb-3">
                <label class="form-label">Email Address / Staff Username</label>
                <div class="input-group">
                    <input type="text" name="email" class="form-control" id="email"
                        placeholder="e.g. teacher@school.ac.ke or staff email"
                        value="{{ old('email') }}" required autofocus
                        style="border-radius: var(--radius-sm) 0 0 var(--radius-sm)">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                </div>
            </div>

            <!-- Helpful Hints for Roles -->
            <div class="card p-3 mb-3 bg-light border-0" style="border-radius: var(--radius-sm); font-size: 0.8rem; line-height: 1.5;">
                <strong class="text-dark d-block mb-1"><i class="bi bi-lightbulb-fill text-warning"></i> Quick Credentials Reminder:</strong>
                <ul class="mb-0 ps-3 text-muted">
                    <li><strong>Parents:</strong> Password is your registered phone number (e.g. 0712345678).</li>
                    <li><strong>Students:</strong> Default password is your Admission Number.</li>
                    <li><strong>Teachers &amp; Staff:</strong> Contact your School Admin or Bursar if locked out.</li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">
                <i class="bi bi-send-check"></i> Send Reset Link
            </button>

            <div class="text-center">
                <a href="{{ route('login') }}" class="btn btn-link text-decoration-none" style="font-size:0.875rem; color:var(--primary);">
                    <i class="bi bi-arrow-left"></i> Return to Sign In
                </a>
            </div>
        </form>

        <!-- Reusable Footer Links Partial -->
        @include('partials.footer-links')
    </div>
</div>

<!-- Floating AI Chatbot Widget -->
@include('partials.ai-chatbot')
@endsection
