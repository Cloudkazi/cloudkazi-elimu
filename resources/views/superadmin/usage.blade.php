@extends('layouts.app')
@section('title', 'Platform Population & Capacity Usage — CloudKazi')
@section('breadcrumb', 'Platform Admin › Population Usage')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-pie-chart text-primary"></i> Tenant Population & License Usage</h1>
        <p style="color:var(--text-secondary);font-size:0.9rem;">Aggregate capacity consumption per school (Strict privacy: aggregate counts only, no student records exposed)</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>School</th>
                    <th>Package</th>
                    <th>Students (Licensed)</th>
                    <th>Students (Current)</th>
                    <th>Student Capacity %</th>
                    <th>Teachers (Licensed)</th>
                    <th>Teachers (Current)</th>
                    <th>Teacher Capacity %</th>
                    <th>Parents (Current)</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usageData as $row)
                @php
                    $sch = $row['school'];
                    $sub = $row['subscription'];
                    $u   = $row['usage'];
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:600;">{{ $sch->name }}</div>
                        <div class="text-muted font-monospace" style="font-size:0.75rem;">{{ $sch->code }}</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">{{ $sub?->package?->name ?? 'Standard' }}</span>
                    </td>
                    <td>
                        <strong>{{ $u['students']['licensed'] ?? 'Unlimited' }}</strong>
                    </td>
                    <td>
                        <span style="font-weight:600;color:var(--text-primary);">{{ number_format($u['students']['current']) }}</span>
                    </td>
                    <td>
                        @if($u['students']['licensed'])
                            <div style="font-size:0.85rem;font-weight:600;margin-bottom:2px;">
                                {{ $u['students']['percent'] }}%
                            </div>
                            <div class="progress" style="height:4px;width:90px;">
                                <div class="progress-bar {{ $u['students']['percent'] >= 90 ? 'bg-danger' : ($u['students']['percent'] >= 75 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ min(100, $u['students']['percent']) }}%"></div>
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $u['teachers']['licensed'] ?? 'Unlimited' }}</strong>
                    </td>
                    <td>
                        {{ number_format($u['teachers']['current']) }}
                    </td>
                    <td>
                        @if($u['teachers']['licensed'])
                            <div style="font-size:0.85rem;font-weight:600;margin-bottom:2px;">
                                {{ $u['teachers']['percent'] }}%
                            </div>
                            <div class="progress" style="height:4px;width:90px;">
                                <div class="progress-bar bg-success" style="width: {{ min(100, $u['teachers']['percent']) }}%"></div>
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        {{ number_format($u['parents']['current']) }}
                    </td>
                    <td style="text-align:right;">
                        <a href="{{ route('superadmin.schools.account', $sch->id) }}" class="btn btn-sm btn-outline">
                            Manage Limits
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted" style="padding:32px;">
                        No schools registered.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
