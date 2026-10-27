@extends('layouts.app')
@section('title', 'Edit Package: ' . $package->name)
@section('breadcrumb', 'Platform Admin › Packages › Edit')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-pencil-square text-primary"></i> Edit Package: {{ $package->name }}</h1>
        <p style="color:var(--text-secondary);font-size:0.9rem;">Code: <strong>{{ $package->code }}</strong> • Update pricing, terms, capacity limits, and included modules</p>
    </div>
    <a href="{{ route('superadmin.packages') }}" class="btn btn-outline">
        <i class="bi bi-arrow-left"></i> Back to Packages
    </a>
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

<form method="POST" action="{{ route('superadmin.packages.update', $package->id) }}">
    @csrf
    @method('PUT')
    <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:24px;">
        <!-- Left: Core Configuration -->
        <div>
            <!-- Tier Identity -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-tag text-primary"></i> Package Identity</h5>
                </div>
                <div class="card-body" style="padding:16px;">
                    <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                        <div>
                            <label class="form-label">Package Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $package->name) }}" required>
                        </div>
                        <div>
                            <label class="form-label">Package Code</label>
                            <input type="text" class="form-control font-monospace" value="{{ $package->code }}" disabled>
                            <div class="form-text" style="font-size:0.75rem;">Package code cannot be altered once created.</div>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $package->description) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Pricing & Terms -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-currency-dollar text-success"></i> Pricing & Lifecycle Terms</h5>
                </div>
                <div class="card-body" style="padding:16px;">
                    <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                        <div>
                            <label class="form-label">Annual Subscription Fee (KES) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="annual_fee" class="form-control" value="{{ old('annual_fee', $package->annual_fee) }}" required>
                        </div>
                        <div>
                            <label class="form-label">One-time Activation Fee (KES) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="activation_fee" class="form-control" value="{{ old('activation_fee', $package->activation_fee) }}" required>
                        </div>
                    </div>
                    <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div>
                            <label class="form-label">Free Trial Period (Days) <span class="text-danger">*</span></label>
                            <input type="number" name="trial_days" class="form-control" value="{{ old('trial_days', $package->trial_days) }}" required>
                        </div>
                        <div>
                            <label class="form-label">Post-Expiry Grace Period (Days) <span class="text-danger">*</span></label>
                            <input type="number" name="grace_period_days" class="form-control" value="{{ old('grace_period_days', $package->grace_period_days) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Population Limits -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-people text-info"></i> Population Capacity Limits</h5>
                </div>
                <div class="card-body" style="padding:16px;">
                    <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:12px;">
                        Leave blank for unlimited capacity.
                    </p>
                    <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div>
                            <label class="form-label">Max Students</label>
                            <input type="number" name="max_students" class="form-control" value="{{ old('max_students', $package->max_students) }}">
                        </div>
                        <div>
                            <label class="form-label">Max Teachers</label>
                            <input type="number" name="max_teachers" class="form-control" value="{{ old('max_teachers', $package->max_teachers) }}">
                        </div>
                        <div>
                            <label class="form-label">Max Parents</label>
                            <input type="number" name="max_parents" class="form-control" value="{{ old('max_parents', $package->max_parents) }}">
                        </div>
                        <div>
                            <label class="form-label">Max Administrators</label>
                            <input type="number" name="max_admins" class="form-control" value="{{ old('max_admins', $package->max_admins) }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Modules & Publishing -->
        <div>
            <!-- Included Functional Modules -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-toggles text-primary"></i> Included Modules</h5>
                </div>
                <div class="card-body" style="padding:16px;">
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        @foreach($allFeatures as $key => $label)
                        @php $isEnabled = $package->isFeatureEnabled($key); @endphp
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:0.9rem;">
                            <input type="checkbox" name="features[]" value="{{ $key }}" class="feat-check" style="width:18px;height:18px;" {{ $isEnabled ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Visibility & Status -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-eye text-primary"></i> Publishing & Sorting</h5>
                </div>
                <div class="card-body" style="padding:16px;">
                    <div class="mb-3">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                            <input type="checkbox" name="is_public" value="1" style="width:18px;height:18px;" {{ old('is_public', $package->is_public) ? 'checked' : '' }}>
                            <span>Publicly visible on registration portal</span>
                        </label>
                    </div>
                    <div class="mb-3">
                        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1" style="width:18px;height:18px;" {{ old('is_active', $package->is_active) ? 'checked' : '' }}>
                            <span>Active (available for assignment)</span>
                        </label>
                    </div>
                    <div>
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $package->sort_order) }}">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;padding:12px;font-size:1rem;font-weight:600;">
                <i class="bi bi-check2-circle"></i> Update Package
            </button>
        </div>
    </div>
</form>
@endsection
