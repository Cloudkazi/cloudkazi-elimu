@extends('layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')

<!-- ===== Page Header ===== -->
<div class="page-header">
    <div class="page-header-text">
        <h1>📊 School Dashboard</h1>
        <p>Welcome back, <strong>{{ auth()->user()->name }}</strong> — {{ now()->format('l, d F Y') }}</p>
    </div>
    @if(auth()->user()?->isDemoAccount())
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <a href="{{ route('school.register') }}" class="btn btn-success" id="dashboard-btn-register-school" style="background:#059669;color:#ffffff;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-building-add"></i> Register School
            </a>
            <form method="POST" action="{{ route('logout') }}" id="dashboard-logout-form-demo" style="margin:0;display:inline;">
                @csrf
                <button type="submit" class="btn btn-danger" id="dashboard-btn-logout" style="background:#dc2626;color:#ffffff;font-weight:600;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
            <span class="badge" style="background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.3);padding:8px 12px;font-size:0.82rem;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-eye"></i> Demo Mode (Read-Only)
            </span>
        </div>
    @else
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('students.create') }}" class="btn btn-accent">
            <i class="bi bi-person-plus"></i> Enroll Student
        </a>
        <a href="{{ route('finance.payments') }}" class="btn btn-primary">
            <i class="bi bi-cash"></i> Record Payment
        </a>
        <a href="{{ route('examinations.marks') }}" class="btn btn-outline">
            <i class="bi bi-pencil"></i> Enter Marks
        </a>
    </div>
    @endif
</div>

<!-- ===== Stats Grid ===== -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom:24px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">🎓</div>
        <div class="stat-body">
            <div class="stat-label">Total Students</div>
            <div class="stat-value">{{ $stats['total_students'] ?? 0 }}</div>
            <div class="stat-trend up">
                <i class="bi bi-arrow-up"></i> {{ $stats['new_this_term'] ?? 0 }} this term
            </div>
        </div>
    </div>
    <div class="stat-card stat-indigo">
        <div class="stat-icon">👩‍🏫</div>
        <div class="stat-body">
            <div class="stat-label">Teachers</div>
            <div class="stat-value">{{ $stats['total_teachers'] ?? 0 }}</div>
            <div class="stat-trend neutral">Academic Faculty</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">🏫</div>
        <div class="stat-body">
            <div class="stat-label">Classes</div>
            <div class="stat-value">{{ $stats['total_classes'] ?? 0 }}</div>
            <div class="stat-trend neutral">Active Streams</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">💰</div>
        <div class="stat-body">
            <div class="stat-label">Fees Today</div>
            <div class="stat-value">KES {{ number_format($stats['fees_collected_today'] ?? 0) }}</div>
            <div class="stat-trend up">
                <i class="bi bi-arrow-up"></i> KES {{ number_format($stats['fees_term'] ?? 0) }} Term
            </div>
        </div>
    </div>
    <div class="stat-card stat-teal">
        <div class="stat-icon">📅</div>
        <div class="stat-body">
            <div class="stat-label">Attendance Today</div>
            <div class="stat-value">{{ $stats['attendance_today'] ?? 0 }}%</div>
            <div class="stat-trend {{ ($stats['attendance_today'] ?? 0) >= 90 ? 'up' : 'down' }}">
                <i class="bi bi-{{ ($stats['attendance_today'] ?? 0) >= 90 ? 'check-circle' : 'exclamation-triangle' }}"></i>
                {{ $stats['present_today'] ?? 0 }} Present
            </div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">⚠️</div>
        <div class="stat-body">
            <div class="stat-label">Students w/ Arrears</div>
            <div class="stat-value">{{ $stats['pending_fees'] ?? 0 }}</div>
            <div class="stat-trend {{ ($stats['pending_fees'] ?? 0) > 0 ? 'down' : 'up' }}">
                <i class="bi bi-{{ ($stats['pending_fees'] ?? 0) > 0 ? 'exclamation-circle' : 'check-circle' }}"></i>
                {{ ($stats['pending_fees'] ?? 0) > 0 ? 'KES ' . number_format($stats['outstanding_fees'] ?? 0) : 'All Cleared' }}
            </div>
        </div>
    </div>
