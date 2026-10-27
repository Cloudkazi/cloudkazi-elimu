@extends('layouts.app')
@section('title', 'Parent Portal')
@section('breadcrumb', 'Portals › Parent Portal')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>👨‍👩‍👧 Parent &amp; Guardian Portal</h1>
        <p>Welcome, <strong>{{ $guardian ? $guardian->first_name . ' ' . $guardian->last_name : Auth::user()->name }}</strong> — Track your children's fees, academic results, and daily attendance.</p>
    </div>
    @if(auth()->user()?->isDemoAccount())
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <a href="{{ route('school.register') }}" class="btn btn-success" id="parent-btn-register-school" style="background:#059669;color:#ffffff;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-building-add"></i> Register School
        </a>
        <form method="POST" action="{{ route('logout') }}" id="parent-logout-form-demo" style="margin:0;display:inline;">
            @csrf
            <button type="submit" class="btn btn-danger" id="parent-btn-logout" style="background:#dc2626;color:#ffffff;font-weight:600;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
        <span class="badge" style="background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.3);padding:8px 12px;font-size:0.82rem;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-eye"></i> Demo Mode (Read-Only)
        </span>
    </div>
    @endif
</div>

@if($children->isEmpty())
<div class="card" style="text-align:center;padding:60px 20px">
    <div style="font-size:3rem;margin-bottom:16px">🔗</div>
    <h3>No Children Linked</h3>
    <p style="color:var(--text-muted);max-width:440px;margin:0 auto">Your account has no linked students yet. Contact the school administrator to link your children's records.</p>
</div>
@else

{{-- Child Selector Tabs --}}
<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    @foreach($children as $child)
    <a href="{{ request()->fullUrlWithQuery(['student_id' => $child->id]) }}"
       class="card" style="text-decoration:none;padding:10px 16px;display:flex;align-items:center;gap:10px;border:2px solid {{ $selectedStudent && $selectedStudent->id == $child->id ? 'var(--primary)' : '#e2e8f0' }}">
        <div class="avatar-text avatar-sm" style="background:var(--primary);color:#fff">{{ strtoupper(substr($child->first_name, 0, 1)) }}</div>
        <div>
            <strong style="color:var(--text-dark);font-size:0.9rem">{{ $child->full_name }}</strong>
            <div style="font-size:0.72rem;color:var(--text-muted)">{{ $child->schoolClass?->name }} {{ $child->stream?->name }} | {{ $child->admission_number }}</div>
        </div>
    </a>
    @endforeach
</div>

@if($selectedStudent)
<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px">
    <a class="btn btn-outline" href="{{ route('portal.parent.diary', ['student_id' => $selectedStudent->id]) }}"><i class="bi bi-journal-text"></i> School Diary</a>
    <a class="btn btn-outline" href="{{ route('portal.parent.performance', ['student_id' => $selectedStudent->id]) }}"><i class="bi bi-graph-up"></i> Academic Performance</a>
</div>
@if($feeYearSummary)
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h5 style="margin:0"><i class="bi bi-calendar2-check"></i> {{ $selectedStudent->first_name }}'s fee account — {{ $feeYearSummary['year']->name }}</h5></div>
    <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px">
        <div><small>Total charges</small><strong style="display:block">KES {{ number_format($feeYearSummary['charges'], 2) }}</strong></div>
        <div><small>Credits / adjustments</small><strong style="display:block">KES {{ number_format($feeYearSummary['credits'], 2) }}</strong></div>
        <div><small>Confirmed payments</small><strong style="display:block">KES {{ number_format($feeYearSummary['payments'], 2) }}</strong></div>
        <div><small>Outstanding</small><strong style="display:block">KES {{ number_format($feeYearSummary['balance'], 2) }}</strong></div>
    </div>
