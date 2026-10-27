@extends('layouts.app')
@section('title', 'Platform & School Administrators — CloudKazi')
@section('breadcrumb', 'Platform Admin › Platform Users')

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;"><i class="bi bi-people text-primary"></i> Platform & School Administrators</h1>
        <p style="color:var(--text-secondary);font-size:0.9rem;">Administrative contacts for platform oversight and tenant management (Private school ERP users are strictly isolated)</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-shield-lock text-primary"></i> Administrative Accounts ({{ $users->total() }})</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Institution / School</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="avatar-text avatar-sm" style="background:var(--primary);color:#fff;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <strong style="color:var(--text-primary);">{{ $u->name }}</strong>
                        </div>
                    </td>
                    <td>{{ $u->email }}</td>
                    <td>
                        @if($u->school)
                            <a href="{{ route('superadmin.schools.account', $u->school_id) }}" style="text-decoration:none;font-weight:600;">
                                {{ $u->school->name }}
                            </a>
                        @else
                            <span class="badge bg-purple text-light">CloudKazi Platform HQ</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $u->isSuperAdmin() ? 'bg-danger' : 'bg-primary' }}">
                            {{ $u->role?->name ?? 'User' }}
                        </span>
                    </td>
                    <td>{{ $u->created_at ? $u->created_at->format('d M Y') : 'N/A' }}</td>
                    <td>
                        <span class="badge {{ $u->is_active ? 'bg-success' : 'bg-danger' }}">
                            {{ $u->is_active ? 'Active' : 'Locked' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted" style="padding:24px;">No administrator users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div style="padding:16px;">{{ $users->links() }}</div>
    @endif
</div>
@endsection
