@extends('layouts.guest')
@section('title', 'Register School — CloudKazi Elimu')

@section('content')
<div class="auth-page" style="min-height:100vh;align-items:flex-start;padding:32px 0;">
    <div class="auth-right" style="max-width:860px; width:100%; margin:0 auto;">
        <div class="auth-form-header">
            <div class="form-logo">🏫</div>
            <h2>Register Your School</h2>
            <p>Set up your institution and subscribe to CloudKazi Elimu</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success" style="width:100%;margin-bottom:16px;">
                <i class="bi bi-check-circle-fill"></i>
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" style="width:100%;margin-bottom:16px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Please correct the following:</strong>
                <ul style="margin:8px 0 0 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card" style="margin-bottom:18px; width:100%;">
            <div class="card-body" style="padding:16px;">
                <h6 style="color:var(--primary); margin-bottom:12px;"><i class="bi bi-search"></i> Resume an existing school registration</h6>
                <form action="{{ route('school.register.search') }}" method="POST" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-5">
                        <label class="form-label">School email</label>
                        <input type="email" name="search_email" class="form-control" value="{{ old('search_email') }}" placeholder="school@example.com">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">School code</label>
                        <input type="text" name="search_code" class="form-control" value="{{ old('search_code') }}" placeholder="GVA-001">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline btn-block">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <form action="{{ route('school.register.store') }}" method="POST" style="width:100%;">
            @csrf
            <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px;margin-bottom:14px;">
                <div class="form-group">
                    <label class="form-label">School Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Green Valley Academy" required>
                </div>
                <div class="form-group">
                    <label class="form-label">School Code (Unique) *</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="e.g. GVA-001" required>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                <div class="form-group">
                    <label class="form-label">Official Email *</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="admin@school.ac.ke" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+254 7..." required>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                <div class="form-group">
                    <label class="form-label">Curriculum Track *</label>
                    <select name="curriculum_type" class="form-select" required>
                        <option value="CBC" {{ old('curriculum_type') === 'CBC' ? 'selected' : '' }}>CBC</option>
                        <option value="8-4-4" {{ old('curriculum_type') === '8-4-4' ? 'selected' : '' }}>8-4-4</option>
                        <option value="Hybrid" {{ old('curriculum_type') === 'Hybrid' || old('curriculum_type') === null ? 'selected' : '' }}>Hybrid</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">County / Location</label>
                    <input type="text" name="county" class="form-control" value="{{ old('county', 'Nairobi') }}" placeholder="Nairobi">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">School Motto</label>
                <input type="text" name="motto" class="form-control" value="{{ old('motto') }}" placeholder="Knowledge is Light">
            </div>

            <hr style="margin:20px 0;border:0;border-top:1px solid #e2e8f0;">

            <h6 style="color:var(--primary);margin-bottom:14px;"><i class="bi bi-box-seam"></i> Select Subscription Package</h6>
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Subscription Tier *</label>
                <select name="package_id" id="package_id" class="form-select" required>
                    @if(isset($packages) && $packages->count() > 0)
                        @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" {{ old('package_id') == $pkg->id ? 'selected' : '' }}>
                                {{ $pkg->name }} — KES {{ number_format($pkg->annual_fee) }}/year 
                                ({{ $pkg->max_students ? number_format($pkg->max_students) . ' students' : 'Unlimited students' }}, {{ $pkg->trial_days > 0 ? $pkg->trial_days . ' days free trial' : 'Immediate payment' }})
                            </option>
                        @endforeach
                    @else
                        @php
                            $defaultPackages = \App\Models\Package::where('is_active', true)->where('is_public', true)->get();
                        @endphp
                        @foreach($defaultPackages as $pkg)
                            <option value="{{ $pkg->id }}">
                                {{ $pkg->name }} — KES {{ number_format($pkg->annual_fee) }}/year ({{ $pkg->max_students ? $pkg->max_students . ' students' : 'Unlimited' }})
                            </option>
                        @endforeach
                    @endif
                </select>
                <div class="form-text" style="font-size:0.8rem;">
                    Pricing, features, trial periods, and capacity are verified securely against platform package tiers.
                </div>
            </div>

            <hr style="margin:20px 0;border:0;border-top:1px solid #e2e8f0;">

            <h6 style="color:var(--primary);margin-bottom:14px;"><i class="bi bi-person-badge"></i> School Administrator Credentials</h6>
            <p class="form-text">After registration, the administrator can sign in with the email address below as their username and the password they set here.</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="admin_name" class="form-control" value="{{ old('admin_name') }}" placeholder="e.g. Dr. Jane Admin" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Login Email *</label>
                    <input type="email" name="admin_email" class="form-control" value="{{ old('admin_email') }}" placeholder="principal@school.ac.ke" required>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                <div class="form-group">
                    <label class="form-label">Mobile Number</label>
                    <input type="text" name="admin_phone" class="form-control" value="{{ old('admin_phone') }}" placeholder="+254 7...">
                </div>
                <div class="form-group">
                    <label class="form-label">Initial Password *</label>
                    <input type="password" name="admin_password" class="form-control" placeholder="Minimum 6 characters" required>
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px; margin-top:18px;">
                <a href="{{ route('login') }}" class="btn btn-outline">Back to login</a>
                <button type="submit" class="btn btn-primary" style="padding:10px 24px;">
                    <i class="bi bi-check-circle"></i> Complete Registration
                </button>
            </div>
        </form>

        <!-- Footer Links Partial -->
        @include('partials.footer-links')
    </div>
</div>

<!-- Floating AI Chatbot Widget -->
@include('partials.ai-chatbot')
@endsection
