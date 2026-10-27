@extends('layouts.app')
@section('title', 'Staff & User Accounts')
@section('breadcrumb', 'Administration › Staff Accounts')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>👥 School Staff & System Accounts</h1>
        <p>Provision staff accounts, assign their portal, and monitor access status</p>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-primary" onclick="document.getElementById('add-user-modal').style.display='flex'">
            <i class="bi bi-person-plus"></i> Add New Staff Account
        </button>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">👥</div>
        <div class="stat-body">
            <div class="stat-label">Total Accounts</div>
            <div class="stat-value">{{ $totalUsers }}</div>
            <div class="stat-trend neutral">Enrolled Staff</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">🛡️</div>
        <div class="stat-body">
            <div class="stat-label">Active Users</div>
            <div class="stat-value">{{ $activeUsers }}</div>
            <div class="stat-trend up">Authorized</div>
        </div>
    </div>
    <div class="stat-card stat-indigo">
        <div class="stat-icon">👩‍🏫</div>
        <div class="stat-body">
            <div class="stat-label">Teaching Staff</div>
            <div class="stat-value">{{ $teachersCount }}</div>
            <div class="stat-trend neutral">Academic Faculty</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">💰</div>
        <div class="stat-body">
            <div class="stat-label">Finance & Ops</div>
            <div class="stat-value">{{ $financeCount }}</div>
            <div class="stat-trend neutral">Bursar & Officers</div>
        </div>
    </div>
    <div class="stat-card stat-teal">
        <div class="stat-icon">📚</div>
        <div class="stat-body">
            <div class="stat-label">Library Staff</div>
            <div class="stat-value">{{ $librariansCount }}</div>
            <div class="stat-trend neutral">Librarians</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" action="{{ route('school.users') }}" style="display:grid;grid-template-columns:2fr 1.5fr auto;gap:16px;align-items:end">
            <div class="form-group">
                <label class="form-label">Search Staff</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="{{ request('search') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Filter by Role</label>
                <select name="role_id" class="form-select">
                    <option value="">All Roles</option>
                    @foreach($roles as $r)
                    <option value="{{ $r->id }}" {{ request('role_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-person-lines-fill"></i> Staff Directory & Account Credentials</h5>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role & Portal</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Last Active</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="avatar-text avatar-sm" style="background:var(--primary)">
                                {{ strtoupper(substr($u->name, 0, 2)) }}
                            </div>
                            <div>
                                <strong style="color:var(--text-dark)">{{ $u->name }}</strong>
                                <div style="font-size:0.75rem;color:var(--text-muted)">{{ $u->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-primary">
                            {{ $u->role?->name ?? 'User' }}
                        </span>
                        <div style="font-size:0.75rem;color:var(--text-muted)">
                            {{ $portalLabels[$u->role?->slug ?? ''] ?? 'School portal' }}
                        </div>
                    </td>
                    <td>{{ $u->phone ?? '—' }}</td>
                    <td>
                        <span class="badge badge-{{ $u->is_active ? 'success' : 'danger' }}">
                            {{ $u->is_active ? 'Active' : 'Locked' }}
                        </span>
                    </td>
                    <td>{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Never logged in' }}</td>
                    <td style="text-align:right">
                        <div style="display:flex;gap:6px;justify-content:flex-end">
                            <!-- Toggle Status Form -->
                            @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('school.users.toggle-status', $u->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $u->is_active ? 'btn-outline' : 'btn-primary' }}"
                                        title="{{ $u->is_active ? 'Lock Account' : 'Activate Account' }}">
                                    <i class="bi bi-{{ $u->is_active ? 'lock' : 'unlock' }}"></i>
                                </button>
                            </form>

                            <!-- Quick Reset Password Button -->
                            <button type="button" class="btn btn-sm btn-outline"
                                    onclick="openResetModal({{ $u->id }}, '{{ addslashes($u->name) }}')"
                                    title="Reset Password">
                                <i class="bi bi-key"></i>
                            </button>
                            @else
                            <a href="{{ route('profile.change-password') }}" class="btn btn-sm btn-outline">
                                <i class="bi bi-key"></i> My Password
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">No staff accounts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer">
        {{ $users->links() }}
    </div>
    @endif
</div>

<!-- Modal: Add New Staff Account -->
<div id="add-user-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:540px;margin:20px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-person-plus"></i> Create New Staff Account</h5>
            <button type="button" onclick="document.getElementById('add-user-modal').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer">&times;</button>
        </div>
        <form method="POST" action="{{ route('school.users.store') }}">
            @csrf
            <div class="card-body">
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Samuel Mutiso" required>
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Email Address (Login Username) *</label>
                    <input type="email" name="email" class="form-control" placeholder="staff@npa.ac.ke" required>
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="+254 7...">
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Role Assignment *</label>
                    <select name="role_id" class="form-select" required>
                        <option value="">Select Role...</option>
                        @foreach($assignableRoles as $r)
                        <option value="{{ $r->id }}">{{ $r->name }} — {{ $portalLabels[$r->slug] ?? 'School portal' }}</option>
                        @endforeach
                    </select>
                    <small style="color:var(--text-muted)">Student and Parent accounts are created automatically when a student is enrolled.</small>
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Initial Password *</label>
                    <input type="password" name="password" class="form-control" required autocomplete="new-password">
                    <small style="color:var(--text-muted)">The account is ready to sign in with this password as soon as it is created.</small>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('add-user-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Create Staff Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reset Staff Password -->
<div id="reset-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:460px;margin:20px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-key"></i> Reset Staff Password</h5>
            <button type="button" onclick="document.getElementById('reset-modal').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer">&times;</button>
        </div>
        <form method="POST" id="reset-password-form">
            @csrf
            <div class="card-body">
                <p id="reset-user-text" style="font-size:0.88rem;color:var(--text-muted);margin-bottom:14px"></p>
                <div class="form-group">
                    <label class="form-label">New Password *</label>
                    <input type="text" name="new_password" class="form-control" value="Password@2026!" required>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('reset-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-arrow-repeat"></i> Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function openResetModal(userId, userName) {
    document.getElementById('reset-password-form').action = '/school/users/' + userId + '/reset-password';
    document.getElementById('reset-user-text').textContent = 'Set a new login password for ' + userName + '.';
    document.getElementById('reset-modal').style.display = 'flex';
}
</script>
@endsection
