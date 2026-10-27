@extends('layouts.app')
@section('title', 'Import Students CSV')
@section('breadcrumb', 'Students › Bulk CSV Import')

@section('content')

<!-- Header Card -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;flex-wrap:wrap;gap:12px">
        <div style="display:flex;align-items:center;gap:14px">
            <div style="width:48px;height:48px;border-radius:12px;background:var(--primary-color,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0">
                📥
            </div>
            <div>
                <h2 style="margin:0;font-size:1.25rem;font-weight:700;color:var(--text-primary,#1e293b)">
                    Bulk Student Onboarding
                </h2>
                <p style="margin:2px 0 0;font-size:0.875rem;color:var(--text-muted,#64748b)">
                    Onboard new students rapidly into your institution using a standardized CSV template
                </p>
            </div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="{{ route('students.import.template') }}" class="btn btn-outline" id="download-template-btn">
                <i class="bi bi-download"></i> Download CSV Template
            </a>
            <a href="{{ route('students.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Cancel
            </a>
        </div>
    </div>
</div>

@php
    $res = session('importResult') ?? ($importResult ?? null);
@endphp

@if($res)
<!-- Import Results Card -->
<div class="card" style="margin-bottom:24px; border:1px solid {{ $res['failed'] > 0 ? 'rgba(239,68,68,0.4)' : 'rgba(16,185,129,0.4)' }};">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h5>
            <i class="bi {{ $res['failed'] > 0 ? 'bi-exclamation-circle-fill text-warning' : 'bi-check-circle-fill text-success' }}"></i>
            Student Import Summary
        </h5>
        <span class="badge {{ $res['imported'] > 0 ? 'bg-success' : 'bg-danger' }}">
            {{ $res['imported'] }} / {{ $res['total'] }} Succeeded
        </span>
    </div>
    <div class="card-body">
        <div class="stats-grid stats-grid-3" style="margin-bottom:16px;">
            <div class="stat-card stat-blue">
                <div class="stat-icon">📄</div>
                <div class="stat-body">
                    <div class="stat-label">Total Rows Evaluated</div>
                    <div class="stat-value">{{ $res['total'] }}</div>
                </div>
            </div>
            <div class="stat-card stat-green">
                <div class="stat-icon">✅</div>
                <div class="stat-body">
                    <div class="stat-label">Successfully Imported</div>
                    <div class="stat-value">{{ $res['imported'] }}</div>
                    <div class="stat-trend up">Records created</div>
                </div>
            </div>
            <div class="stat-card stat-orange">
                <div class="stat-icon">⚠️</div>
                <div class="stat-body">
                    <div class="stat-label">Failed Rows</div>
                    <div class="stat-value">{{ $res['failed'] }}</div>
                    <div class="stat-trend down">Skipped due to errors</div>
                </div>
            </div>
        </div>

        @if(!empty($res['failed_rows']))
        <h6 style="font-weight:700; margin-top:16px; margin-bottom:10px; color:var(--text-primary,#1e293b);">
            <i class="bi bi-x-circle text-danger"></i> Failed Rows Detail
        </h6>
        <div class="table-responsive">
            <table class="table table-bordered table-sm" style="font-size:0.875rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px">Row #</th>
                        <th style="width:160px">Admission Number</th>
                        <th>Failure Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($res['failed_rows'] as $f)
                    <tr>
                        <td class="text-center font-monospace"><strong>{{ $f['row'] }}</strong></td>
                        <td class="font-monospace">{{ $f['admission_number'] }}</td>
                        <td class="text-danger">{{ $f['reason'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endif

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; align-items:start;">

    <!-- Left Column: Upload Form -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-file-earmark-arrow-up"></i> Upload Student CSV</h5>
        </div>
        <div class="card-body">
            @if(auth()->user()?->isDemoAccount())
            <div class="alert alert-warning" style="margin-bottom:18px;">
                <i class="bi bi-lock-fill"></i> <strong>Read-Only Demo Mode:</strong> File upload and database imports are protected and disabled for demo accounts.
            </div>
            @endif

            <form action="{{ route('students.import.process') }}" method="POST" enctype="multipart/form-data" id="student-csv-form">
                @csrf

                <div class="mb-3">
                    <label class="form-label" style="font-weight:600;">Choose CSV File <span style="color:var(--danger,#ef4444)">*</span></label>
                    <input type="file" name="csv_file" class="form-control @error('csv_file') is-invalid @enderror" accept=".csv,text/csv,text/plain" required {{ auth()->user()?->isDemoAccount() ? 'disabled' : '' }}>
                    @error('csv_file')
                        <div class="invalid-feedback" style="display:block">{{ $message }}</div>
                    @enderror
                    <div class="form-text" style="font-size:0.8rem; color:var(--text-muted,#64748b); margin-top:6px;">
                        Supported format: <strong>.csv</strong> (Max size: 5MB). File must follow the standard column layout.
                    </div>
                </div>

                <div style="display:flex; gap:10px; margin-top:24px;">
                    <button type="submit" class="btn btn-primary" id="btn-submit-import" {{ auth()->user()?->isDemoAccount() ? 'disabled' : '' }}>
                        <i class="bi bi-cloud-arrow-up"></i> Import Students
                    </button>
                    <a href="{{ route('students.index') }}" class="btn btn-outline">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Column: Guidelines & Classes Reference -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-info-circle"></i> Instructions & Reference</h5>
        </div>
        <div class="card-body" style="font-size:0.875rem;">
            <p style="margin-bottom:12px;">
                Ensure your CSV spreadsheet conforms to the following rules before uploading:
            </p>

            <ul style="padding-left:18px; margin-bottom:16px; line-height:1.7;">
                <li><strong>Tenant Isolation:</strong> All imported students are automatically and securely assigned to your current school institution.</li>
                <li><strong>Unique Admission Numbers:</strong> Duplicate admission numbers within your school or within the file are detected and rejected.</li>
                <li><strong>Required Columns:</strong> <code>admission_number</code>, <code>first_name</code>, <code>last_name</code>, <code>gender</code>, <code>dob</code>, <code>class</code>, <code>guardian_name</code>, <code>guardian_phone</code>, and <code>guardian_email</code>.</li>
                <li><strong>Gender Values:</strong> <code>Male</code>, <code>Female</code>, or <code>Other</code>.</li>
                <li><strong>Date Format:</strong> <code>YYYY-MM-DD</code> (e.g., 2015-05-12).</li>
                <li><strong>Portals Generated:</strong> Student and Parent portal accounts are created automatically. Students sign in with their admission number and guardian phone; parents sign in with their email and phone number.</li>
            </ul>

            <h6 style="font-weight:700; margin-top:16px; margin-bottom:8px;">Valid Classes in Your School</h6>
            <div style="display:flex; flex-wrap:wrap; gap:6px;">
                @forelse($classes as $c)
                    <span class="badge bg-light text-dark font-monospace" style="border:1px solid #cbd5e1; padding:5px 8px;">
                        {{ $c->name }}
                        @if($c->streams->isNotEmpty())
                            ({{ $c->streams->pluck('name')->implode(', ') }})
                        @endif
                    </span>
                @empty
                    <span class="text-muted">No classes created yet. Please create classes first under Academics.</span>
                @endforelse
            </div>
        </div>
    </div>

</div>

@endsection
