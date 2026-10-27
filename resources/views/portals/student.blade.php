@extends('layouts.app')
@section('title', 'Student Portal')
@section('breadcrumb', 'Portals › Student Portal')

@section('content')
{{-- Page Header --}}
<div class="page-header">
    <div class="page-header-text">
        <h1>🎓 Student Academic Portal</h1>
        <p>Welcome, <strong>{{ $student?->full_name ?? Auth::user()->name }}</strong>{{ $student ? ' — Adm: ' . $student->admission_number . ' | ' . ($student->schoolClass?->name ?? '') : '' }}</p>
    </div>
    @if(auth()->user()?->isDemoAccount())
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <a href="{{ route('school.register') }}" class="btn btn-success" id="student-btn-register-school" style="background:#059669;color:#ffffff;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-building-add"></i> Register School
        </a>
        <form method="POST" action="{{ route('logout') }}" id="student-logout-form-demo" style="margin:0;display:inline;">
            @csrf
            <button type="submit" class="btn btn-danger" id="student-btn-logout" style="background:#dc2626;color:#ffffff;font-weight:600;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
        <span class="badge" style="background:rgba(245,158,11,0.15);color:#f59e0b;border:1px solid rgba(245,158,11,0.3);padding:8px 12px;font-size:0.82rem;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-eye"></i> Demo Mode (Read-Only)
        </span>
    </div>
    @endif
</div>

@if(isset($unlinked) && $unlinked)
<div class="card" style="text-align:center;padding:60px 20px">
    <div style="font-size:3rem;margin-bottom:16px">🔗</div>
    <h3>Student Profile Not Linked</h3>
    <p style="color:var(--text-muted);max-width:420px;margin:0 auto">Your login account has not been linked to a student record yet. Please contact the school administrator to connect your account.</p>
</div>
@else

<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
    <a class="btn btn-outline" href="{{ route('portal.student.diary') }}"><i class="bi bi-journal-text"></i> School Diary</a>
    <a class="btn btn-outline" href="{{ route('portal.student.performance') }}"><i class="bi bi-graph-up"></i> Academic Performance</a>
    @if($practiceAvailable)
    <a class="btn btn-outline" href="{{ route('portal.practice') }}"><i class="bi bi-lightning-charge"></i> My Practice</a>
    @endif
</div>

@unless($practiceAvailable)
<div class="alert alert-info" role="status" style="margin-bottom:20px">
    Student practice is temporarily unavailable. Please contact your school administrator.
</div>
@endunless

@if($feeYearSummary)
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h5 style="margin:0"><i class="bi bi-calendar2-check"></i> Fee account — Academic Year {{ $feeYearSummary['year']->name }}</h5></div>
    <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px">
        <div><small>Total charges</small><strong style="display:block">KES {{ number_format($feeYearSummary['charges'], 2) }}</strong></div>
        <div><small>Credits / adjustments</small><strong style="display:block">KES {{ number_format($feeYearSummary['credits'], 2) }}</strong></div>
        <div><small>Confirmed payments</small><strong style="display:block">KES {{ number_format($feeYearSummary['payments'], 2) }}</strong></div>
        <div><small>Outstanding</small><strong style="display:block">KES {{ number_format($feeYearSummary['balance'], 2) }}</strong></div>
    </div>
</div>
@endif

@if($practiceChapter)
<section id="practice-prompt" class="card" style="margin-bottom:20px;border-left:4px solid #2563eb">
    <div class="card-body" style="display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap">
        <div>
            <h2 style="font-size:1.05rem;margin:0 0 6px">{{ $practiceChapter->subject?->name ?? 'Learning' }} Practice Now Available!</h2>
            <p style="margin:0;color:var(--text-muted)">Practice curriculum content for {{ $student->schoolClass?->name }} and track your progress, one topic at a time.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-primary" href="{{ route('portal.practice', ['subject_id' => $practiceChapter->subject_id]) }}">Try Now</a>
            <button class="btn btn-outline" type="button" onclick="sessionStorage.setItem('practice-prompt-dismissed','1');document.getElementById('practice-prompt').hidden=true">Remind Me Later</button>
        </div>
    </div>
