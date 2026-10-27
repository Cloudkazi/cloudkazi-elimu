@extends('layouts.app')
@section('title', $school->name . ' — Account Oversight')
@section('breadcrumb', 'Platform Admin › Schools › ' . $school->name)

@section('content')
<div class="page-header" style="margin-bottom:20px;">
    <div class="page-header-text">
        <h1 style="font-size:1.6rem;font-weight:700;">
            <i class="bi bi-building text-primary"></i> {{ $school->name }}
            <span class="badge {{ $school->status === 'active' ? 'bg-success' : ($school->status === 'suspended' ? 'bg-danger' : 'bg-warning') }}" style="font-size:0.75rem;vertical-align:middle;margin-left:8px;">
                {{ ucfirst($school->status) }}
            </span>
        </h1>
        <p style="color:var(--text-secondary);font-size:0.9rem;">
            Code: <strong>{{ $school->code }}</strong> • Curriculum: {{ $school->curriculum_type }} • County: {{ $school->county ?? 'N/A' }}
        </p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" class="btn btn-outline" data-bs-toggle="modal" data-bs-target="#changePackageModal">
            <i class="bi bi-box-arrow-up-right"></i> Change Package
        </button>
        <button type="button" class="btn btn-outline" data-bs-toggle="modal" data-bs-target="#extendSubModal">
            <i class="bi bi-calendar-plus"></i> Extend Subscription
        </button>
        <button type="button" class="btn btn-outline" data-bs-toggle="modal" data-bs-target="#allocatePopModal">
            <i class="bi bi-people"></i> Allocate Limits
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manualPayModal">
            <i class="bi bi-cash-coin"></i> Record Payment
        </button>
        @if($school->status === 'suspended')
            <form method="POST" action="{{ route('superadmin.schools.reactivate', $school->id) }}" style="display:inline;" onsubmit="return confirm('Reactivate this school account?');">
                @csrf
                <button type="submit" class="btn btn-success"><i class="bi bi-play-circle"></i> Reactivate</button>
            </form>
        @else
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#suspendModal">
                <i class="bi bi-pause-circle"></i> Suspend
            </button>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:18px;">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
    </div>
