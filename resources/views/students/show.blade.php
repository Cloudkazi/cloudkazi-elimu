@extends('layouts.app')
@section('title', $student->full_name . ' — Profile')
@section('breadcrumb', 'Students › ' . $student->full_name)

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>👤 Student Profile</h1>
        <p>Detailed record for <strong>{{ $student->full_name }}</strong></p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="{{ route('students.edit', $student) }}" class="btn btn-primary">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('finance.student-statement', $student) }}" class="btn btn-accent">
            <i class="bi bi-receipt"></i> Fee Statement
        </a>
        <a href="{{ route('examinations.student-results', $student) }}" class="btn btn-outline">
            <i class="bi bi-bar-chart"></i> Results
        </a>
        <form action="{{ route('students.destroy', $student) }}" method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to permanently delete this student? This action will remove the student and their associated records.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline btn-danger">
                <i class="bi bi-trash"></i> Delete
            </button>
        </form>
        <button class="btn btn-outline" onclick="window.print()">
            <i class="bi bi-printer"></i>
        </button>
        <a href="{{ route('students.index') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div style="display:grid;grid-template-columns:320px 1fr;gap:20px;align-items:start">

    <!-- Left: Profile Card -->
    <div>
        <div class="student-profile-card">
            <div class="student-profile-header">
                <div class="student-avatar-big">
                    @if($student->photo)
                        <img src="{{ asset('storage/'.$student->photo) }}" alt="{{ $student->full_name }}"
                            style="width:100%;height:100%;border-radius:50%;object-fit:cover">
                    @else
                        {{ strtoupper(substr($student->first_name, 0, 2)) }}
                    @endif
                </div>
                <h4>{{ $student->full_name }}</h4>
                <p>
                    <span class="badge badge-{{ $student->status === 'active' ? 'success' : 'warning' }}"
                          style="background:rgba(255,255,255,0.2);color:white;border:1px solid rgba(255,255,255,0.3)">
                        {{ ucfirst($student->status) }}
                    </span>
                </p>
                <div class="student-profile-stats" style="margin-top:14px">
                    <div class="sp-stat">
                        <div class="val">{{ $student->currentClass?->class_name ?? '—' }}</div>
                        <div class="lbl">Class</div>
                    </div>
                    <div class="sp-stat">
                        <div class="val">{{ $attendanceRate ?? '—' }}%</div>
                        <div class="lbl">Attendance</div>
                    </div>
                    <div class="sp-stat">
                        <div class="val">{{ $avgScore ?? '—' }}%</div>
                        <div class="lbl">Avg Score</div>
                    </div>
                </div>
            </div>
            <div class="student-profile-body">
                <div class="profile-info-grid">
                    <div class="profile-info-item">
                        <label>Admission No.</label>
                        <span>{{ $student->admission_number }}</span>
                    </div>
                    <div class="profile-info-item">
                        <label>NEMIS UPI</label>
                        <span>{{ $student->nemis_upi ?? '—' }}</span>
                    </div>
                    <div class="profile-info-item">
                        <label>Date of Birth</label>
                        <span>{{ $student->date_of_birth?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="profile-info-item">
                        <label>Gender</label>
                        <span>{{ $student->gender }}</span>
                    </div>
                    <div class="profile-info-item">
                        <label>Blood Group</label>
                        <span>{{ $student->blood_group ?? '—' }}</span>
                    </div>
                    <div class="profile-info-item">
                        <label>Nationality</label>
                        <span>{{ $student->nationality ?? 'Kenyan' }}</span>
                    </div>
                    <div class="profile-info-item">
                        <label>County</label>
                        <span>{{ $student->county ?? '—' }}</span>
                    </div>
                    <div class="profile-info-item">
                        <label>Fee Profile</label>
                        <span><strong style="text-transform:capitalize">{{ str_replace('_', ' + ', $student->fee_profile ?? 'regular') }}</strong></span>
                    </div>
                    <div class="profile-info-item">
                        <label>Admitted</label>
                        <span>{{ $student->admission_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                </div>

                <div class="separator"></div>

                <div style="margin-bottom:10px">
                    <label class="form-label" style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase">Parent/Guardian</label>
                    <div style="font-weight:700">{{ $student->guardian_name ?? '—' }}</div>
                    <div style="font-size:0.8rem;color:var(--text-muted)">{{ $student->guardian_relationship ?? '' }}</div>
                    <div style="font-size:0.875rem;margin-top:4px">📞 {{ $student->guardian_phone ?? '—' }}</div>
                    @if($student->guardian_email)
                    <div style="font-size:0.875rem">📧 {{ $student->guardian_email }}</div>
                    @endif
                </div>

                @if($student->hostelAllocation || $student->isBoarder())
                <div class="separator"></div>
                <div style="margin-bottom:10px">
                    <label class="form-label" style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase">Hostel Accommodation</label>
                    <div style="font-weight:700">🏨 {{ $student->hostelAllocation?->dormitory?->name ?? 'Hostel Resident' }}</div>
                    <div style="font-size:0.8rem;color:var(--text-muted)">
                        {{ $student->hostelAllocation?->room?->room_number ? 'Room ' . $student->hostelAllocation->room->room_number : '' }}
                        {{ $student->hostelAllocation?->bed_number ? ' | Bed ' . $student->hostelAllocation->bed_number : '' }}
                    </div>
                    @if($student->hostelAllocation?->dormitory?->warden_name)
                    <div style="font-size:0.8rem;margin-top:2px">Warden: {{ $student->hostelAllocation->dormitory->warden_name }} ({{ $student->hostelAllocation->dormitory->warden_phone ?? 'N/A' }})</div>
                    @endif
                </div>
                @endif

                @if($student->medical_conditions)
                <div class="separator"></div>
                <div>
                    <label class="form-label" style="color:var(--text-muted);font-size:0.72rem;text-transform:uppercase">Medical Notes</label>
                    <p style="font-size:0.85rem;color:var(--text)">{{ $student->medical_conditions }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Fee Balance Card -->
        <div class="card" style="margin-top:16px">
            <div class="card-header"><h5><i class="bi bi-cash"></i> Fee Status</h5></div>
            <div class="card-body">
                @php
                    $balance = $student->fee_balance ?? 0;
                    $totalBilled = $totalBilled ?? 0;
                    $totalPaid = $totalPaid ?? 0;
                @endphp
                <div style="text-align:center;margin-bottom:14px">
                    <div style="font-size:0.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase">Outstanding Balance</div>
                    <div style="font-family:'Outfit',sans-serif;font-size:2rem;font-weight:900;color:{{ $balance > 0 ? 'var(--danger)' : 'var(--success)' }}">
                        KES {{ number_format(abs($balance), 2) }}
                    </div>
                    <span class="badge {{ $balance > 0 ? 'badge-danger' : 'badge-success' }}">
                        {{ $balance > 0 ? 'Amount Owed' : '✓ Fully Paid' }}
                    </span>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <div style="text-align:center;padding:10px;background:var(--bg);border-radius:8px">
                        <div style="font-size:0.72rem;color:var(--text-muted)">Total Billed</div>
                        <div style="font-weight:800;color:var(--text)">KES {{ number_format($totalBilled) }}</div>
                    </div>
                    <div style="text-align:center;padding:10px;background:var(--bg);border-radius:8px">
                        <div style="font-size:0.72rem;color:var(--text-muted)">Total Paid</div>
                        <div style="font-weight:800;color:var(--success)">KES {{ number_format($totalPaid) }}</div>
                    </div>
                </div>
                <div style="margin-top:12px">
                    <div style="display:flex;justify-content:space-between;font-size:0.75rem;margin-bottom:4px">
                        <span>Payment Progress</span>
                        <span>{{ $totalBilled > 0 ? round($totalPaid / $totalBilled * 100) : 0 }}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar {{ $balance > 0 ? 'warning' : 'success' }}"
                            style="width: {{ $totalBilled > 0 ? min(100, round($totalPaid / $totalBilled * 100)) : 0 }}%"></div>
                    </div>
                </div>
                <div style="margin-top:14px;display:flex;gap:8px">
                    <a href="{{ route('finance.student-statement', $student) }}" class="btn btn-outline btn-sm" style="flex:1;justify-content:center">
                        <i class="bi bi-receipt"></i> Statement
                    </a>
                    <a href="{{ route('finance.payments') }}?student_id={{ $student->id }}" class="btn btn-success btn-sm" style="flex:1;justify-content:center">
                        <i class="bi bi-cash-coin"></i> Pay Fees
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Tabs Content -->
    <div>
        <!-- Tab Navigation -->
        <div style="display:flex;gap:4px;margin-bottom:20px;background:white;padding:6px;border-radius:var(--radius);box-shadow:var(--shadow-sm);"
             data-tab-group="student-tabs">
            @foreach([
                ['id' => 'tab-academic', 'icon' => 'bi-book', 'label' => 'Academic'],
                ['id' => 'tab-attendance', 'icon' => 'bi-calendar-check', 'label' => 'Attendance'],
                ['id' => 'tab-results', 'icon' => 'bi-bar-chart', 'label' => 'Results'],
                ['id' => 'tab-payments', 'icon' => 'bi-receipt', 'label' => 'Payments'],
                ['id' => 'tab-docs', 'icon' => 'bi-file-earmark', 'label' => 'Documents'],
            ] as $tab)
            <button data-tab-target="{{ $tab['id'] }}"
                    class="btn btn-outline btn-sm {{ $loop->first ? 'active' : '' }}"
                    style="{{ $loop->first ? 'background:var(--primary);color:white;border-color:var(--primary)' : '' }}">
                <i class="bi {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
            </button>
            @endforeach
        </div>

        <!-- Academic Tab -->
        <div id="tab-academic" class="tab-pane active">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-book"></i> Subject Assignments — {{ now()->year }}/{{ now()->year+1 }}</h5>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Subject</th><th>Teacher</th><th>Lessons/Week</th><th>Curriculum</th></tr>
                        </thead>
                        <tbody>
                            @forelse($subjects ?? [] as $subject)
                            <tr>
                                <td><span class="badge badge-primary">{{ $subject->subject_name }}</span></td>
                                <td>{{ $subject->teacher?->full_name ?? 'Not Assigned' }}</td>
                                <td>{{ $subject->lessons_per_week ?? '5' }}</td>
                                <td><span class="badge badge-teal">{{ $subject->curriculum_type ?? 'CBC' }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="no-data"><i class="bi bi-book"></i> No subjects assigned</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Attendance Tab -->
        <div id="tab-attendance" class="tab-pane" style="display:none">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-calendar-check"></i> Attendance History</h5>
                    <span class="badge badge-{{ ($attendanceRate ?? 0) >= 90 ? 'success' : 'warning' }}">
                        {{ $attendanceRate ?? 0 }}% overall
                    </span>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px">
                        <div style="text-align:center;padding:12px;background:#dcfce7;border-radius:8px">
                            <div style="font-size:1.4rem;font-weight:900;color:#166534">{{ $attendanceStats['present'] ?? 0 }}</div>
                            <div style="font-size:0.75rem;color:#166534">Present</div>
                        </div>
                        <div style="text-align:center;padding:12px;background:#fee2e2;border-radius:8px">
                            <div style="font-size:1.4rem;font-weight:900;color:#991b1b">{{ $attendanceStats['absent'] ?? 0 }}</div>
                            <div style="font-size:0.75rem;color:#991b1b">Absent</div>
                        </div>
                        <div style="text-align:center;padding:12px;background:#fef3c7;border-radius:8px">
                            <div style="font-size:1.4rem;font-weight:900;color:#78350f">{{ $attendanceStats['late'] ?? 0 }}</div>
                            <div style="font-size:0.75rem;color:#78350f">Late</div>
                        </div>
                        <div style="text-align:center;padding:12px;background:#ede9fe;border-radius:8px">
                            <div style="font-size:1.4rem;font-weight:900;color:#5b21b6">{{ $attendanceStats['excused'] ?? 0 }}</div>
                            <div style="font-size:0.75rem;color:#5b21b6">Excused</div>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height:320px;overflow-y:auto">
                        <table>
                            <thead><tr><th>Date</th><th>Status</th><th>Remarks</th></tr></thead>
                            <tbody>
                                @forelse($attendanceHistory ?? [] as $att)
                                <tr>
                                    <td>{{ $att->date?->format('D, d M Y') }}</td>
                                    <td>
                                        @php $attColors = ['present'=>'success','absent'=>'danger','late'=>'warning','excused'=>'purple']; @endphp
                                        <span class="badge badge-{{ $attColors[$att->status] ?? 'gray' }}">{{ ucfirst($att->status) }}</span>
                                    </td>
                                    <td>{{ $att->remarks ?? '—' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="no-data"><i class="bi bi-calendar-x"></i> No attendance records</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Tab -->
        <div id="tab-results" class="tab-pane" style="display:none">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-bar-chart"></i> Examination Results</h5>
                    <a href="{{ route('examinations.student-results', $student) }}" class="btn btn-outline btn-sm">Full Report Card</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Exam</th><th>Subject</th><th>Score</th><th>Grade</th><th>Remarks</th></tr>
                        </thead>
                        <tbody>
                            @forelse($examResults ?? [] as $result)
                            <tr>
                                <td>{{ $result->exam?->exam_name }}</td>
                                <td>{{ $result->subject?->subject_name }}</td>
                                <td><strong>{{ $result->score }}/{{ $result->out_of ?? 100 }}</strong></td>
                                <td><span class="badge badge-primary">{{ $result->grade }}</span></td>
                                <td>{{ $result->remarks ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="no-data"><i class="bi bi-pencil-square"></i> No exam results recorded</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payments Tab -->
        <div id="tab-payments" class="tab-pane" style="display:none">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-receipt"></i> Payment History</h5>
                    <a href="{{ route('finance.payments') }}?student_id={{ $student->id }}" class="btn btn-success btn-sm">
                        <i class="bi bi-plus"></i> Add Payment
                    </a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Amount</th><th>Method</th><th>Ref</th><th>Term</th><th>Receipt</th></tr>
                        </thead>
                        <tbody>
                            @forelse($payments ?? [] as $payment)
                            <tr>
                                <td>{{ $payment->created_at?->format('d M Y') }}</td>
                                <td><strong style="color:var(--success)">KES {{ number_format($payment->amount, 2) }}</strong></td>
                                <td><span class="badge badge-teal">{{ $payment->payment_method ?? 'Cash' }}</span></td>
                                <td><code>{{ $payment->reference_number ?? '—' }}</code></td>
                                <td>Term {{ $payment->term }}</td>
                                <td>
                                    <a href="{{ route('finance.receipt', $payment) }}" class="btn btn-outline btn-sm">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="no-data"><i class="bi bi-cash-coin"></i> No payments recorded</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Documents Tab -->
        <div id="tab-docs" class="tab-pane" style="display:none">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-file-earmark"></i> Student Documents</h5></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                        @php
                            $docs = [
                                ['label'=>'Birth Certificate','icon'=>'bi-file-earmark-person','key'=>'birth_certificate'],
                                ['label'=>'ID / Passport Copy','icon'=>'bi-file-earmark-text','key'=>'id_copy'],
                                ['label'=>'Medical Records','icon'=>'bi-file-earmark-medical','key'=>'medical'],
                                ['label'=>'Transfer Letter','icon'=>'bi-file-earmark-arrow-up','key'=>'transfer'],
                                ['label'=>'KCPE Cert','icon'=>'bi-award','key'=>'kcpe'],
                                ['label'=>'Other Documents','icon'=>'bi-folder','key'=>'other'],
                            ];
                        @endphp
                        @foreach($docs as $doc)
                        <div style="border:2px dashed var(--border);border-radius:var(--radius-sm);padding:20px;text-align:center">
                            <i class="bi {{ $doc['icon'] }}" style="font-size:28px;color:var(--text-muted);display:block;margin-bottom:8px"></i>
                            <div style="font-size:0.8rem;font-weight:600">{{ $doc['label'] }}</div>
                            <div style="margin-top:8px">
                                <label class="btn btn-outline btn-sm" style="cursor:pointer">
                                    <i class="bi bi-upload"></i> Upload
                                    <input type="file" style="display:none" name="doc_{{ $doc['key'] }}">
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
// Tab switching
document.querySelectorAll('[data-tab-target]').forEach(btn => {
    btn.addEventListener('click', function() {
        const target = this.getAttribute('data-tab-target');
        document.querySelectorAll('[data-tab-target]').forEach(b => {
            b.style.background = ''; b.style.color = ''; b.style.borderColor = '';
        });
        this.style.background = 'var(--primary)';
        this.style.color = 'white';
        this.style.borderColor = 'var(--primary)';
        document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
        const pane = document.getElementById(target);
        if (pane) pane.style.display = 'block';
    });
});
</script>
@endpush

@endsection