</div>
@endif
{{-- Summary Stat Cards --}}
@php $balance = $feeAccount['balance'] ?? ($feeBreakdown['balance'] ?? 0); @endphp
<div class="stats-grid stats-grid-4" style="margin-bottom:24px">
    <div class="stat-card {{ $balance <= 0 ? 'stat-green' : 'stat-orange' }}">
        <div class="stat-icon">💰</div>
        <div class="stat-body">
            <div class="stat-label">Fee Balance</div>
            <div class="stat-value">{{ $balance <= 0 ? 'CLEARED' : 'KES ' . number_format($balance) }}</div>
            <div class="stat-trend {{ $balance <= 0 ? 'up' : 'down' }}">Paid: KES {{ number_format($feeAccount['payments'] ?? $feeBreakdown['total_paid'] ?? 0) }}</div>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon">📑</div>
        <div class="stat-body">
            <div class="stat-label">Latest Exam Average</div>
            <div class="stat-value">{{ $reportSummary ? number_format($reportSummary->average_score ?? 0, 1) . '%' : 'Awaiting' }}</div>
            <div class="stat-trend up">Grade: <strong>{{ $reportSummary?->mean_grade ?? 'Pending' }}</strong></div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">🏨</div>
        <div class="stat-body">
            <div class="stat-label">Accommodation</div>
            <div class="stat-value" style="font-size:0.9rem">{{ $hostelAllocation ? ($hostelAllocation->dormitory?->name ?? 'Boarder') : 'Day Scholar' }}</div>
            <div class="stat-trend">{{ $hostelAllocation?->room?->room_number ? 'Room ' . $hostelAllocation->room->room_number : '' }}</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">🚌</div>
        <div class="stat-body">
            <div class="stat-label">Transport</div>
            <div class="stat-value" style="font-size:0.9rem">{{ $transportAllocation ? ($transportAllocation->route?->name ?? 'Assigned') : 'None' }}</div>
            <div class="stat-trend">{{ $transportAllocation?->route?->pickup_point ?? 'No route' }}</div>
        </div>
    </div>
</div>

{{-- Boarding & Dormitory Allocation Card --}}
@if($selectedStudent->isBoarder() || $hostelAllocation)
<div class="card" style="margin-bottom:20px;border-left:4px solid #8b5cf6">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-building"></i> Boarding &amp; Dormitory Allocation — {{ $selectedStudent->first_name }}</h5>
        <span class="badge" style="background:#ede9fe;color:#6d28d9">Boarder</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px">
            <div style="padding:12px;background:var(--surface-subtle);border-radius:8px">
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;font-weight:600">Dormitory Hall</div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--text)">{{ $hostelAllocation?->dormitory?->name ?? 'Assigned Hall' }}</div>
                <div style="font-size:0.75rem;color:var(--text-muted)">Gender: {{ $hostelAllocation?->dormitory?->gender ?? 'Hostel' }}</div>
            </div>
            <div style="padding:12px;background:var(--surface-subtle);border-radius:8px">
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;font-weight:600">Room / Cubicle</div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--text)">{{ $hostelAllocation?->room?->room_number ? 'Room ' . $hostelAllocation->room->room_number : 'Assigned' }}</div>
                <div style="font-size:0.75rem;color:var(--text-muted)">Bed: <strong>{{ $hostelAllocation?->bed_number ?? 'Assigned' }}</strong></div>
            </div>
            <div style="padding:12px;background:var(--surface-subtle);border-radius:8px">
                <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;font-weight:600">Hostel Warden / Matron</div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--text)">{{ $hostelAllocation?->dormitory?->warden_name ?? 'Resident Warden' }}</div>
                <div style="font-size:0.8rem;color:var(--primary)">📞 {{ $hostelAllocation?->dormitory?->warden_phone ?? 'Via School Office' }}</div>
            </div>
        </div>
    </div>