@endif
@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:18px;">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Row 1: Account Overview & Subscription Info -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <!-- School Profile Card -->
    <div class="card">
        <div class="card-header">
            <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-info-circle text-primary"></i> School Profile</h5>
        </div>
        <div class="card-body" style="padding:16px;">
            <table class="table table-sm table-borderless" style="margin:0;">
                <tr>
                    <td class="text-muted" style="width:140px;">School Name:</td>
                    <td><strong>{{ $school->name }}</strong></td>
                </tr>
                <tr>
                    <td class="text-muted">School Code:</td>
                    <td><span class="badge bg-light text-dark font-monospace">{{ $school->code }}</span></td>
                </tr>
                <tr>
                    <td class="text-muted">Contact Email:</td>
                    <td>{{ $school->email }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Phone:</td>
                    <td>{{ $school->phone }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Curriculum:</td>
                    <td>{{ $school->curriculum_type }}</td>
                </tr>
                <tr>
                    <td class="text-muted">County / Town:</td>
                    <td>{{ $school->county ?? 'Nairobi' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Account Status:</td>
                    <td>
                        <span class="badge {{ $school->status === 'active' ? 'bg-success' : ($school->status === 'suspended' ? 'bg-danger' : 'bg-warning') }}">
                            {{ ucfirst($school->status) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Subscription Details Card -->
    <div class="card">
        <div class="card-header">
            <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-credit-card text-success"></i> SaaS Subscription</h5>
        </div>
        <div class="card-body" style="padding:16px;">
            @if($subscription)
            <table class="table table-sm table-borderless" style="margin:0;">
                <tr>
                    <td class="text-muted" style="width:140px;">Current Package:</td>
                    <td>
                        <strong class="text-primary">{{ $subscription->package?->name ?? $subscription->plan_name ?? 'Standard' }}</strong>
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">Annual License:</td>
                    <td>KES {{ number_format($subscription->annual_fee ?? $subscription->amount ?? 0) }} / year</td>
                </tr>
                <tr>
                    <td class="text-muted">Activation Fee:</td>
                    <td>KES {{ number_format($subscription->activation_fee ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Start Date:</td>
                    <td>{{ $subscription->start_date ? $subscription->start_date->format('d M Y') : 'N/A' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Expiry Date:</td>
                    <td>
                        <strong>{{ $subscription->end_date ? $subscription->end_date->format('d M Y') : 'N/A' }}</strong>
                        @if($subscription->end_date)
                            <span class="badge {{ $subscription->daysRemaining() <= 14 ? 'bg-danger' : 'bg-info' }}" style="margin-left:6px;">
                                {{ $subscription->daysRemaining() }} days left
                            </span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">Subscription Status:</td>
                    <td>
                        <span class="badge {{ $subscription->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($subscription->status) }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="text-muted">Payment Status:</td>
                    <td>
                        @if($subscription->payment_status === 'successful')
                            <span class="badge bg-success"><i class="bi bi-check2"></i> Confirmed Paid</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> {{ ucfirst($subscription->payment_status ?? 'Pending') }}</span>
                        @endif
                    </td>
                </tr>
            </table>
            @else
                <div class="text-center text-muted" style="padding:24px;">
                    <p>No subscription attached to this school.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Row 2: Population Usage Aggregate Cards (Strict Privacy: Aggregates Only) -->
<h5 style="margin-bottom:14px;font-size:1.1rem;font-weight:600;">
    <i class="bi bi-pie-chart text-info"></i> Population & License Allocation (Aggregate Counts Only)
</h5>
<div class="stats-grid stats-grid-3" style="margin-bottom:24px;">
    <!-- Students -->
    <div class="card" style="padding:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-weight:600;font-size:0.95rem;"><i class="bi bi-mortarboard text-primary"></i> Students</span>
            <span class="badge bg-light text-dark">
                {{ $usage['students']['licensed'] ? $usage['students']['licensed'] . ' Licensed' : 'Unlimited' }}
            </span>
        </div>
        <div style="font-size:1.6rem;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
            {{ number_format($usage['students']['current']) }}
        </div>
        <div style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:8px;">
            @if($usage['students']['licensed'])
                Remaining capacity: <strong>{{ number_format($usage['students']['remaining']) }}</strong>
                ({{ $usage['students']['percent'] }}% utilized)
            @else
                No capacity cap configured
            @endif
        </div>
        @if($usage['students']['licensed'])
        <div class="progress" style="height:6px;">
            <div class="progress-bar {{ $usage['students']['percent'] >= 90 ? 'bg-danger' : ($usage['students']['percent'] >= 75 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ min(100, $usage['students']['percent']) }}%"></div>
        </div>
        @endif
    </div>

    <!-- Teachers -->
    <div class="card" style="padding:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-weight:600;font-size:0.95rem;"><i class="bi bi-person-workspace text-success"></i> Teachers</span>
            <span class="badge bg-light text-dark">
                {{ $usage['teachers']['licensed'] ? $usage['teachers']['licensed'] . ' Licensed' : 'Unlimited' }}
            </span>
        </div>
        <div style="font-size:1.6rem;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
            {{ number_format($usage['teachers']['current']) }}
        </div>
        <div style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:8px;">
            @if($usage['teachers']['licensed'])
                Remaining capacity: <strong>{{ number_format($usage['teachers']['remaining']) }}</strong>
                ({{ $usage['teachers']['percent'] }}% utilized)
            @else
                No capacity cap configured
            @endif
        </div>
        @if($usage['teachers']['licensed'])
        <div class="progress" style="height:6px;">
            <div class="progress-bar bg-success" style="width: {{ min(100, $usage['teachers']['percent']) }}%"></div>
        </div>
        @endif
    </div>

    <!-- Parents -->
    <div class="card" style="padding:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span style="font-weight:600;font-size:0.95rem;"><i class="bi bi-people text-purple"></i> Parent Portals</span>
            <span class="badge bg-light text-dark">
                {{ $usage['parents']['licensed'] ? $usage['parents']['licensed'] . ' Licensed' : 'Unlimited' }}
            </span>
        </div>
        <div style="font-size:1.6rem;font-weight:700;color:var(--text-primary);margin-bottom:4px;">
            {{ number_format($usage['parents']['current']) }}
        </div>
        <div style="font-size:0.8rem;color:var(--text-secondary);margin-bottom:8px;">
            @if($usage['parents']['licensed'])
                Remaining capacity: <strong>{{ number_format($usage['parents']['remaining']) }}</strong>
                ({{ $usage['parents']['percent'] }}% utilized)
            @else
                No capacity cap configured
            @endif
        </div>
        @if($usage['parents']['licensed'])
        <div class="progress" style="height:6px;">
            <div class="progress-bar bg-info" style="width: {{ min(100, $usage['parents']['percent']) }}%"></div>
        </div>
        @endif
    </div>
</div>

<!-- Row 3: Module / Feature Overrides -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h5 style="margin:0;font-size:1rem;font-weight:600;">
            <i class="bi bi-toggles text-primary"></i> Module Access & Feature Control
        </h5>
        <span class="badge bg-light text-dark">Package: {{ $subscription?->package?->name ?? 'Standard' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle" style="margin:0;">
            <thead>
                <tr>
                    <th>Feature Name</th>
                    <th>Package Default</th>
                    <th>Effective Status</th>
                    <th>Source</th>
                    <th>Override Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allFeatures as $key => $label)
                @php $state = $featureStates[$key]; @endphp
                <tr>
                    <td>
                        <strong>{{ $label }}</strong>
                        <div class="text-muted font-monospace" style="font-size:0.75rem;">{{ $key }}</div>
                    </td>
                    <td>
                        @if($state['package_value'])
                            <span class="badge bg-success-subtle text-success"><i class="bi bi-check2"></i> Included</span>
                        @else
                            <span class="badge bg-secondary-subtle text-muted"><i class="bi bi-x"></i> Not in package</span>
                        @endif
                    </td>
                    <td>
                        @if($state['enabled'])
                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
                        @else
                            <span class="badge bg-danger"><i class="bi bi-slash-circle"></i> Disabled</span>
                        @endif
                    </td>
                    <td>
                        @if($state['overridden'])
                            <span class="badge bg-warning text-dark"><i class="bi bi-shield-exclamation"></i> SuperAdmin Override</span>
                        @else
                            <span class="badge bg-light text-dark">Package Default</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            @if($state['enabled'])
                                <form method="POST" action="{{ route('superadmin.schools.feature.update', $school->id) }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="feature_key" value="{{ $key }}">
                                    <input type="hidden" name="enabled" value="0">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Disable this feature for this school">
                                        Disable
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('superadmin.schools.feature.update', $school->id) }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="feature_key" value="{{ $key }}">
                                    <input type="hidden" name="enabled" value="1">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Enable this feature for this school">
                                        Enable
                                    </button>
                                </form>
                            @endif

                            @if($state['overridden'])
                                <form method="POST" action="{{ route('superadmin.schools.feature.reset', $school->id) }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="feature_key" value="{{ $key }}">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Reset to package default">
                                        Reset
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Row 4: SaaS Payments Ledger & Audit Logs -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
    <!-- SaaS Subscription Payments -->
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
            <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-receipt text-success"></i> SaaS Billing History</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manualPayModal">
                + Add Payment
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $pmt)
                    <tr>
                        <td style="font-size:0.8rem;">{{ $pmt->created_at->format('d M Y') }}</td>
                        <td class="font-monospace" style="font-size:0.75rem;">{{ $pmt->reference }}</td>
                        <td><span class="badge bg-light text-dark">{{ strtoupper($pmt->payment_method) }}</span></td>
                        <td><strong>KES {{ number_format($pmt->amount) }}</strong></td>
                        <td>
                            @if($pmt->status === 'successful')
                                <span class="badge bg-success">Success</span>
                            @else
                                <span class="badge bg-warning text-dark">{{ ucfirst($pmt->status) }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted" style="padding:18px;">
                            No SaaS subscription payments recorded yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Audit Logs for this school -->
    <div class="card">
        <div class="card-header">
            <h5 style="margin:0;font-size:1rem;font-weight:600;"><i class="bi bi-shield-check text-primary"></i> School Audit Trail</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-sm">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditLogs as $log)
                    <tr>
                        <td><span class="badge bg-light text-dark font-monospace" style="font-size:0.75rem;">{{ $log->action }}</span></td>
                        <td style="font-size:0.8rem;">{{ Str::limit($log->description, 45) }}</td>
                        <td style="font-size:0.75rem;color:var(--text-muted);">{{ $log->created_at ? $log->created_at->diffForHumans() : 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted" style="padding:18px;">
                            No audit trail entries for this school.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. Change Package Modal -->
<div class="modal fade" id="changePackageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.schools.change-package', $school->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-seam"></i> Change Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="font-size:0.85rem;color:var(--text-secondary);">
                        Select a new package tier. Pricing is loaded strictly from the package configuration in the database.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">New Package</label>
                        <select name="package_id" class="form-select" required>
                            @foreach($packages as $pkg)
                                <option value="{{ $pkg->id }}" {{ $subscription?->package_id == $pkg->id ? 'selected' : '' }}>
                                    {{ $pkg->name }} — KES {{ number_format($pkg->annual_fee) }}/year ({{ $pkg->max_students ? $pkg->max_students . ' students' : 'Unlimited' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply Package</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. Extend Subscription Modal -->
<div class="modal fade" id="extendSubModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.schools.extend-subscription', $school->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-calendar-plus"></i> Extend Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Extension Period</label>
                        <select name="months" class="form-select" required>
                            <option value="1">1 Month</option>
                            <option value="3">3 Months</option>
                            <option value="6">6 Months</option>
                            <option value="12" selected>12 Months (1 Year)</option>
                            <option value="24">24 Months (2 Years)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Extend Expiry Date</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. Allocate Population Limits Modal -->
<div class="modal fade" id="allocatePopModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.schools.allocate-population', $school->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-people"></i> Allocate Population Limits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="font-size:0.85rem;color:var(--text-secondary);">
                        Leave blank to inherit the limit from the package. Values set here override the package limit.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Student Limit</label>
                        <input type="number" name="student_limit" class="form-control" placeholder="e.g. 750" value="{{ $usage['students']['licensed'] }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teacher Limit</label>
                        <input type="number" name="teacher_limit" class="form-control" placeholder="e.g. 50" value="{{ $usage['teachers']['licensed'] }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Limit</label>
                        <input type="number" name="parent_limit" class="form-control" placeholder="e.g. 750" value="{{ $usage['parents']['licensed'] }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason / Justification</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="e.g. School expanded to 4 streams in Form 1"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Allocation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. Record Manual Payment Modal -->
<div class="modal fade" id="manualPayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.schools.record-payment', $school->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Record SaaS Subscription Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="font-size:0.85rem;color:var(--text-secondary);">
                        This records a CloudKazi platform subscription payment. It is completely isolated from student fee payments.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="mpesa">M-Pesa</option>
                            <option value="bank">Bank Transfer / Deposit</option>
                            <option value="paystack">Paystack</option>
                            <option value="manual">Manual / Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (KES)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" value="{{ $subscription?->annual_fee ?? 30000 }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Transaction Reference</label>
                        <input type="text" name="reference" class="form-control" placeholder="e.g. QKJ458921X or BNK-2026-001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Annual renewal received via direct bank deposit"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 5. Suspend School Modal -->
<div class="modal fade" id="suspendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('superadmin.schools.suspend', $school->id) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-exclamation-octagon"></i> Suspend School Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p style="font-size:0.9rem;">
                        Are you sure you want to suspend <strong>{{ $school->name }}</strong>?
                    </p>
                    <div class="alert alert-warning" style="font-size:0.85rem;">
                        <i class="bi bi-shield-exclamation"></i> School staff and users will be prevented from accessing ERP modules. <strong>No school data will be deleted.</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Suspension Reason</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Subscription payment overdue by 30 days" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Suspension</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