</section>
<script>
if (sessionStorage.getItem('practice-prompt-dismissed') === '1') {
    document.getElementById('practice-prompt').hidden = true;
}
</script>
@endif

{{-- Summary Cards --}}
<div class="stats-grid stats-grid-4" style="margin-bottom:20px">
    {{-- Academic Performance --}}
    <div class="stat-card stat-blue">
        <div class="stat-icon">📈</div>
        <div class="stat-body">
            <div class="stat-label">Academic Performance</div>
            <div class="stat-value">{{ $reportSummary ? number_format($reportSummary->average_score ?? 0, 1) . '%' : 'Pending' }}</div>
            <div class="stat-trend up">Rank: {{ $reportSummary?->class_rank ?? '—' }} in class</div>
        </div>
    </div>
    {{-- Fee Balance --}}
    @php $balance = $feeAccount['balance'] ?? ($feeBreakdown['balance'] ?? 0); @endphp
    <div class="stat-card {{ $balance <= 0 ? 'stat-green' : 'stat-red' }}">
        <div class="stat-icon">💳</div>
        <div class="stat-body">
            <div class="stat-label">Fee Balance</div>
            <div class="stat-value">{{ $balance <= 0 ? 'CLEARED' : 'KES ' . number_format($balance) }}</div>
            <div class="stat-trend {{ $balance <= 0 ? 'up' : 'down' }}">
                Paid: KES {{ number_format($feeAccount['payments'] ?? $feeBreakdown['total_paid'] ?? 0) }}
            </div>
        </div>
    </div>
    {{-- Dormitory --}}
    <div class="stat-card stat-purple">
        <div class="stat-icon">🏨</div>
        <div class="stat-body">
            <div class="stat-label">Accommodation</div>
            <div class="stat-value" style="font-size:0.95rem">{{ $hostelAllocation ? ($hostelAllocation->dormitory?->name ?? 'Assigned') : 'Day Scholar' }}</div>
            <div class="stat-trend">{{ $hostelAllocation?->room?->room_number ? 'Room ' . $hostelAllocation->room->room_number : ($hostelAllocation ? 'Boarder' : 'No hostel assigned') }}</div>
        </div>
    </div>
    {{-- Transport --}}
    <div class="stat-card stat-orange">
        <div class="stat-icon">🚌</div>
        <div class="stat-body">
            <div class="stat-label">Transport</div>
            <div class="stat-value" style="font-size:0.95rem">{{ $transportAllocation ? ($transportAllocation->route?->name ?? 'Assigned') : 'None' }}</div>
            <div class="stat-trend">{{ $transportAllocation?->route?->pickup_point ?? 'No route assigned' }}</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:20px;margin-bottom:20px">

    {{-- Fee Breakdown by Category --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-receipt-cutoff"></i> Fee Statement — Current Term</h5>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th class="text-right">Charged (KES)</th>
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
                        <td class="text-right"><strong>{{ number_format($cat['amount'], 2) }}</strong></td>
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
                    <tr style="color:{{ ($feeBreakdown['balance'] ?? 0) > 0 ? 'var(--danger)' : 'var(--success)' }};font-weight:700">
                        <td>Balance Due</td>
                        <td class="text-right">KES {{ number_format($feeAccount['balance'] ?? $feeBreakdown['balance'] ?? 0, 2) }}</td>
                        <td>
                            @if(($feeBreakdown['is_cleared'] ?? false))
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

    {{-- Recent Payment History --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-clock-history"></i> Recent Payments</h5>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Academic Year / Term</th>
                        <th>Method</th>
                        <th>Ref</th>
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
                        <td style="font-size:0.75rem;color:var(--text-muted)">{{ $pmt->receipt_number }}</td>
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
                    <tr><td colspan="6" class="no-data">No confirmed payments recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($feeInvoices->isNotEmpty())
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h5><i class="bi bi-journal-text"></i> Fee Invoices by Academic Year and Term</h5></div>
    <div class="table-responsive"><table>
        <thead><tr><th>Invoice</th><th>Academic Year</th><th>Term</th><th>Charges</th><th>Credits</th><th>Paid</th><th>Balance</th></tr></thead>
        <tbody>
        @foreach($feeInvoices as $invoice)
            <tr>
                <td>{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->academicYear?->name ?? '—' }}</td>
                <td>{{ $invoice->term?->name ?? '—' }}</td>
                <td>KES {{ number_format($invoice->total_amount, 2) }}</td>
                <td>KES {{ number_format($invoice->waiver_amount, 2) }}</td>
                <td>KES {{ number_format($invoice->paid_amount, 2) }}</td>
                <td><strong>KES {{ number_format($invoice->balance, 2) }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</div>
@endif

    {{-- Dormitory / Hostel Allocation Details --}}
    @if($student->isBoarder() || $hostelAllocation)
    <div class="card" style="margin-bottom:20px;border-left:4px solid #8b5cf6">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-building"></i> Boarding &amp; Dormitory Allocation</h5>
            <span class="badge" style="background:#ede9fe;color:#6d28d9">Hostel Resident</span>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:16px">
                <div style="padding:12px;background:var(--surface-subtle);border-radius:8px">
                    <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;font-weight:600">Dormitory / Hall</div>
                    <div style="font-size:1.1rem;font-weight:700;color:var(--text)">{{ $hostelAllocation?->dormitory?->name ?? 'Assigned Hall' }}</div>
                    <div style="font-size:0.75rem;color:var(--text-muted)">Gender: {{ $hostelAllocation?->dormitory?->gender ?? 'Hostel' }}</div>
                </div>
                <div style="padding:12px;background:var(--surface-subtle);border-radius:8px">
                    <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;font-weight:600">Room / Cubicle</div>
                    <div style="font-size:1.1rem;font-weight:700;color:var(--text)">{{ $hostelAllocation?->room?->room_number ? 'Room ' . $hostelAllocation->room->room_number : 'Assigned' }}</div>
                    <div style="font-size:0.75rem;color:var(--text-muted)">Bed: <strong>{{ $hostelAllocation?->bed_number ?? 'Assigned' }}</strong></div>
                </div>
                <div style="padding:12px;background:var(--surface-subtle);border-radius:8px">
                    <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;font-weight:600">Hostel Warden</div>
                    <div style="font-size:1.1rem;font-weight:700;color:var(--text)">{{ $hostelAllocation?->dormitory?->warden_name ?? 'Resident Warden' }}</div>
                    <div style="font-size:0.8rem;color:var(--primary)">📞 {{ $hostelAllocation?->dormitory?->warden_phone ?? 'Via School Office' }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif

{{-- Timetable --}}
@if($activeTimetable && count($streamMatrix) > 0)
<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-calendar3"></i> My Class Timetable — {{ $student?->schoolClass?->name }} {{ $student?->stream?->name }}</h5>
        @if($student?->stream_id)
        <a href="{{ route('timetable.print', ['timetable_id' => $activeTimetable->id, 'stream_id' => $student->stream_id]) }}" class="btn btn-sm btn-outline" target="_blank">
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
                        // Check both key order styles for robustness
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

{{-- My Library Books --}}
<div class="card" id="my-library-books" style="margin-bottom:20px">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-book"></i> My Library Books</h5>
        @if(isset($libraryIssues) && $libraryIssues->count() > 0)
        <span class="badge badge-primary">{{ $libraryIssues->count() }} {{ Str::plural('Book', $libraryIssues->count()) }}</span>
        @endif
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($libraryIssues ?? [] as $issue)
                @php
                    $isOverdue = $issue->isOverdue();
                    $effectiveStatus = $issue->computed_status;
                @endphp
                <tr style="{{ $isOverdue ? 'background:rgba(239, 68, 68, 0.08); border-left: 4px solid var(--danger);' : '' }}">
                    <td>
                        <strong style="color:var(--primary)">{{ $issue->book?->title ?? 'Untitled Book' }}</strong>
                        @if($issue->book?->category)
                            <div style="font-size:0.75rem;color:var(--text-muted)"><i class="bi bi-tag"></i> {{ $issue->book->category->name }}</div>
                        @endif
                    </td>
                    <td>{{ $issue->book?->author ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($issue->issue_date)->format('d M Y') }}</td>
                    <td>
                        <span style="color:{{ $isOverdue ? 'var(--danger)' : 'inherit' }};font-weight:{{ $isOverdue ? '700' : 'normal' }}">
                            {{ \Carbon\Carbon::parse($issue->due_date)->format('d M Y') }}
                            @if($isOverdue)
                                <small style="display:block;font-size:0.75rem;color:var(--danger)"><i class="bi bi-exclamation-triangle"></i> Overdue</small>
                            @endif
                        </span>
                    </td>
                    <td>
                        @if($issue->status === 'returned')
                            <span class="badge badge-success"><i class="bi bi-check2-circle"></i> Returned</span>
                        @elseif($isOverdue)
                            <span class="badge badge-danger"><i class="bi bi-exclamation-triangle-fill"></i> Overdue</span>
                        @else
                            <span class="badge badge-primary"><i class="bi bi-clock-history"></i> Issued</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="no-data" style="text-align:center;padding:30px 15px;color:var(--text-muted)">
                        <div style="font-size:1.8rem;margin-bottom:6px">📚</div>
                        <p style="margin:0;font-size:0.95rem">No library books have been issued to you.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px">

    {{-- Learning Materials --}}
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-journal-text"></i> Class Learning Materials &amp; Assignments</h5>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $m)
                    <tr>
                        <td><strong>{{ $m->subject?->name }}</strong></td>
                        <td>
                            <div>{{ $m->title }}</div>
                            <div style="font-size:0.72rem;color:var(--text-muted)">{{ $m->description }}</div>
                        </td>
                        <td><span class="badge badge-primary">{{ ucfirst(str_replace('_', ' ', $m->type)) }}</span></td>
                        <td>{{ $m->due_date ? \Carbon\Carbon::parse($m->due_date)->format('d M Y') : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="no-data">No study materials published for your class yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Report Card + Announcements --}}
    <div style="display:flex;flex-direction:column;gap:20px">
        @if($latestExam && $student)
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-printer"></i> My Report Card</h5>
            </div>
            <div class="card-body">
                <p style="font-size:0.85rem;color:var(--text-muted)">View your official report card for <strong>{{ $latestExam->name }}</strong>.</p>
                <a href="{{ route('examinations.report-card', ['examId' => $latestExam->id, 'studentId' => $student->id]) }}"
                   class="btn btn-primary btn-block" target="_blank" style="width:100%;text-align:center">
                    <i class="bi bi-file-earmark-richtext"></i> View Report Card
                </a>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-megaphone"></i> School Bulletins</h5>
            </div>
            <div class="card-body" style="padding:0">
                @forelse($announcements as $ann)
                <div style="padding:12px 16px;border-bottom:1px solid #f1f5f9">
                    <strong style="font-size:0.85rem;color:var(--primary)">{{ $ann->title }}</strong>
                    <p style="margin:4px 0 0 0;font-size:0.75rem;color:var(--text-muted)">{{ Str::limit($ann->content, 90) }}</p>
                </div>
                @empty
                <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:0.85rem">No announcements.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@endif
@endsection