</div>
@endif

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:20px;margin-bottom:20px">

    {{-- Fee Category Breakdown --}}
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-receipt-cutoff"></i> Fee Statement — {{ $selectedStudent->full_name }}</h5>
            @if(($feeBreakdown['balance'] ?? 0) > 0)
            <button type="button" class="btn btn-success btn-sm" onclick="document.getElementById('parent-pay-modal').style.display='flex'">
                <i class="bi bi-phone"></i> Pay Fees Online
            </button>
            @endif
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Fee Category</th>
                        <th class="text-right">Amount (KES)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($feeBreakdown['categories'] ?? [] as $key => $cat)
                    @if($cat['applicable'])
                    <tr>
                        <td>
                            <strong>{{ $cat['label'] }}</strong>
                            <div style="font-size:0.72rem;color:var(--text-muted)">{{ $cat['description'] }}</div>
                        </td>
                        <td class="text-right">{{ number_format($cat['amount'], 2) }}</td>
                        <td><span class="badge badge-{{ $cat['amount'] > 0 ? 'primary' : 'secondary' }}">{{ $cat['amount'] > 0 ? 'Active' : 'Nil' }}</span></td>
                    </tr>
                    @endif
                    @endforeach
                    <tr style="background:var(--surface-subtle);font-weight:600">
                        <td>Total Billed</td>
                        <td class="text-right">KES {{ number_format($feeAccount['charges'] ?? $feeBreakdown['total_billed'] ?? 0, 2) }}</td>
                        <td></td>
                    </tr>
                    @if(($feeAccount['credits'] ?? 0) > 0)
                    <tr>
                        <td>Credits / waivers</td>
                        <td class="text-right">KES {{ number_format($feeAccount['credits'], 2) }}</td>
                        <td><span class="badge badge-secondary">Applied</span></td>
                    </tr>
                    @endif
                    <tr style="color:var(--success)">
                        <td>Total Paid</td>
                        <td class="text-right"><strong>KES {{ number_format($feeAccount['payments'] ?? $feeBreakdown['total_paid'] ?? 0, 2) }}</strong></td>
                        <td><span class="badge badge-success">Received</span></td>
                    </tr>
                    <tr style="font-weight:700;color:{{ ($feeBreakdown['balance'] ?? 0) > 0 ? 'var(--danger)' : 'var(--success)' }}">
                        <td>Balance Due</td>
                        <td class="text-right">KES {{ number_format($feeAccount['balance'] ?? $feeBreakdown['balance'] ?? 0, 2) }}</td>
                        <td>
                            @if($feeBreakdown['is_cleared'] ?? false)
                                <span class="badge badge-success">✔ CLEARED</span>
                            @else
                                <span class="badge badge-danger">OUTSTANDING</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Payments --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-clock-history"></i> Recent Payments</h5>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Year / Term</th>
                        <th>Method</th>
                        <th class="text-right">Amount</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $pmt)
                    <tr>
                        <td>{{ $pmt->payment_date?->format('d M Y') }}</td>
                        <td>{{ $pmt->invoice?->academicYear?->name ?? '—' }} / {{ $pmt->invoice?->term?->name ?? '—' }}</td>
                        <td><span class="badge {{ $pmt->method_badge_class }}">{{ $pmt->method_label }}</span></td>
                        <td class="text-right"><strong>{{ number_format($pmt->amount, 2) }}</strong></td>
                        <td>
                            <a href="{{ route('finance.receipt', ['id' => $pmt->id, 'download' => 1]) }}" class="btn btn-outline btn-sm" title="Download PDF receipt">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <a href="{{ route('finance.receipt', $pmt->id) }}" target="_blank" class="btn btn-outline btn-sm" title="Print Official Receipt">
                                <i class="bi bi-printer"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="no-data">No confirmed payments recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;margin-bottom:20px">

    {{-- Invoices Table --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-receipt"></i> Invoices</h5>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Invoice No.</th>
                        <th>Academic Year</th>
                        <th>Term</th>
                        <th class="text-right">Billed</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr>
                        <td><strong style="font-family:monospace">{{ $inv->invoice_number }}</strong></td>
                        <td style="font-size:0.8rem">{{ $inv->academicYear?->name ?? '—' }}</td>
                        <td style="font-size:0.8rem">{{ $inv->term?->name ?? '—' }}</td>
                        <td class="text-right">{{ number_format($inv->total_amount, 2) }}</td>
                        <td class="text-right" style="color:var(--success)">{{ number_format($inv->paid_amount, 2) }}</td>
                        <td class="text-right">
                            <strong style="color:{{ $inv->balance > 0 ? 'var(--danger)' : 'var(--success)' }}">
                                {{ number_format($inv->balance, 2) }}
                            </strong>
                        </td>
                        <td><span class="badge badge-{{ $inv->status === 'paid' ? 'success' : ($inv->status === 'partially_paid' ? 'warning' : 'danger') }}">{{ ucfirst(str_replace('_', ' ', $inv->status)) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="no-data">No fee invoices found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Report Card + Announcements --}}
    <div style="display:flex;flex-direction:column;gap:20px">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-award"></i> Terminal Report Card</h5>
            </div>
            <div class="card-body">
                @if($latestExam)
                <p style="font-size:0.875rem;color:var(--text-muted)">
                    Official report card for <strong>{{ $latestExam->name }}</strong>.
                </p>
                <a href="{{ route('examinations.report-card', ['examId' => $latestExam->id, 'studentId' => $selectedStudent->id]) }}"
                   class="btn btn-primary btn-block" target="_blank" style="width:100%;text-align:center">
                    <i class="bi bi-file-earmark-richtext"></i> View Report Card
                </a>
                @else
                <p style="color:var(--text-muted);font-size:0.85rem">No exam session published yet.</p>
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-megaphone"></i> School Notices</h5>
            </div>
            <div class="card-body" style="padding:0">
                @forelse($announcements as $ann)
                <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9">
                    <strong style="font-size:0.85rem;color:var(--primary)">{{ $ann->title }}</strong>
                    <p style="margin:4px 0 0;font-size:0.75rem;color:var(--text-muted)">{{ Str::limit($ann->content, 90) }}</p>
                </div>
                @empty
                <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:0.85rem">No announcements.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Class Timetable --}}
@if($activeTimetable && count($streamMatrix) > 0)
<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-calendar3"></i> Class Timetable — {{ $selectedStudent->schoolClass?->name }} {{ $selectedStudent->stream?->name }}</h5>
        @if($selectedStudent->stream_id)
        <a href="{{ route('timetable.print', ['timetable_id' => $activeTimetable->id, 'stream_id' => $selectedStudent->stream_id]) }}"
           class="btn btn-sm btn-outline" target="_blank">
            <i class="bi bi-printer"></i> Print
        </a>
        @endif
    </div>
    <div class="table-responsive">
        @php
            $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
            $maxPeriods = 8;
        @endphp
        <table style="font-size:0.8rem">
            <thead>
                <tr>
                    <th style="width:100px">Period</th>
                    @foreach($days as $day)
                    <th>{{ $day }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for($p = 1; $p <= $maxPeriods; $p++)
                <tr>
                    <td><strong>Period {{ $p }}</strong></td>
                    @foreach($days as $day)
                    @php
                        $slot = $streamMatrix[$day][$p] ?? ($streamMatrix[$p][$day] ?? null);
                        $subj = $slot['subject_name'] ?? ($slot['subject'] ?? ($slot['subject_code'] ?? null));
                        $tchr = $slot['teacher_name'] ?? ($slot['teacher'] ?? null);
                    @endphp
                    <td>
                        @if($subj)
                        <div style="font-size:0.78rem;font-weight:600;color:var(--primary)">{{ $subj }}</div>
                        @if($tchr)
                        <div style="font-size:0.68rem;color:var(--text-muted)">{{ $tchr }}</div>
                        @endif
                        @else
                        <span style="color:var(--text-muted)">—</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Attendance Summary --}}
@if($recentAttendance->isNotEmpty())
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-calendar-check"></i> Recent Attendance (Last 7 Days)</h5>
    </div>
    <div style="display:flex;gap:8px;padding:16px;flex-wrap:wrap">
        @foreach($recentAttendance as $att)
        <div style="text-align:center;padding:10px 14px;border-radius:8px;background:{{ $att->status === 'present' ? '#dcfce7' : '#fee2e2' }};min-width:70px">
            <div style="font-size:0.7rem;color:var(--text-muted)">{{ \Carbon\Carbon::parse($att->attendance_date)->format('D d') }}</div>
            <div style="font-weight:700;font-size:0.85rem;color:{{ $att->status === 'present' ? '#16a34a' : '#dc2626' }}">
                {{ strtoupper(substr($att->status, 0, 1)) }}
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if($selectedStudent)
{{-- Parent Self-Service Payment Gateway Modal --}}
<div id="parent-pay-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;padding:16px">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:580px;box-shadow:0 20px 40px rgba(0,0,0,0.25);overflow:hidden;max-height:92vh;display:flex;flex-direction:column">
        
        {{-- Modal Header --}}
        <div style="background:linear-gradient(135deg, #00A651, #007537);color:#fff;padding:16px 22px;display:flex;justify-content:space-between;align-items:center">
            <div>
                <h5 style="margin:0;color:#fff;font-size:1.15rem"><i class="bi bi-wallet2"></i> Pay School Fees Online</h5>
                <small style="opacity:0.9">Student: <strong>{{ $selectedStudent->full_name }}</strong> ({{ $selectedStudent->admission_number }})</small>
            </div>
            <button type="button" onclick="document.getElementById('parent-pay-modal').style.display='none'" style="background:none;border:none;color:#fff;font-size:1.6rem;cursor:pointer;line-height:1">&times;</button>
        </div>

        {{-- Channel Tabs --}}
        <div style="display:flex;border-bottom:1px solid var(--border);background:var(--surface-subtle);overflow-x:auto">
            <button type="button" class="tab-btn active" id="tab-btn-stk" onclick="switchPayTab('stk')"
                style="flex:1;padding:12px;border:none;background:none;font-weight:700;font-size:0.85rem;cursor:pointer;border-bottom:3px solid #00A651;color:#00A651">
                📱 M-Pesa STK
            </button>
            <button type="button" class="tab-btn" id="tab-btn-paybill" onclick="switchPayTab('paybill')"
                style="flex:1;padding:12px;border:none;background:none;font-weight:600;font-size:0.85rem;cursor:pointer;border-bottom:3px solid transparent;color:var(--text-muted)">
                💳 Paybill
            </button>
            <button type="button" class="tab-btn" id="tab-btn-till" onclick="switchPayTab('till')"
                style="flex:1;padding:12px;border:none;background:none;font-weight:600;font-size:0.85rem;cursor:pointer;border-bottom:3px solid transparent;color:var(--text-muted)">
                🛒 Buy Goods Till
            </button>
            <button type="button" class="tab-btn" id="tab-btn-bank" onclick="switchPayTab('bank')"
                style="flex:1;padding:12px;border:none;background:none;font-weight:600;font-size:0.85rem;cursor:pointer;border-bottom:3px solid transparent;color:var(--text-muted)">
                🏦 Bank
            </button>
        </div>

        {{-- Modal Body (Scrollable) --}}
        <div style="padding:22px;overflow-y:auto;flex:1">

            {{-- Tab 1: M-Pesa STK Push Form --}}
            <div id="tab-content-stk">
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px;margin-bottom:18px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="font-size:1.8rem">📲</span>
                        <div>
                            <div style="font-weight:700;color:#166534;font-size:0.95rem">Instant Lipa Na M-Pesa Online</div>
                            <div style="font-size:0.8rem;color:#15803d">Enter your phone number and you will receive a secure prompt on your screen to enter your M-Pesa PIN.</div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('parent.dashboard.pay') }}" method="POST">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $selectedStudent->id }}">
                    <div class="form-group" style="margin-bottom:16px">
                        <label class="form-label">Safaricom M-Pesa Phone Number <span style="color:red">*</span></label>
                        <input type="tel" name="phone" class="form-control" required
                            value="{{ old('phone', $guardian?->phone ?? auth()->user()?->phone) }}"
                            placeholder="e.g. 0712345678 or 254712345678">
                        <small style="color:var(--text-muted)">Must be a registered Safaricom line (07... or 01...)</small>
                    </div>

                    <div class="form-group" style="margin-bottom:16px">
                        <label class="form-label">Amount to Pay (KES) <span style="color:red">*</span></label>
                        <input type="number" name="amount" class="form-control" required min="1"
                            value="{{ old('amount', max(1, (int) round($feeBreakdown['balance'] ?? 0))) }}"
                            placeholder="e.g. 5000">
                        <div style="display:flex;justify-content:space-between;margin-top:4px">
                            <small style="color:var(--text-muted)">Total Outstanding Balance:</small>
                            <small style="font-weight:700;color:var(--danger)">KES {{ number_format($feeBreakdown['balance'] ?? 0, 2) }}</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100" style="background:#00A651;border-color:#00A651">
                        <i class="bi bi-send-check"></i> Send M-Pesa PIN Prompt
                    </button>
                </form>
            </div>

            {{-- Tab 2: Paybill Instructions --}}
            <div id="tab-content-paybill" style="display:none">
                <div style="background:var(--surface-subtle);border-radius:10px;padding:16px;margin-bottom:18px">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:12px">
                        <div style="background:#fff;padding:12px;border-radius:8px;border:1px solid var(--border)">
                            <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;font-weight:600">Paybill Business No</div>
                            <div style="font-size:1.3rem;font-weight:900;color:#00A651;font-family:monospace" id="pb-num">
                                {{ $paymentSettings->daraja_paybill_number ?? $paymentSettings->daraja_shortcode ?? '174379' }}
                            </div>
                            <button type="button" class="btn btn-outline btn-xs" style="margin-top:4px" onclick="copyText('pb-num')">
                                <i class="bi bi-copy"></i> Copy Paybill
                            </button>
                        </div>
                        <div style="background:#fff;padding:12px;border-radius:8px;border:1px solid var(--border)">
                            <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;font-weight:600">Account Number</div>
                            <div style="font-size:1.3rem;font-weight:900;color:var(--primary);font-family:monospace" id="pb-acc">
                                {{ $selectedStudent->admission_number }}
                            </div>
                            <button type="button" class="btn btn-outline btn-xs" style="margin-top:4px" onclick="copyText('pb-acc')">
                                <i class="bi bi-copy"></i> Copy Account
                            </button>
                        </div>
                    </div>
                </div>

                <h6 style="font-weight:700;margin-bottom:10px">Step-by-Step Instructions:</h6>
                <ol style="font-size:0.85rem;color:var(--text-muted);line-height:1.7;padding-left:20px;margin:0">
                    <li>Open <strong>M-Pesa</strong> on your phone (SIM toolkit or M-Pesa App)</li>
                    <li>Select <strong>Lipa Na M-Pesa</strong> &rsaquo; <strong>Pay Bill</strong></li>
                    <li>Enter Business No: <strong>{{ $paymentSettings->daraja_paybill_number ?? $paymentSettings->daraja_shortcode ?? '174379' }}</strong></li>
                    <li>Enter Account No: <strong>{{ $selectedStudent->admission_number }}</strong></li>
                    <li>Enter the amount you wish to pay</li>
                    <li>Enter your M-Pesa PIN and confirm the transaction</li>
                </ol>
            </div>

            {{-- Tab 3: Till Number Instructions --}}
            <div id="tab-content-till" style="display:none">
                <div style="background:var(--surface-subtle);border-radius:10px;padding:16px;margin-bottom:18px">
                    <div style="background:#fff;padding:14px;border-radius:8px;border:1px solid var(--border);text-align:center">
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;font-weight:600">Buy Goods Till Number</div>
                        <div style="font-size:1.8rem;font-weight:900;color:#00A651;font-family:monospace;margin:6px 0" id="till-num">
                            {{ $paymentSettings->daraja_till_number ?? '987654' }}
                        </div>
                        <div style="font-size:0.85rem;color:var(--text-muted)">
                            Store Name: <strong>{{ $paymentSettings->daraja_business_name ?? auth()->user()?->school?->name ?? 'School Finance' }}</strong>
                        </div>
                        <button type="button" class="btn btn-outline btn-xs" style="margin-top:8px" onclick="copyText('till-num')">
                            <i class="bi bi-copy"></i> Copy Till Number
                        </button>
                    </div>
                </div>

                <h6 style="font-weight:700;margin-bottom:10px">Step-by-Step Instructions:</h6>
                <ol style="font-size:0.85rem;color:var(--text-muted);line-height:1.7;padding-left:20px;margin:0">
                    <li>Go to <strong>Lipa Na M-Pesa</strong> on your phone</li>
                    <li>Select <strong>Buy Goods and Services</strong></li>
                    <li>Enter Till No: <strong>{{ $paymentSettings->daraja_till_number ?? '987654' }}</strong></li>
                    <li>Enter the Amount and your M-Pesa PIN</li>
                </ol>
            </div>

            {{-- Tab 4: School Bank Accounts --}}
            <div id="tab-content-bank" style="display:none">
                <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:14px">
                    You can make direct transfers (EFT, RTGS, PesaLink, Mobile App to Bank) or cash deposits at any of the school bank branches:
                </p>

                @forelse($bankAccounts as $acc)
                <div style="background:#fff;border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:12px;box-shadow:0 2px 6px rgba(0,0,0,0.02)">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                        <strong style="font-size:1rem;color:var(--primary)">{{ $acc->bank_name }}</strong>
                        @if($acc->is_primary)
                            <span class="badge badge-success" style="font-size:0.7rem">PRIMARY</span>
                        @endif
                    </div>
                    <div style="font-size:0.82rem;color:var(--text-muted);margin-bottom:4px">
                        Account Name: <strong>{{ $acc->account_name }}</strong>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;background:var(--surface-subtle);padding:8px 12px;border-radius:6px">
                        <span style="font-family:monospace;font-size:1.1rem;font-weight:800;color:var(--text)" id="bank-acc-{{ $acc->id }}">
                            {{ $acc->account_number }}
                        </span>
                        <button type="button" class="btn btn-outline btn-xs" onclick="copyText('bank-acc-{{ $acc->id }}')">
                            <i class="bi bi-copy"></i> Copy
                        </button>
                    </div>
                    @if($acc->branch)
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:6px">Branch: {{ $acc->branch }}</div>
                    @endif
                </div>
                @empty
                <div style="padding:20px;text-align:center;color:var(--text-muted)">
                    No school bank accounts currently listed. Please use M-Pesa or contact the bursar.
                </div>
                @endforelse

                <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;margin-top:14px">
                    <small style="color:#92400e;font-weight:600">
                        <i class="bi bi-exclamation-triangle"></i> Important: Quote <strong>{{ $selectedStudent->admission_number }}</strong> as the deposit / transfer reference so the bursar can reconcile your payment instantly.
                    </small>
                </div>
            </div>

        </div>

        {{-- Modal Footer --}}
        <div style="background:var(--surface-subtle);padding:12px 22px;display:flex;justify-content:flex-end">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('parent-pay-modal').style.display='none'">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function switchPayTab(tab) {
    const tabs = ['stk', 'paybill', 'till', 'bank'];
    tabs.forEach(t => {
        const content = document.getElementById('tab-content-' + t);
        const btn = document.getElementById('tab-btn-' + t);
        if (content) content.style.display = t === tab ? 'block' : 'none';
        if (btn) {
            btn.style.color = t === tab ? '#00A651' : 'var(--text-muted)';
            btn.style.borderBottomColor = t === tab ? '#00A651' : 'transparent';
            btn.style.fontWeight = t === tab ? '700' : '600';
        }
    });
}

function copyText(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const text = el.innerText.trim();
    navigator.clipboard.writeText(text).then(() => {
        alert('Copied to clipboard: ' + text);
    }).catch(() => {
        prompt('Copy this:', text);
    });
}
</script>
@endif

@else
<div class="card">
    <div class="card-body no-data">Select a child above to view their information.</div>
</div>
@endif

@endif
@endsection
