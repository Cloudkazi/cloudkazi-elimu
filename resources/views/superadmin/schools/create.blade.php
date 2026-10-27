@extends('layouts.app')
@section('title', 'Onboard Tenant School — Platform Super Admin')
@section('breadcrumb', 'Platform Admin › Onboard School')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-building-add text-primary"></i> Onboard Educational Institution</h1>
        <p style="color:var(--text-secondary);font-size:0.9rem;">Provision tenant school account, assign SaaS package tier, and set up School Administrator credentials</p>
    </div>
    <div>
        <a href="{{ route('superadmin.schools') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back to Schools
        </a>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:18px;">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card" style="max-width:840px;margin:0 auto;">
    <div class="card-header">
        <h5 style="margin:0;font-size:1.05rem;font-weight:600;"><i class="bi bi-gear-wide-connected text-primary"></i> Institution Provisioning Wizard</h5>
    </div>
    <form action="{{ route('superadmin.schools.store') }}" method="POST">
        @csrf
        <div class="card-body" style="padding:20px;">
            <h6 style="font-weight:700;color:var(--text-primary);margin-bottom:14px;border-bottom:1px solid rgba(0,0,0,0.06);padding-bottom:8px;">
                1. School Institutional Details
            </h6>
            <div class="row" style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px;margin-bottom:14px;">
                <div>
                    <label class="form-label">School Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Alliance Horizon Academy" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="form-label">School Code (Unique Identifier) <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" placeholder="e.g. AHA-001" value="{{ old('code') }}" required>
                </div>
            </div>

            <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                <div>
                    <label class="form-label">Official School Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="admin@alliancehorizon.ac.ke" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label class="form-label">Official Contact Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" placeholder="+254 700 000 000" value="{{ old('phone') }}" required>
                </div>
            </div>

            <div class="row" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:20px;">
                <div>
                    <label class="form-label">Curriculum Track <span class="text-danger">*</span></label>
                    <select name="curriculum_type" class="form-select" required>
                        <option value="CBC" {{ old('curriculum_type') == 'CBC' ? 'selected' : '' }}>CBC (Competency Based)</option>
                        <option value="8-4-4" {{ old('curriculum_type') == '8-4-4' ? 'selected' : '' }}>8-4-4 System</option>
                        <option value="Hybrid" {{ old('curriculum_type') == 'Hybrid' ? 'selected' : '' }}>Hybrid / Dual System</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">County / Location</label>
                    <input type="text" name="county" class="form-control" placeholder="e.g. Kiambu" value="{{ old('county', 'Nairobi') }}">
                </div>
                <div>
                    <label class="form-label">School Motto</label>
                    <input type="text" name="motto" class="form-control" placeholder="e.g. Strive for Excellence" value="{{ old('motto', 'Excellence in Education') }}">
                </div>
            </div>

            <h6 style="font-weight:700;color:var(--text-primary);margin-bottom:14px;border-bottom:1px solid rgba(0,0,0,0.06);padding-bottom:8px;">
                2. Subscription Package Tier
            </h6>
            <div class="mb-4">
                <label class="form-label">Select Package <span class="text-danger">*</span></label>
                <select name="package_id" class="form-select" required>
                    @foreach($packages as $pkg)
                        <option value="{{ $pkg->id }}" {{ old('package_id') == $pkg->id ? 'selected' : '' }}>
                            {{ $pkg->name }} — KES {{ number_format($pkg->annual_fee) }}/year ({{ $pkg->max_students ? $pkg->max_students . ' students' : 'Unlimited' }})
                        </option>
                    @endforeach
                </select>
                <div class="form-text" style="font-size:0.8rem;">
                    Pricing, features, trial period, and student capacity limits are strictly enforced from the package configuration.
                </div>
            </div>

            <h6 style="font-weight:700;color:var(--text-primary);margin-bottom:14px;border-bottom:1px solid rgba(0,0,0,0.06);padding-bottom:8px;">
                3. School Administrator Account Setup
            </h6>
            <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
                <div>
                    <label class="form-label">Principal / Admin Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="admin_name" class="form-control" placeholder="Dr. Jane Wanjiku" value="{{ old('admin_name') }}" required>
                </div>
                <div>
                    <label class="form-label">Administrator Login Email <span class="text-danger">*</span></label>
                    <input type="email" name="admin_email" class="form-control" placeholder="principal@alliancehorizon.ac.ke" value="{{ old('admin_email') }}" required>
                </div>
            </div>

            <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                <div>
                    <label class="form-label">Administrator Phone Number</label>
                    <input type="text" name="admin_phone" class="form-control" placeholder="07XXXXXXXX" value="{{ old('admin_phone') }}">
                </div>
                <div>
                    <label class="form-label">Initial Password <span class="text-danger">*</span></label>
                    <input type="password" name="admin_password" class="form-control" placeholder="Minimum 6 characters" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="padding:12px 24px;font-size:1rem;font-weight:600;width:100%;">
                <i class="bi bi-shield-check"></i> Provision School & Issue Credentials
            </button>
        </div>
    </form>
</div>
@endsection
