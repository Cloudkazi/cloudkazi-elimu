@extends('layouts.guest')
@section('title', 'School Payment')

@section('content')
<div class="auth-page" style="min-height:100vh;align-items:center;">
    <div class="auth-right" style="max-width:680px; width:100%;">
        <div class="auth-form-header">
            <div class="form-logo">💳</div>
            <h2>Complete Subscription</h2>
            <p>Pay for your chosen school package</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="width:100%">
                <i class="bi bi-check-circle-fill"></i>
                {{ session('success') }}
            </div>
        @endif

        <div class="card" style="margin-top: 12px;">
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px; margin-bottom:8px;">
                    <div>
                        <div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">School</div>
                        <div style="font-size:1.15rem; font-weight:800;">{{ $school->name }}</div>
                    </div>
                    <span class="badge badge-success">Active registration</span>
                </div>

                <div style="background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:16px; margin-top:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center; margin-bottom:8px;">
                        <span style="color:var(--text-muted)">Package</span>
                        <strong>{{ $school->subscription?->plan_name ?? 'Standard' }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center; margin-bottom:8px;">
                        <span style="color:var(--text-muted)">Annual Amount</span>
                        <strong>KES {{ number_format((float) $amount, 2) }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="color:var(--text-muted)">Access</span>
                        <strong>School admin portal enabled</strong>
                    </div>
                </div>

                <div style="margin-top:18px; display:grid; gap:12px;">
                    <form action="{{ route('school.payment.mpesa', ['school' => $school->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="amount" value="{{ number_format((float) $amount, 2, '.', '') }}">
                        <div class="form-group">
                            <label class="form-label">Phone Number for M-Pesa *</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. 0712345678" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-wallet2"></i> Pay via M-Pesa
                        </button>
                    </form>

                    <form action="{{ route('school.payment.paystack', ['school' => $school->id]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="amount" value="{{ number_format((float) $amount, 2, '.', '') }}">
                        <div class="form-group">
                            <label class="form-label">Email for Paystack receipt *</label>
                            <input type="email" name="email" class="form-control" placeholder="admin@school.ac.ke" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-credit-card"></i> Pay via Paystack
                        </button>
                    </form>

                    <a href="{{ route('login') }}" class="btn btn-outline btn-lg">
                        <i class="bi bi-arrow-left"></i> Back to login
                    </a>
                </div>
            </div>
        </div>

        @include('partials.footer-links')
    </div>
</div>

@include('partials.ai-chatbot')
@endsection
