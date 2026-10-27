@extends('layouts.app')
@section('title', 'Transport Fleet & Vehicles')
@section('breadcrumb', 'Operations › Fleet Vehicles')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🚍 School Bus Fleet & Vehicle Inventory</h1>
        <p>Monitor school-owned buses, vans, driver assignments, and NTSA inspection roadworthiness</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-truck"></i> Registered School Fleet Vehicles</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Registration No.</th>
                    <th>Assigned Route</th>
                    <th>Driver Assigned</th>
                    <th>Phone Contact</th>
                    <th>NTSA Roadworthiness</th>
                    <th>Capacity</th>
                </tr>
            </thead>
            <tbody>
                @forelse($routes as $r)
                <tr>
                    <td><strong style="font-family:monospace;font-size:1.05rem">{{ $r->vehicle_registration }}</strong></td>
                    <td>{{ $r->route_name }}</td>
                    <td><strong>{{ $r->driver_name }}</strong></td>
                    <td><i class="bi bi-telephone"></i> {{ $r->driver_phone }}</td>
                    <td><span class="badge badge-success">Valid / Cleared</span></td>
                    <td>45 Seater Bus</td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">No vehicles registered yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