</div>

<!-- ===== Row 2: Charts + Activity ===== -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">

    <!-- Fee Collection Chart -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-graph-up-arrow"></i> Fee Collection Trend (Last 6 Months)</h5>
            <select class="form-select" style="width:auto;font-size:0.8rem;padding:4px 10px">
                <option>All Streams</option>
            </select>
        </div>
        <div class="card-body">
            <canvas id="feeChart" height="200"></canvas>
        </div>
    </div>

    <!-- Attendance Donut -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-pie-chart"></i> Today's Attendance</h5>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;align-items:center">
            <canvas id="attChart" width="180" height="180"></canvas>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px;width:100%">
                <div style="display:flex;align-items:center;gap:6px;font-size:0.8rem">
                    <span style="width:10px;height:10px;border-radius:50%;background:#059669;display:inline-block"></span>
                    Present ({{ $stats['present_today'] ?? 0 }})
                </div>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.8rem">
                    <span style="width:10px;height:10px;border-radius:50%;background:#dc2626;display:inline-block"></span>
                    Absent ({{ $stats['absent_today'] ?? 0 }})
                </div>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.8rem">
                    <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block"></span>
                    Late ({{ $stats['late_today'] ?? 0 }})
                </div>
                <div style="display:flex;align-items:center;gap:6px;font-size:0.8rem">
                    <span style="width:10px;height:10px;border-radius:50%;background:#8b5cf6;display:inline-block"></span>
                    Excused ({{ $stats['excused_today'] ?? 0 }})
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== Row 3: Quick Actions ===== -->
@if(auth()->user()?->isDemoAccount())
<div class="card" style="margin-bottom:20px; border:1px solid rgba(148,163,184,0.4);">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <h5 style="margin:0;"><i class="bi bi-lightning-charge"></i> Quick Actions (Demo Mode)</h5>
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="{{ route('school.register') }}" class="btn btn-sm btn-success" style="background:#059669;color:#fff;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
                <i class="bi bi-building-add"></i> Register School
            </a>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;display:inline;">
                @csrf
                <button type="submit" class="btn btn-sm btn-danger" style="background:#dc2626;color:#fff;font-weight:600;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-warning" style="margin:0; opacity:0.9;">
            <i class="bi bi-lock-fill"></i> This is a read-only demo account for exploring CloudKazi Elimu. To onboard and manage your own real school, click <strong>Register School</strong> above.
        </div>
    </div>
</div>
@else
<div class="card" style="margin-bottom:20px">
    <div class="card-header">
        <h5><i class="bi bi-lightning-charge"></i> Quick Actions</h5>
    </div>
    <div class="card-body">
        <div class="quick-actions">
            <a href="{{ route('students.create') }}" class="quick-action-btn">
                <div class="qa-icon" style="background:linear-gradient(135deg,#2563eb,#1d4ed8)">👤</div>
                <div class="qa-label">Enroll Student</div>
            </a>
            <a href="{{ route('finance.payments') }}" class="quick-action-btn">
                <div class="qa-icon" style="background:linear-gradient(135deg,#059669,#047857)">💳</div>
                <div class="qa-label">Record Payment</div>
            </a>
            <a href="{{ route('attendance.index') }}" class="quick-action-btn">
                <div class="qa-icon" style="background:linear-gradient(135deg,#0ea5e9,#0284c7)">📋</div>
                <div class="qa-label">Take Attendance</div>
            </a>
            <a href="{{ route('examinations.marks') }}" class="quick-action-btn">
                <div class="qa-icon" style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">✏️</div>
                <div class="qa-label">Enter Marks</div>
            </a>
            <a href="{{ route('examinations.report-cards') }}" class="quick-action-btn">
                <div class="qa-icon" style="background:linear-gradient(135deg,#e8922a,#c97820)">📄</div>
                <div class="qa-label">Report Cards</div>
            </a>
            <a href="{{ route('operations.notices') }}" class="quick-action-btn">
                <div class="qa-icon" style="background:linear-gradient(135deg,#0d9488,#0f766e)">📢</div>
                <div class="qa-label">Send Notice</div>
            </a>
            <a href="{{ route('finance.mpesa') }}" class="quick-action-btn">
                <div class="qa-icon" style="background:linear-gradient(135deg,#00A651,#007a3d)">📱</div>
                <div class="qa-label">M-Pesa STK</div>
            </a>
            <a href="{{ route('academics.timetable') }}" class="quick-action-btn">
                <div class="qa-icon" style="background:linear-gradient(135deg,#db2777,#be185d)">📅</div>
                <div class="qa-label">Timetable</div>
            </a>
        </div>
    </div>
