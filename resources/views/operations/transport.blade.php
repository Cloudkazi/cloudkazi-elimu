@extends('layouts.app')
@section('title', 'Transport Routes & Fleet')
@section('breadcrumb', 'Operations › Transport')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🚌 School Bus Routes & Student Transport</h1>
        <p>Manage pickup zones, driver contacts, vehicle registrations, and termly fares</p>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-primary" onclick="document.getElementById('modal-route').style.display='flex'">
            <i class="bi bi-plus-circle"></i> Add Route
        </button>
        <button class="btn btn-outline" onclick="document.getElementById('modal-assign').style.display='flex'">
            <i class="bi bi-person-plus"></i> Allocate Student
        </button>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h5><i class="bi bi-geo-alt"></i> Active Transport Routes</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Route Name / Zones</th>
                    <th>Bus Reg No.</th>
                    <th>Driver Name</th>
                    <th>Driver Phone</th>
                    <th>Termly Fare (KES)</th>
                    <th>Allocated Students</th>
                </tr>
            </thead>
            <tbody>
                @forelse($routes as $r)
                <tr>
                    <td><strong style="color:var(--primary)">{{ $r->route_name }}</strong></td>
                    <td><span class="badge badge-gray" style="font-family:monospace">{{ $r->vehicle_registration }}</span></td>
                    <td>{{ $r->driver_name }}</td>
                    <td><i class="bi bi-telephone"></i> {{ $r->driver_phone }}</td>
                    <td><strong>KES {{ number_format($r->termly_fare, 2) }}</strong></td>
                    <td><span class="badge badge-primary">{{ $r->students_count }} students</span></td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">No transport routes configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Route -->
<div id="modal-route" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-bus-front"></i> Create Transport Route</h5>
            <button onclick="document.getElementById('modal-route').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('transport.routes.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Route Name *</label>
                    <input type="text" name="route_name" class="form-control" placeholder="e.g. Route 1: Kilimani - Ngong Road" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Vehicle Registration Number *</label>
                    <input type="text" name="vehicle_registration" class="form-control" placeholder="e.g. KDA 123X" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Driver Name *</label>
                        <input type="text" name="driver_name" class="form-control" placeholder="e.g. John Mwangi" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Driver Phone *</label>
                        <input type="text" name="driver_phone" class="form-control" placeholder="+2547..." required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Termly Fare (KES) *</label>
                    <input type="number" name="termly_fare" class="form-control" placeholder="e.g. 12000" min="0" required>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-route').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Route</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Assign Student -->
<div id="modal-assign" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-person-check"></i> Assign Student to Transport Route</h5>
            <button onclick="document.getElementById('modal-assign').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('transport.assign') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Student *</label>
                    <select name="student_id" class="form-control" required>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->admission_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Route *</label>
                    <select name="transport_route_id" class="form-control" required>
                        @foreach($routes as $rt)
                            <option value="{{ $rt->id }}">{{ $rt->route_name }} ({{ $rt->vehicle_registration }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Pickup Point / Stage *</label>
                    <input type="text" name="pickup_point" class="form-control" placeholder="e.g. Adams Arcade Stage" required>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-assign').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Allocate</button>
            </div>
        </form>
    </div>
</div>
@endsection
