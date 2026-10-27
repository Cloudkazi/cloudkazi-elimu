@extends('layouts.app')
@section('title', ($student->first_name ?? 'Student') . ' ' . ($student->last_name ?? '') . ' — Report Card')
@section('breadcrumb', 'Examinations › Report Card')

@php
    $schoolColor = '#1a3c6e'; // Default deep blue — could be customised per school
    $accentColor = '#e8922a';
    $schoolName  = $school->name ?? 'CloudKazi Academy';
    $schoolAddr  = $school->address ?? '';
    $schoolPhone = $school->phone ?? '';
    $schoolEmail = $school->email ?? '';
    $schoolMotto = $school->motto ?? '';
    $academicYear = $exam->academicYear?->name ?? date('Y');
    $termName     = $exam->term?->name ?? 'Term';
    $examName     = $exam->name ?? 'End of Term';
@endphp

@push('styles')
<style>
/* ─── Print-safe Google Font ─── */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;1,600&display=swap');

/* ─── Screen controls (hidden on print) ─── */
.rc-controls {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-bottom: 28px;
    flex-wrap: wrap;
}
@media print {
    @page { size: A4 portrait; margin: 8mm; }
    html, body { width: auto !important; margin: 0 !important; padding: 0 !important; background: #fff !important; }
    .sidebar, .topbar, .rc-controls, .page-header, nav, header, footer { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .page-content { margin: 0 !important; padding: 0 !important; }
    .rc-paper {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        border: 1px solid #cbd5e1;
        border-radius: 0;
        box-shadow: none;
        overflow: visible;
    }
    .rc-header, .rc-next-term, .rc-marks-table th, .rc-marks-table tfoot td {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    .rc-student-section, .rc-summary-stats, .rc-remarks-section, .rc-parent-section, .rc-next-term {
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .rc-marks-table thead { display: table-header-group; }
    .rc-marks-table tr { break-inside: avoid; page-break-inside: avoid; }
    .rc-footer-note { color: #475569; }
}

/* ─── Report paper ─── */
.rc-paper {
    font-family: 'Inter', sans-serif;
    width: 100%;
    max-width: 870px;
    margin: 0 auto;
    background: #fff;
    box-shadow: 0 4px 40px rgba(0,0,0,0.12);
    border-radius: 12px;
    overflow: hidden;
    print-color-adjust: exact;
    -webkit-print-color-adjust: exact;
}

/* ─── Header band ─── */
.rc-header {
    background: linear-gradient(135deg, {{ $schoolColor }}, #0f2548);
    color: #fff;
    padding: 28px 36px 22px;
    display: flex;
    align-items: center;
    gap: 24px;
}
.rc-logo-wrap {
    flex-shrink: 0;
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    border: 3px solid rgba(255,255,255,0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    overflow: hidden;
}
.rc-logo-wrap img { width: 100%; height: 100%; object-fit: cover; }
.rc-school-info { flex: 1; }
.rc-school-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    margin-bottom: 4px;
}
.rc-school-sub {
    font-size: 0.8rem;
    opacity: 0.8;
    line-height: 1.6;
}
.rc-school-motto {
    font-style: italic;
    font-size: 0.78rem;
    opacity: 0.68;
    margin-top: 4px;
}
.rc-report-badge {
    flex-shrink: 0;
    background: {{ $accentColor }};
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    text-align: center;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    line-height: 1.4;
}
.rc-report-badge span {
    display: block;
    font-size: 1rem;
    letter-spacing: 0;
    text-transform: none;
}

/* ─── Divider stripe ─── */
.rc-stripe {
    height: 5px;
    background: linear-gradient(90deg, {{ $accentColor }}, {{ $schoolColor }}, {{ $accentColor }});
}

/* ─── Student profile section ─── */
.rc-student-section {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0;
    border-bottom: 2px solid #f0f0f0;
}
.rc-student-details {
    padding: 22px 28px;
}
.rc-student-details h2 {
    font-size: 1.25rem;
    font-weight: 800;
    color: #0f2548;
    margin: 0 0 14px;
    letter-spacing: -0.01em;
}
.rc-info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px 20px;
}
.rc-info-item label {
    display: block;
    font-size: 0.67rem;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #888;
    font-weight: 600;
    margin-bottom: 2px;
}
.rc-info-item span {
    display: block;
    font-size: 0.88rem;
    font-weight: 700;
    color: #1a1a2e;
}
.rc-photo-box {
    width: 130px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px 20px 16px 0;
    gap: 8px;
}
.rc-photo {
    width: 90px;
    height: 110px;
    border-radius: 8px;
    object-fit: cover;
    border: 3px solid {{ $schoolColor }};
}
.rc-photo-placeholder {
    width: 90px;
    height: 110px;
    border-radius: 8px;
    background: linear-gradient(135deg, #e8edf5, #c8d4e8);
    border: 3px solid {{ $schoolColor }};
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 38px;
}
.rc-curriculum-tag {
    font-size: 0.7rem;
    font-weight: 800;
    padding: 3px 12px;
    border-radius: 20px;
    letter-spacing: 0.05em;
    background: {{ $schoolColor }};
    color: #fff;
}

/* ─── Section labels ─── */
.rc-section-header {
    background: #f5f7fb;
    border-left: 4px solid {{ $schoolColor }};
    padding: 8px 20px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: {{ $schoolColor }};
}

/* ─── Academic table ─── */
.rc-marks-table {
    padding: 0 20px 4px;
}
.rc-marks-table table {
    width: 100%;
    border-collapse: collapse;
}
.rc-marks-table th {
    background: {{ $schoolColor }};
    color: #fff;
    padding: 9px 10px;
    font-size: 0.7rem;
    font-weight: 700;
    text-align: center;
    letter-spacing: 0.04em;
}
.rc-marks-table th:first-child { text-align: left; border-radius: 0; }
.rc-marks-table td {
    padding: 8px 10px;
    font-size: 0.82rem;
    border-bottom: 1px solid #f0f0f0;
    text-align: center;
    vertical-align: middle;
}
.rc-marks-table td:first-child { text-align: left; font-weight: 600; color: #1a1a2e; }
.rc-marks-table tr:nth-child(even) td { background: #fafbff; }
.rc-marks-table tfoot td {
    background: linear-gradient(90deg, {{ $schoolColor }}, #0f2548) !important;
    color: #fff !important;
    font-weight: 800;
    border-bottom: none;
}

/* ─── Grade badges ─── */
.grade-chip {
    display: inline-block;
    padding: 3px 11px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.03em;
}
.grade-A  { background: #d1fae5; color: #065f46; }
.grade-B  { background: #dbeafe; color: #1d4ed8; }
.grade-C  { background: #fef3c7; color: #92400e; }
.grade-D  { background: #fee2e2; color: #991b1b; }
.grade-E  { background: #fce7f3; color: #9d174d; }
.grade-EE { background: #d1fae5; color: #065f46; }
.grade-ME { background: #dbeafe; color: #1d4ed8; }
.grade-AE { background: #fef3c7; color: #92400e; }
.grade-BE { background: #fee2e2; color: #991b1b; }

/* ─── Summary stats ─── */
.rc-summary-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
    border-top: 2px solid #f0f0f0;
    border-bottom: 2px solid #f0f0f0;
    margin: 0 0 0;
}
.rc-stat {
    padding: 16px 14px;
    text-align: center;
    border-right: 1px solid #f0f0f0;
}
.rc-stat:last-child { border-right: none; }
.rc-stat-v {
    font-size: 1.6rem;
    font-weight: 900;
    color: {{ $schoolColor }};
    font-family: 'Playfair Display', serif;
    line-height: 1;
    margin-bottom: 4px;
}
.rc-stat-l {
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #888;
    font-weight: 600;
}

/* ─── Remarks / Signature section ─── */
.rc-remarks-section {
    padding: 20px 28px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    border-bottom: 2px solid #f0f0f0;
}
.rc-remark-box label {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #888;
    font-weight: 700;
    margin-bottom: 6px;
}
.rc-remark-box .remark-content {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 10px 14px;
    min-height: 60px;
    font-size: 0.85rem;
    color: #1a1a2e;
    line-height: 1.55;
    background: #fafbff;
}
.rc-sig-line {
    display: flex;
    gap: 20px;
    margin-top: 10px;
}
.rc-sig-line div { flex: 1; }
.rc-sig-line span {
    display: block;
    font-size: 0.68rem;
    text-transform: uppercase;
    color: #aaa;
    margin-bottom: 4px;
    letter-spacing: 0.05em;
}
.rc-sig-line .sig-blank {
    border-bottom: 1px solid #ccc;
    height: 28px;
    width: 100%;
}

/* ─── Parent section ─── */
.rc-parent-section {
    padding: 16px 28px;
    background: #f8faff;
    border-bottom: 2px solid #f0f0f0;
}
.rc-parent-section label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #888;
    font-weight: 700;
}
.rc-parent-sigs {
    display: flex;
    gap: 32px;
    margin-top: 8px;
}
.rc-parent-sigs .sig-entry span {
    font-size: 0.68rem;
    color: #aaa;
    text-transform: uppercase;
}
.rc-parent-sigs .sig-entry .sig-blank {
    border-bottom: 1px solid #ccc;
    height: 26px;
    width: 120px;
}

/* ─── Next term footer ─── */
.rc-next-term {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    background: linear-gradient(135deg, {{ $schoolColor }}, #0f2548);
    color: #fff;
    padding: 14px 28px;
}
.rc-next-term .nt-item { padding: 4px 0; }
.rc-next-term .nt-label { font-size: 0.68rem; opacity: 0.68; text-transform: uppercase; letter-spacing: 0.06em; }
.rc-next-term .nt-val   { font-size: 0.95rem; font-weight: 800; margin-top: 2px; }

/* ─── Footer note ─── */
.rc-footer-note {
    text-align: center;
    padding: 10px;
    font-size: 0.7rem;
    color: #aaa;
    background: #fff;
}
</style>
@endpush

@section('content')

{{-- ─── Screen controls (not printed) ─── --}}
<div class="rc-controls no-print">
    <div class="page-header-text" style="flex:1">
        <h1>📄 Report Card</h1>
        <p>{{ $examName }} — {{ $student->first_name }} {{ $student->last_name }}</p>
    </div>
    <button class="btn btn-primary" onclick="window.print()">
        <i class="bi bi-printer"></i> Print Report Card
    </button>
    <a href="{{ route('examinations.report-cards') }}" class="btn btn-outline">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

{{-- ════════════════════════════════════════════════════════════
     PRINTABLE REPORT CARD
     ════════════════════════════════════════════════════════════ --}}
<div class="rc-paper">

    {{-- ─── School Header ─── --}}
    <div class="rc-header">
        <div class="rc-logo-wrap">
            @if($school?->logo)
                <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $schoolName }} logo">
            @else
                🎓
            @endif
        </div>

        <div class="rc-school-info">
            <div class="rc-school-name">{{ $schoolName }}</div>
            <div class="rc-school-sub">
                @if($schoolAddr){{ $schoolAddr }}@endif
                @if($schoolAddr && ($schoolPhone || $schoolEmail)) &nbsp;|&nbsp; @endif
                @if($schoolPhone){{ $schoolPhone }}@endif
                @if($schoolPhone && $schoolEmail) &nbsp;|&nbsp; @endif
                @if($schoolEmail){{ $schoolEmail }}@endif
            </div>
            @if($schoolMotto)
            <div class="rc-school-motto">"{{ $schoolMotto }}"</div>
            @endif
        </div>

        <div class="rc-report-badge">
            Academic Progress<br>Report
            <span>{{ $examName }}</span>
            <span>{{ $academicYear }}</span>
        </div>
    </div>

    <div class="rc-stripe"></div>

    {{-- ─── Student Profile ─── --}}
    <div class="rc-student-section">
        <div class="rc-student-details">
            <h2>{{ strtoupper($student->first_name . ' ' . ($student->other_names ? $student->other_names . ' ' : '') . $student->last_name) }}</h2>
            <div class="rc-info-grid">
                <div class="rc-info-item">
                    <label>Admission No.</label>
                    <span>{{ $student->admission_number ?? '—' }}</span>
                </div>
                <div class="rc-info-item">
                    <label>NEMIS / UPI</label>
                    <span>{{ $student->nemis_upi ?? '—' }}</span>
                </div>
                <div class="rc-info-item">
                    <label>Gender</label>
                    <span>{{ $student->gender ?? '—' }}</span>
                </div>
                <div class="rc-info-item">
                    <label>Date of Birth</label>
                    <span>{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d M Y') : '—' }}</span>
                </div>
                <div class="rc-info-item">
                    <label>Class</label>
                    <span>{{ $student->schoolClass?->name ?? '—' }}</span>
                </div>
                <div class="rc-info-item">
                    <label>Stream</label>
                    <span>{{ $student->stream?->name ?? '—' }}</span>
                </div>
                <div class="rc-info-item">
                    <label>Academic Year</label>
                    <span>{{ $academicYear }}</span>
                </div>
                <div class="rc-info-item">
                    <label>Term</label>
                    <span>{{ $termName }}</span>
                </div>
                <div class="rc-info-item">
                    <label>Curriculum</label>
                    <span><span class="rc-curriculum-tag">{{ $student->curriculum ?? '8-4-4' }}</span></span>
                </div>
            </div>
        </div>

        <div class="rc-photo-box">
            @if($student->passport_photo)
                <img class="rc-photo" src="{{ asset('storage/' . $student->passport_photo) }}" alt="Passport Photo">
            @else
                <div class="rc-photo-placeholder">👤</div>
            @endif
            <div style="font-size:0.68rem;color:#aaa;text-align:center;line-height:1.3">Passport<br>Photo</div>
        </div>
    </div>

    {{-- ─── Academic Performance ─── --}}
    <div class="rc-section-header">📊 Academic Performance — All Examinations, {{ $academicYear }}</div>

    <div class="rc-marks-table" style="padding:12px 20px 8px">
        <table>
            <thead>
                <tr>
                    @unless($isCBC)
                    <th>Examination</th>
                    <th>Term</th>
                    @endunless
                    <th style="text-align:left;padding-left:12px">Subject</th>
                    @if($isCBC)
                        <th>Strand / Sub-strand</th>
                        <th>Level</th>
                        <th>Achievement</th>
                    @else
                        <th>Score</th>
                        <th>Out Of</th>
                        <th>%</th>
                        <th>Grade</th>
                        <th>Points</th>
                        <th>Position</th>
                    @endif
                    <th style="text-align:left">Teacher Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($yearExams as $yearExam)
                @php $examResults = $yearResultsByExam->get($yearExam->id, collect()); @endphp
                <tr>
                    <td colspan="{{ $isCBC ? 5 : 10 }}" style="background:#eaf0f8;color:#1a3c6e;font-weight:800;text-align:left;padding:10px 12px">
                        {{ $academicYear }} · {{ $yearExam->name }} — {{ $yearExam->term?->name ?? 'Term not specified' }}
                    </td>
                </tr>
                @forelse($examResults as $result)
                @php
                    if ($isCBC) {
                        $rubric = $result->achievement_level ?? 'ME';
                        $achievementMap = ['EE'=>'Exceeds Expectations','ME'=>'Meets Expectations','AE'=>'Approaches Expectations','BE'=>'Below Expectations'];
                        $achievement = $achievementMap[$rubric] ?? $rubric;
                    } else {
                        $pct = $result->out_of > 0 ? round($result->score / $result->out_of * 100, 1) : 0;
                        $gradeKey = strtoupper(substr($result->grade ?? '', 0, 1));
                        $positionKey = $result->examination_id . '_' . $result->subject_id;
                        $pos = $yearSubjectPositions[$positionKey] ?? '—';
                    }
                @endphp
                <tr>
                    @unless($isCBC)
                    <td style="font-weight:700">{{ $yearExam->name }}</td>
                    <td>{{ $yearExam->term?->name ?? '—' }}</td>
                    @endunless
                    <td style="padding-left:12px">
                        {{ $result->subject?->name ?? 'Unknown' }}
                        @if($result->subject?->code)
                            <small style="color:#aaa;font-weight:400;font-size:0.72rem">({{ $result->subject->code }})</small>
                        @endif
                    </td>

                    @if($isCBC)
                        <td>{{ $result->sub_strand ?? $result->strand ?? '—' }}</td>
                        <td>
                            <span class="grade-chip grade-{{ $rubric }}">{{ $rubric }}</span>
                        </td>
                        <td style="font-size:0.8rem">{{ $achievement }}</td>
                    @else
                        <td style="font-weight:800;font-size:1rem">{{ number_format($result->score, 1) }}</td>
                        <td style="color:#888">{{ $result->out_of }}</td>
                        <td style="font-weight:700;color:{{ $pct >= 70 ? '#059669' : ($pct >= 50 ? '#e8922a' : '#dc2626') }}">
                            {{ $pct }}%
                        </td>
                        <td>
                            <span class="grade-chip grade-{{ $gradeKey }}">{{ $result->grade ?? '—' }}</span>
                        </td>
                        <td style="font-weight:700">{{ $result->points ?? '—' }}</td>
                        <td style="font-weight:700">{{ is_numeric($pos) ? ordinal($pos) : $pos }}</td>
                    @endif

                    <td style="font-size:0.8rem;color:#555;text-align:left;padding-left:10px">
                        {{ $result->remarks ?? ($isCBC ? ($achievement) : ($pct >= 70 ? 'Excellent' : ($pct >= 50 ? 'Good' : 'Needs Improvement'))) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isCBC ? 5 : 10 }}" style="padding:10px 12px;text-align:center;color:#94a3b8;">
                        No assessment records found for this examination and term.
                    </td>
                </tr>
                @endforelse
                @endforeach
                @if($yearExams->isEmpty())
                <tr>
                    <td colspan="{{ $isCBC ? 5 : 10 }}" style="padding:24px;text-align:center;color:#aaa;">
                        No examinations found for this academic year.
                    </td>
                </tr>
                @endif
            </tbody>
            @if(!$isCBC && $yearMarks844->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="3" style="padding-left:12px">ACADEMIC YEAR TOTAL / AVERAGE</td>
                    <td>{{ number_format($yearTotalScore, 1) }}</td>
                    <td>{{ number_format($yearTotalOutOf, 1) }}</td>
                    <td>{{ $yearAverage ?? '—' }}{{ $yearAverage !== null ? '%' : '' }}</td>
                    <td><span class="grade-chip" style="background:rgba(255,255,255,0.2);color:#fff">{{ $yearOverallGrade ?? '—' }}</span></td>
                    <td>{{ $yearMarks844->avg('points') !== null ? number_format($yearMarks844->avg('points'), 2) : '—' }}</td>
                    <td>—</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    {{-- ─── Summary Stats ─── --}}
    <div class="rc-section-header">Selected Examination Summary — {{ $examName }}</div>
    <div class="rc-summary-stats">
        <div class="rc-stat">
            <div class="rc-stat-v">{{ $overallGrade ?? '—' }}</div>
            <div class="rc-stat-l">Mean Grade</div>
        </div>
        <div class="rc-stat">
            <div class="rc-stat-v">
                @if($overallPosition && $classTotal)
                    {{ $overallPosition }}<small style="font-size:1rem">/{{ $classTotal }}</small>
                @elseif($overallPosition)
                    {{ ordinal($overallPosition) }}
                @else
                    —
                @endif
            </div>
            <div class="rc-stat-l">Class Position</div>
        </div>
        <div class="rc-stat">
            <div class="rc-stat-v">{{ $avgPercent !== null ? $avgPercent . '%' : '—' }}</div>
            <div class="rc-stat-l">Average Score</div>
        </div>
        <div class="rc-stat">
            <div class="rc-stat-v">{{ $attendanceRate !== null ? $attendanceRate . '%' : '—' }}</div>
            <div class="rc-stat-l">Attendance Rate</div>
        </div>
    </div>

    {{-- ─── Remarks Section ─── --}}
    <div class="rc-section-header" style="margin-top:0">💬 Teacher Remarks & Signatures</div>

    <div class="rc-remarks-section">
        <div class="rc-remark-box">
            <label>Class Teacher's Remarks</label>
            <div class="remark-content">
                {{ $teacherComment ?? 'A dedicated student who shows great potential. Keep it up!' }}
            </div>
            <div class="rc-sig-line">
                <div>
                    <span>Signature</span>
                    <div class="sig-blank"></div>
                </div>
                <div>
                    <span>Date</span>
                    <div class="sig-blank"></div>
                </div>
                <div>
                    <span>Official Stamp</span>
                    <div class="sig-blank"></div>
                </div>
            </div>
        </div>

        <div class="rc-remark-box">
            <label>Head Teacher's Remarks</label>
            <div class="remark-content">
                {{ $headComment ?? 'Well done. Continue working hard and strive for excellence.' }}
            </div>
            <div class="rc-sig-line">
                <div>
                    <span>Signature</span>
                    <div class="sig-blank"></div>
                </div>
                <div>
                    <span>Date</span>
                    <div class="sig-blank"></div>
                </div>
                <div>
                    <span>Official Stamp</span>
                    <div class="sig-blank"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Parent / Guardian section ─── --}}
    <div class="rc-parent-section">
        <label>Parent / Guardian Acknowledgement</label>
        <div class="rc-parent-sigs">
            <div class="sig-entry">
                <span>Parent/Guardian Comment</span>
                <div class="sig-blank" style="width:260px;margin-top:4px"></div>
            </div>
            <div class="sig-entry">
                <span>Signature</span>
                <div class="sig-blank" style="width:120px;margin-top:4px"></div>
            </div>
            <div class="sig-entry">
                <span>Date</span>
                <div class="sig-blank" style="width:100px;margin-top:4px"></div>
            </div>
        </div>
    </div>

    {{-- ─── Next Term Info ─── --}}
    <div class="rc-next-term">
        <div class="nt-item">
            <div class="nt-label">Next Term Opens</div>
            <div class="nt-val">{{ '—' }}</div>
        </div>
        <div class="nt-item">
            <div class="nt-label">Examination</div>
            <div class="nt-val">{{ $examName }}</div>
        </div>
        <div class="nt-item">
            <div class="nt-label">Next Class</div>
            <div class="nt-val">{{ $student->schoolClass?->name ?? '—' }}</div>
        </div>
    </div>

    <div class="rc-footer-note">
        This report card was generated by CloudKazi Elimu SMIS on {{ now()->format('d M Y, H:i') }}
        &nbsp;|&nbsp; Not valid without the school's official stamp
        &nbsp;|&nbsp; {{ $schoolName }}
    </div>

</div>{{-- .rc-paper --}}

@endsection

@php
function ordinal(int $n): string {
    $suffix = ['th','st','nd','rd'];
    $v = $n % 100;
    return $n . ($suffix[($v - 20) % 10] ?? $suffix[$v] ?? $suffix[0]);
}
@endphp