</div>
@endif

<!-- ===== Row 4: Recent Students + Activity ===== -->
<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:20px;margin-bottom:20px">

    <!-- Recent Enrollments -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-person-plus"></i> Recent Enrollments</h5>
            <a href="{{ route('students.index') }}" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Adm No.</th>
                        <th>Class</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentStudents ?? [] as $student)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="avatar-text avatar-sm" style="background:linear-gradient(135deg,#2563eb,#7c3aed)">
                                    {{ strtoupper(substr($student->first_name,0,1)) }}
                                </div>
                                <div>
                                    <div style="font-weight:600">{{ $student->full_name }}</div>
                                    <div style="font-size:0.75rem;color:var(--text-muted)">{{ $student->gender }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-gray">{{ $student->admission_number }}</span></td>
                        <td>{{ $student->currentClass?->class_name ?? '—' }}</td>
                        <td>
                            <span class="badge badge-{{ $student->status === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($student->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('students.show', $student) }}" class="btn btn-outline btn-sm">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="no-data">
                        <i class="bi bi-person-x"></i>
                        No students found. <a href="{{ route('students.create') }}">Enroll first student</a>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Payments + Events -->
    <div style="display:flex;flex-direction:column;gap:20px">
        <!-- Recent Payments -->
        <div class="card" style="margin-bottom:0">
            <div class="card-header">
                <h5><i class="bi bi-receipt"></i> Recent Payments</h5>
                <a href="{{ route('finance.payments') }}" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="card-body" style="padding: 8px 0">
                @forelse($recentPayments ?? [] as $payment)
                <div class="activity-item" style="padding:10px 16px">
                    <div class="activity-dot" style="background:#dcfce7;color:#166534">💳</div>
                    <div class="activity-content">
                        <div class="title">{{ $payment->student?->full_name ?? 'Student' }}</div>
                        <div class="meta">
                            <strong style="color:var(--success)">KES {{ number_format($payment->amount, 2) }}</strong>
                            &nbsp;·&nbsp; {{ $payment->payment_method ?? 'Cash' }}
                            &nbsp;·&nbsp; {{ $payment->created_at?->diffForHumans() }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="no-data" style="padding:20px">
                    <i class="bi bi-cash-coin"></i>
                    No payments today
                </div>
                @endforelse
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="card" style="margin-bottom:0">
            <div class="card-header">
                <h5><i class="bi bi-calendar-event"></i> Upcoming Events</h5>
            </div>
            <div class="card-body" style="padding:12px">
                <div class="event-list">
                    <div class="event-item warning">
                        <div class="event-date">
                            <div class="day">{{ now()->addDays(3)->format('d') }}</div>
                            <div class="month">{{ now()->addDays(3)->format('M') }}</div>
                        </div>
                        <div class="event-body">
                            <div class="event-title">Term Exam - Week 1</div>
                            <div class="event-meta">All classes · 08:00 AM</div>
                        </div>
                    </div>
                    <div class="event-item success">
                        <div class="event-date">
                            <div class="day">{{ now()->addDays(7)->format('d') }}</div>
                            <div class="month">{{ now()->addDays(7)->format('M') }}</div>
                        </div>
                        <div class="event-body">
                            <div class="event-title">Fee Deadline</div>
                            <div class="event-meta">Finance Office · Reminder</div>
                        </div>
                    </div>
                    <div class="event-item danger">
                        <div class="event-date">
                            <div class="day">{{ now()->addDays(14)->format('d') }}</div>
                            <div class="month">{{ now()->addDays(14)->format('M') }}</div>
                        </div>
                        <div class="event-body">
                            <div class="event-title">Parent's Day</div>
                            <div class="event-meta">School Hall · 09:00 AM</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== Row 5: Class Performance + Top Students ===== -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    <!-- Class Performance -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-bar-chart"></i> Class Performance (Last Exam)</h5>
        </div>
        <div class="card-body">
            <canvas id="classChart" height="220"></canvas>
        </div>
    </div>

    <!-- Top Students -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-trophy"></i> Top Students</h5>
            <a href="{{ route('examinations.results') }}" class="btn btn-outline btn-sm">Full Results</a>
        </div>
        <div class="card-body" style="padding:0">
            @php
                $topStudents = $topStudents ?? [];
                $medals = ['🥇','🥈','🥉','4️⃣','5️⃣'];
            @endphp
            @forelse($topStudents as $i => $student)
            <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border-light)">
                <span style="font-size:1.3rem;width:30px;text-align:center">{{ $medals[$i] ?? ($i+1) }}</span>
                <div class="avatar-text avatar-sm" style="background:linear-gradient(135deg,#e8922a,#c97820)">
                    {{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) }}
                </div>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:0.875rem">{{ $student->full_name ?? 'Student' }}</div>
                    <div style="font-size:0.75rem;color:var(--text-muted)">{{ $student->currentClass?->class_name ?? '' }}</div>
                </div>
                <div style="text-align:right">
                    <div style="font-family:'Outfit',sans-serif;font-weight:800;color:var(--primary)">{{ $student->avg_score ?? '—' }}%</div>
                </div>
            </div>
            @empty
            <div class="no-data"><i class="bi bi-trophy"></i> No exam results yet</div>
            @endforelse
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Fee Chart ---
    const feeCtx = document.getElementById('feeChart');
    if (feeCtx) {
        new Chart(feeCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($feeChartLabels ?? ['Apr','May','Jun','Jul','Aug','Sep']) !!},
                datasets: [{
                    label: 'Fees Collected (KES)',
                    data: {!! json_encode($feeChartData ?? [450000, 520000, 380000, 610000, 490000, 720000]) !!},
                    backgroundColor: 'rgba(26,58,92,0.8)',
                    borderRadius: 6,
                }, {
                    label: 'Outstanding',
                    data: {!! json_encode($feeOutstandingData ?? [120000, 90000, 150000, 80000, 110000, 60000]) !!},
                    backgroundColor: 'rgba(232,146,42,0.7)',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom', labels: { font: { size: 12 } } } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => 'KES ' + (v/1000) + 'K' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // --- Attendance Donut ---
    const attCtx = document.getElementById('attChart');
    if (attCtx) {
        new Chart(attCtx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent', 'Late', 'Excused'],
                datasets: [{
                    data: [
                        {{ $stats['present_today'] ?? 320 }},
                        {{ $stats['absent_today'] ?? 15 }},
                        {{ $stats['late_today'] ?? 12 }},
                        {{ $stats['excused_today'] ?? 5 }}
                    ],
                    backgroundColor: ['#059669','#dc2626','#f59e0b','#8b5cf6'],
                    borderWidth: 3,
                    borderColor: '#fff',
                }]
            },
            options: {
                cutout: '68%',
                plugins: { legend: { display: false } }
            }
        });
    }

    // --- Class Performance Chart ---
    const classCtx = document.getElementById('classChart');
    if (classCtx) {
        new Chart(classCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($classLabels ?? ['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6']) !!},
                datasets: [{
                    label: 'Average Score (%)',
                    data: {!! json_encode($classScores ?? [72, 68, 75, 81, 65, 77]) !!},
                    backgroundColor: function(ctx) {
                        const v = ctx.raw;
                        return v >= 75 ? '#059669' : v >= 60 ? '#e8922a' : '#dc2626';
                    },
                    borderRadius: 6,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } },
                    y: { grid: { display: false } }
                }
            }
        });
    }
});
</script>
@endpush
