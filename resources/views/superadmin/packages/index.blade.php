@extends('layouts.app')
@section('title', 'Subscription Packages — CloudKazi')
@section('breadcrumb', 'Platform Admin › Packages & Tiers')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-box-seam text-primary"></i> SaaS Subscription Packages</h1>
        <p style="color:var(--text-secondary);font-size:0.9rem;">Define subscription tiers, pricing, population limits, and included functional modules</p>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="{{ route('superadmin.packages.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Package
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:18px;">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="row" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(340px, 1fr));gap:20px;">
    @forelse($packages as $pkg)
    <div class="card" style="display:flex;flex-direction:column;border-top:4px solid {{ $pkg->is_active ? 'var(--primary)' : 'var(--text-muted)' }};">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:flex-start;background:none;">
            <div>
                <h4 style="margin:0;font-size:1.25rem;font-weight:700;">{{ $pkg->name }}</h4>
                <span class="badge bg-light text-dark font-monospace" style="font-size:0.75rem;">{{ $pkg->code }}</span>
                @if(!$pkg->is_public)
                    <span class="badge bg-secondary" style="font-size:0.7rem;">Private</span>
                @endif
            </div>
            <div>
                @if($pkg->is_active)
                    <span class="badge bg-success">Active</span>
                @else
                    <span class="badge bg-secondary">Inactive</span>
                @endif
            </div>
        </div>

        <div class="card-body" style="padding:16px;flex:1;">
            <p style="color:var(--text-secondary);font-size:0.85rem;min-height:38px;">
                {{ $pkg->description ?? 'No description provided.' }}
            </p>

            <!-- Pricing -->
            <div style="background:var(--bg-light, #f8f9fa);padding:12px;border-radius:8px;margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;align-items:baseline;">
                    <span style="font-size:1.4rem;font-weight:700;color:var(--text-primary);">
                        KES {{ number_format($pkg->annual_fee) }}
                    </span>
                    <span style="font-size:0.8rem;color:var(--text-secondary);">/ Year</span>
                </div>
                <div style="font-size:0.8rem;color:var(--text-muted);margin-top:2px;">
                    Activation Fee: <strong>KES {{ number_format($pkg->activation_fee) }}</strong>
                </div>
            </div>

            <!-- Population Limits -->
            <div style="margin-bottom:16px;">
                <div style="font-size:0.8rem;font-weight:600;text-transform:uppercase;color:var(--text-muted);margin-bottom:6px;">
                    Licensed Capacity
                </div>
                <div style="display:flex;gap:12px;font-size:0.85rem;">
                    <div><i class="bi bi-mortarboard text-primary"></i> <strong>{{ $pkg->max_students ? number_format($pkg->max_students) : 'Unlimited' }}</strong> Students</div>
                    <div><i class="bi bi-person-workspace text-success"></i> <strong>{{ $pkg->max_teachers ? number_format($pkg->max_teachers) : 'Unlimited' }}</strong> Staff</div>
                </div>
            </div>

            <!-- Lifecycle rules -->
            <div style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:16px;">
                <i class="bi bi-clock"></i> Trial: <strong>{{ $pkg->trial_days }} days</strong> • Grace Period: <strong>{{ $pkg->grace_period_days }} days</strong>
            </div>

            <!-- Included Features -->
            <div>
                <div style="font-size:0.8rem;font-weight:600;text-transform:uppercase;color:var(--text-muted);margin-bottom:8px;">
                    Included Modules
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;font-size:0.8rem;">
                    @foreach($allFeatures as $fk => $flabel)
                        @php $hasFeature = $pkg->isFeatureEnabled($fk); @endphp
                        <div style="color:{{ $hasFeature ? 'var(--text-primary)' : 'var(--text-muted)' }};">
                            @if($hasFeature)
                                <i class="bi bi-check2 text-success"></i> <span>{{ $flabel }}</span>
                            @else
                                <i class="bi bi-x text-muted" style="opacity:0.6;"></i> <span style="opacity:0.6;text-decoration:line-through;">{{ $flabel }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card-footer" style="background:none;border-top:1px solid rgba(0,0,0,0.06);padding:12px 16px;display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;gap:6px;">
                <a href="{{ route('superadmin.packages.edit', $pkg->id) }}" class="btn btn-sm btn-outline">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <form method="POST" action="{{ route('superadmin.packages.duplicate', $pkg->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline" title="Clone this package">
                        <i class="bi bi-copy"></i>
                    </button>
                </form>
            </div>
            <form method="POST" action="{{ route('superadmin.packages.toggle', $pkg->id) }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-sm {{ $pkg->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                    {{ $pkg->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>
    @empty
    <div style="grid-column:1/-1;" class="card text-center text-muted" style="padding:48px;">
        <i class="bi bi-box" style="font-size:2.5rem;"></i>
        <p style="margin-top:12px;">No packages configured yet. Create your first subscription package.</p>
        <a href="{{ route('superadmin.packages.create') }}" class="btn btn-primary" style="margin:0 auto;width:fit-content;">Create Package</a>
    </div>
    @endforelse
</div>
@endsection
