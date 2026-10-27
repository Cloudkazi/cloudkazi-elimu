@extends('layouts.app')
@section('title', 'Inventory & Assets')
@section('breadcrumb', 'Operations › Inventory')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📦 Institutional Asset Register & Inventory</h1>
        <p>Track school equipment, science lab inventory, ICT lab computers, furniture, and fixed assets</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="document.getElementById('modal-asset').style.display='flex'">
            <i class="bi bi-plus-circle"></i> Register Asset
        </button>
    </div>
</div>

<div class="stats-grid stats-grid-2" style="margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">🏷️</div>
        <div class="stat-body">
            <div class="stat-label">Total Asset Items</div>
            <div class="stat-value">{{ $totalCount }} Assets</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">💰</div>
        <div class="stat-body">
            <div class="stat-label">Total Asset Valuation</div>
            <div class="stat-value">KES {{ number_format($totalCost, 2) }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-box-seam"></i> Asset Register Roster</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Asset Tag</th>
                    <th>Asset Name</th>
                    <th>Category</th>
                    <th>Condition</th>
                    <th>Purchase Cost (KES)</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assets as $a)
                <tr>
                    <td><span class="badge badge-gray" style="font-family:monospace">{{ $a->asset_tag }}</span></td>
                    <td><strong>{{ $a->name }}</strong></td>
                    <td><span class="badge badge-primary">{{ $a->category }}</span></td>
                    <td>
                        <span class="badge badge-{{ $a->condition === 'New' || $a->condition === 'Good' ? 'success' : ($a->condition === 'Fair' ? 'warning' : 'danger') }}">
                            {{ $a->condition }}
                        </span>
                    </td>
                    <td>KES {{ number_format($a->purchase_cost, 2) }}</td>
                    <td>{{ $a->location ?: 'Main Campus' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="no-data">No assets registered yet. Click "Register Asset".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($assets->hasPages())
        <div class="card-footer">{{ $assets->links() }}</div>
    @endif
</div>

<!-- Modal: Add Asset -->
<div id="modal-asset" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-plus-circle"></i> Register Asset</h5>
            <button onclick="document.getElementById('modal-asset').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('inventory.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Asset Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Dell Core i7 Desktop PC" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Asset Tag Number *</label>
                        <input type="text" name="asset_tag" class="form-control" placeholder="e.g. AST-ICT-001" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" class="form-control" required>
                            <option value="IT & Computers">IT & Computers</option>
                            <option value="Furniture">Furniture</option>
                            <option value="Lab Equipment">Lab Equipment</option>
                            <option value="Sports">Sports Gear</option>
                            <option value="Musical">Musical Instruments</option>
                            <option value="Vehicles">Vehicles</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Purchase Cost (KES) *</label>
                        <input type="number" name="purchase_cost" class="form-control" placeholder="e.g. 65000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Condition *</label>
                        <select name="condition" class="form-control" required>
                            <option value="New">New</option>
                            <option value="Good" selected>Good</option>
                            <option value="Fair">Fair</option>
                            <option value="Needs Repair">Needs Repair</option>
                            <option value="Condemned">Condemned</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Location / Room</label>
                    <input type="text" name="location" class="form-control" placeholder="e.g. Science Lab 2">
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-asset').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Asset</button>
            </div>
        </form>
    </div>
</div>
@endsection
