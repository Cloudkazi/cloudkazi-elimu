@extends('layouts.app')
@section('title', 'Change Password')
@section('breadcrumb', 'Settings › Change Password')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🔐 Change Account Password</h1>
        <p>Update your personal login password to ensure security</p>
    </div>
</div>

<div class="card" style="max-width:540px;margin:0 auto">
    <div class="card-header">
        <h5><i class="bi bi-shield-lock"></i> Security Credentials</h5>
    </div>
    <form method="POST" action="{{ route('profile.change-password.update') }}">
        @csrf
        <div class="card-body">
            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Current Password <span style="color:red">*</span></label>
                <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
            </div>

            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">New Password <span style="color:red">*</span></label>
                <input type="password" name="password" class="form-control" required placeholder="Minimum 6 characters">
            </div>

            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Confirm New Password <span style="color:red">*</span></label>
                <input type="password" name="password_confirmation" class="form-control" required placeholder="Repeat new password">
            </div>
        </div>
        <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
            <a href="{{ url()->previous() }}" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> Save New Password
            </button>
        </div>
    </form>
</div>
@endsection
