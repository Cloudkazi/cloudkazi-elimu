@extends('layouts.app')
@section('title', 'Edit Student — ' . $student->full_name)
@section('breadcrumb', 'Students › Edit Profile')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>✏️ Edit Student Profile: {{ $student->full_name }}</h1>
        <p>Admission No: <strong>{{ $student->admission_number }}</strong> | Curriculum: <strong>{{ $student->curriculum }}</strong></p>
    </div>
    <div>
        <a href="{{ route('students.show', $student->id) }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back to Profile
        </a>
    </div>
</div>

<form action="{{ route('students.update', $student->id) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="card" style="margin-bottom:20px">
        <div class="card-header">
            <h5><i class="bi bi-person"></i> Basic & Academic Details</h5>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="first_name" class="form-control" value="{{ $student->first_name }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" value="{{ $student->last_name }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Other Names</label>
                    <input type="text" name="other_names" class="form-control" value="{{ $student->other_names }}">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
                <div class="form-group">
                    <label class="form-label">Class *</label>
                    <select name="school_class_id" class="form-control" required>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ $student->school_class_id == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->curriculum }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Stream</label>
                    <select name="stream_id" class="form-control">
                        <option value="">-- Unassigned --</option>
                        @foreach($streams as $str)
                            <option value="{{ $str->id }}" {{ $student->stream_id == $str->id ? 'selected' : '' }}>
                                {{ $str->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Curriculum Track *</label>
                    <select name="curriculum" class="form-control" required>
                        <option value="CBC" {{ $student->curriculum === 'CBC' ? 'selected' : '' }}>CBC</option>
                        <option value="8-4-4" {{ $student->curriculum === '8-4-4' ? 'selected' : '' }}>8-4-4</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label class="form-label">Fee Profile &amp; Boarding Status *</label>
                    <select name="fee_profile" class="form-control" required>
                        <option value="regular" {{ old('fee_profile', $student->fee_profile ?? 'regular') === 'regular' ? 'selected' : '' }}>Regular Day Scholar (Tuition + Other)</option>
                        <option value="boarder" {{ old('fee_profile', $student->fee_profile ?? '') === 'boarder' ? 'selected' : '' }}>Boarder (Tuition + Boarding/Hostel)</option>
                        <option value="transport" {{ old('fee_profile', $student->fee_profile ?? '') === 'transport' ? 'selected' : '' }}>Day Scholar + Transport (Tuition + Bus)</option>
                        <option value="boarder_transport" {{ old('fee_profile', $student->fee_profile ?? '') === 'boarder_transport' ? 'selected' : '' }}>Boarder + Transport (All Inclusive)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Enrollment Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="active" {{ $student->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="transferred" {{ $student->status === 'transferred' ? 'selected' : '' }}>Transferred</option>
                        <option value="suspended" {{ $student->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="alumni" {{ $student->status === 'alumni' ? 'selected' : '' }}>Alumni / Graduated</option>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label class="form-label">Senior Pathway (If applicable)</label>
                    <input type="text" name="pathway" class="form-control" value="{{ $student->pathway }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Blood Group</label>
                    <input type="text" name="blood_group" class="form-control" value="{{ $student->blood_group }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Medical Conditions / Allergies</label>
                <textarea name="medical_conditions" class="form-control" rows="2">{{ $student->medical_conditions }}</textarea>
            </div>
        </div>
        <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
            <a href="{{ route('students.show', $student->id) }}" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </div>
    </div>
</form>
@endsection
