@extends('layouts.app')
@section('title', 'Platform Audit Logs — CloudKazi')
@section('breadcrumb', 'Platform Admin › Audit Trail')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-shield-check text-primary"></i> Platform Audit Trail</h1>
        <p style="color:var(--text-secondary);font-size:0.9rem;">Trace administrative actions, subscription lifecycle events, and feature changes (Credentials redacted)</p>
    </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;padding:16px;">
    <form method="GET" action="{{ route('superadmin.audit-logs') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <div style="min-width:180px;">
            <select name="school_id" class="form-select">
                <option value="">All Schools</option>
                @foreach($schools as $sch)
                    <option value="{{ $sch->id }}" {{ request('school_id') == $sch->id ? 'selected' : '' }}>{{ $sch->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:140px;">
            <select name="module" class="form-select">
                <option value="">All Modules</option>
                @foreach($modules as $mod)
                    <option value="{{ $mod }}" {{ request('module') == $mod ? 'selected' : '' }}>{{ ucfirst($mod) }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:160px;">
            <input type="text" name="action" class="form-control" placeholder="Action keyword..." value="{{ request('action') }}">
        </div>
        <div style="display:flex;gap:6px;align-items:center;">
            <input type="date" name="from" class="form-control" value="{{ request('from') }}" title="From Date">
            <span class="text-muted">to</span>
            <input type="date" name="to" class="form-control" value="{{ request('to') }}" title="To Date">
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
        @if(request()->hasAny(['school_id', 'module', 'action', 'from', 'to']))
            <a href="{{ route('superadmin.audit-logs') }}" class="btn btn-outline">Reset</a>
        @endif
    </form>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>School</th>
                    <th>Description</th>
                    <th>User</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td style="font-size:0.8rem;white-space:nowrap;">
                        <div>{{ $log->created_at ? $log->created_at->format('d M Y, H:i:s') : 'N/A' }}</div>
                        <div class="text-muted" style="font-size:0.75rem;">{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark font-monospace" style="font-size:0.8rem;">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($log->module ?? 'general') }}</span>
                    </td>
                    <td>
                        @if($log->school)
                            <strong>{{ $log->school->name }}</strong>
                        @else
                            <span class="text-muted">Platform / Global</span>
                        @endif
                    </td>
                    <td style="font-size:0.85rem;max-width:320px;">
                        <div>{{ $log->description }}</div>
                        @if($log->new_values)
                            <details style="margin-top:4px;">
                                <summary style="font-size:0.75rem;color:var(--primary);cursor:pointer;">View changes</summary>
                                <pre style="font-size:0.7rem;background:var(--bg-light,#f8f9fa);padding:6px;border-radius:4px;margin-top:4px;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                            </details>
                        @endif
                    </td>
                    <td style="font-size:0.85rem;">
                        {{ $log->user?->name ?? 'System' }}
                    </td>
                    <td class="font-monospace text-muted" style="font-size:0.75rem;">
                        {{ $log->ip_address ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding:32px;">
                        <i class="bi bi-shield" style="font-size:2rem;"></i>
                        <p style="margin-top:8px;">No audit trail events found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div style="padding:16px;">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
