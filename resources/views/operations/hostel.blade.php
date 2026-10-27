@extends('layouts.app')
@section('title', 'Hostel & Boarding Management')
@section('breadcrumb', 'Operations › Hostel')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🏠 Hostel & Boarding Facility Management</h1>
        <p>Manage student dormitories, bed occupancy, boarding wardens, and room assignments</p>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-primary" onclick="document.getElementById('modal-dorm').style.display='flex'">
            <i class="bi bi-plus-circle"></i> Add Dormitory
        </button>
        <button class="btn btn-outline" onclick="document.getElementById('modal-bed').style.display='flex'">
            <i class="bi bi-person-plus"></i> Allocate Bed
        </button>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h5><i class="bi bi-house"></i> Dormitory Facilities & Capacity</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Dormitory Name</th>
                    <th>Gender</th>
                    <th>Total Capacity</th>
                    <th>Warden Name</th>
                    <th>Warden Phone</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dormitories as $d)
                <tr>
                    <td><strong style="color:var(--primary)">{{ $d->name }}</strong></td>
                    <td><span class="badge badge-{{ $d->gender === 'Male' ? 'primary' : 'teal' }}">{{ $d->gender }}</span></td>
                    <td><strong>{{ $d->capacity }}</strong> beds</td>
                    <td>{{ $d->warden_name ?: 'Unassigned' }}</td>
                    <td>{{ $d->warden_phone ?: '—' }}</td>
                    <td><span class="badge badge-success">Active</span></td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">No dormitories added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Dormitory -->
<div id="modal-dorm" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-house-add"></i> Create Dormitory / Hall</h5>
            <button onclick="document.getElementById('modal-dorm').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('hostel.dormitories.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Dormitory Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Mara House, Tsavo Hall" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Gender Allocation *</label>
                        <select name="gender" class="form-control" required>
                            <option value="Male">Boys / Male</option>
                            <option value="Female">Girls / Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Capacity (Beds) *</label>
                        <input type="number" name="capacity" class="form-control" value="80" min="1" required>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Hostel Warden Name</label>
                        <input type="text" name="warden_name" class="form-control" placeholder="e.g. Madam Grace">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Warden Phone</label>
                        <input type="text" name="warden_phone" class="form-control" placeholder="+2547...">
                    </div>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-dorm').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Dormitory</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Allocate Bed -->
<div id="modal-bed" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-person-check"></i> Allocate Student Bed</h5>
            <button onclick="document.getElementById('modal-bed').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('hostel.assign') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Student *</label>
                    <select name="student_id" class="form-control" required>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->gender }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Dormitory *</label>
                    <select name="dormitory_id" class="form-control" required>
                        @foreach($dormitories as $dm)
                            <option value="{{ $dm->id }}">{{ $dm->name }} ({{ $dm->gender }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Bed Number / Cubicle</label>
                    <input type="text" name="bed_number" class="form-control" placeholder="e.g. Bed 14 - Upper Deck">
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-bed').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Allocate Bed</button>
            </div>
        </form>
    </div>
</div>
@endsection
