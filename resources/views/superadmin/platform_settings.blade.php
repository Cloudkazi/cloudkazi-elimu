@extends('layouts.app')
@section('title', 'Platform Settings — CloudKazi')
@section('breadcrumb', 'Platform Admin › Platform Settings')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-sliders text-primary"></i> Platform Global Settings</h1>
        <p style="color:var(--text-secondary);font-size:0.9rem;">Global SaaS defaults, platform support contact info, and system branding</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:18px;">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('superadmin.settings.update') }}">
    @csrf
    <div style="max-width:700px;">
        <!-- General Platform Info -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-globe text-primary"></i> Platform Identity & Support</h5>
            </div>
            <div class="card-body" style="padding:16px;">
                <div class="mb-3">
                    <label class="form-label">Platform Name</label>
                    <input type="text" name="platform_name" class="form-control" value="{{ $settings['platform_name'] ?? 'CloudKazi Elimu' }}">
                </div>
                <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label class="form-label">Support Email</label>
                        <input type="email" name="support_email" class="form-control" value="{{ $settings['support_email'] ?? 'support@cloudkazi.com' }}">
                    </div>
                    <div>
                        <label class="form-label">Support Phone</label>
                        <input type="text" name="support_phone" class="form-control" value="{{ $settings['support_phone'] ?? '+254 700 000 000' }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- Default SaaS Terms -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-clock-history text-success"></i> Default SaaS Subscription Terms</h5>
            </div>
            <div class="card-body" style="padding:16px;">
                <div class="row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                    <div>
                        <label class="form-label">Default Currency</label>
                        <input type="text" name="default_currency" class="form-control" value="{{ $settings['default_currency'] ?? 'KES' }}">
                    </div>
                    <div>
                        <label class="form-label">Default Trial Period (Days)</label>
                        <input type="number" name="default_trial_days" class="form-control" value="{{ $settings['default_trial_days'] ?? 30 }}">
                    </div>
                    <div>
                        <label class="form-label">Default Grace Period (Days)</label>
                        <input type="number" name="default_grace_days" class="form-control" value="{{ $settings['default_grace_days'] ?? 14 }}">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
            <i class="bi bi-check2-circle"></i> Save Settings
        </button>
    </div>
</form>
@endsection
