@extends('layouts.app')
@section('title', 'Fee Structures & Vote-Heads')
@section('breadcrumb', 'Finance › Fee Structures')

@php
    $feeStructureGroups = $feeStructures->groupBy(fn ($structure) => implode('|', [
        $structure->school_class_id,
        $structure->academic_year_id,
        $structure->term_id,
    ]));
    $feeStructureSchool = auth()->user()?->school
        ?? \App\Models\School::find(session('active_school_id'));
@endphp

@push('styles')
<style>
.fee-schedule-grid {
    display: grid;
    gap: 18px;
    margin-bottom: 24px;
}
.fee-schedule-sheet {
    overflow: hidden;
    border: 1px solid #d8e0ea;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
}
.fee-schedule-heading {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px 24px;
    border-bottom: 1px solid #d8e0ea;
    background: linear-gradient(135deg, #f8fafc, #eef4fb);
}
.fee-schedule-logo {
    width: 64px;
    height: 64px;
    flex: 0 0 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 1px solid #dbe3ec;
    border-radius: 50%;
    background: #fff;
    color: #1e3a5f;
    font-size: 1.5rem;
    font-weight: 800;
}
.fee-schedule-logo img { width: 100%; height: 100%; object-fit: cover; }
.fee-schedule-school { flex: 1; min-width: 0; }
.fee-schedule-school h2 { margin: 0; color: #17365d; font-size: 1.15rem; font-weight: 800; }
.fee-schedule-school p { margin: 3px 0 0; color: #64748b; font-size: 0.78rem; }
.fee-schedule-title {
    flex: 0 0 auto;
    text-align: right;
    color: #17365d;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.fee-schedule-title strong { display: block; margin-top: 4px; color: #c47b18; font-size: 1rem; letter-spacing: 0; }
.fee-schedule-meta {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    padding: 16px 24px;
    border-bottom: 1px solid #e2e8f0;
}
.fee-schedule-meta div { min-width: 0; }
.fee-schedule-meta span { display: block; color: #64748b; font-size: 0.65rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase; }
.fee-schedule-meta strong { display: block; margin-top: 3px; color: #1e293b; font-size: 0.88rem; }
.fee-schedule-table-wrap { padding: 12px 24px 0; }
.fee-schedule-table { width: 100%; border-collapse: collapse; }
.fee-schedule-table th {
    padding: 10px 12px;
    background: #17365d;
    color: #fff;
    font-size: 0.7rem;
    letter-spacing: 0.04em;
    text-align: left;
    text-transform: uppercase;
}
.fee-schedule-table th:last-child, .fee-schedule-table td:last-child { text-align: right; }
.fee-schedule-table td { padding: 10px 12px; border-bottom: 1px solid #e8edf3; color: #334155; font-size: 0.84rem; }
.fee-schedule-table tbody tr:nth-child(even) { background: #f8fafc; }
.fee-schedule-total {
    display: flex;
    justify-content: flex-end;
    align-items: baseline;
    gap: 28px;
    padding: 15px 36px;
    background: #f1f5f9;
    color: #17365d;
    font-size: 0.82rem;
    font-weight: 800;
    text-transform: uppercase;
}
.fee-schedule-total strong { color: #0f172a; font-size: 1.15rem; }
.fee-schedule-signatures {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    padding: 28px 24px 20px;
    color: #475569;
    font-size: 0.72rem;
}
.fee-schedule-signatures span { display: block; padding-top: 6px; border-top: 1px solid #94a3b8; }
.fee-schedule-note { padding: 0 24px 16px; color: #64748b; font-size: 0.7rem; }
@media (max-width: 700px) {
    .fee-schedule-heading { align-items: flex-start; flex-wrap: wrap; padding: 16px; }
    .fee-schedule-title { margin-left: 80px; text-align: left; }
    .fee-schedule-meta { grid-template-columns: repeat(2, 1fr); padding: 14px 16px; }
    .fee-schedule-table-wrap { overflow-x: auto; padding: 10px 12px 0; }
    .fee-schedule-table { min-width: 480px; }
}
@media print {
    @page { size: A4 portrait; margin: 12mm; }
    html, body { margin: 0 !important; padding: 0 !important; background: #fff !important; }
    .sidebar, .topbar, .page-header, .fee-screen-only, .legacy-fee-list,
    #modal-category, #modal-fee, nav, header, footer { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
    .page-content { margin: 0 !important; padding: 0 !important; }
    .fee-schedule-grid { display: block; margin: 0; }
    .fee-schedule-sheet {
        display: block;
        margin: 0 0 10mm;
        border: 1px solid #94a3b8;
        border-radius: 0;
        box-shadow: none;
        break-after: page;
        page-break-after: always;
    }
    .fee-schedule-sheet:last-child { break-after: auto; page-break-after: auto; }
    .fee-schedule-heading, .fee-schedule-table th, .fee-schedule-total {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    .fee-schedule-table tr, .fee-schedule-signatures { break-inside: avoid; page-break-inside: avoid; }
}
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📊 Fee Structures & Vote-Heads Breakdown</h1>
        <p>Configure termly statutory fees, tuition, boarding, activity, and development levies per class</p>
    </div>
    <div class="fee-screen-only" style="display:flex;gap:10px">
        <button class="btn btn-outline" onclick="window.print()">
            <i class="bi bi-printer"></i> Print Fee Schedules
        </button>
        <button class="btn btn-outline" onclick="document.getElementById('modal-category').style.display='flex'">
            <i class="bi bi-tag"></i> Add Fee Category
        </button>
        <button class="btn btn-primary" onclick="document.getElementById('modal-fee').style.display='flex'">
            <i class="bi bi-plus-circle"></i> Add Fee Item
        </button>
    </div>
</div>

@if($feeStructureGroups->isNotEmpty())
<div class="fee-schedule-grid">
    @foreach($feeStructureGroups as $structures)
        @php
            $firstStructure = $structures->first();
            $scheduleTotal = $structures->sum('amount');
        @endphp
        <section class="fee-schedule-sheet">
            <div class="fee-schedule-heading">
                <div class="fee-schedule-logo">
                    @if($feeStructureSchool?->logo)
                        <img src="{{ asset('storage/' . $feeStructureSchool->logo) }}" alt="{{ $feeStructureSchool->name }} logo">
                    @else
                        {{ strtoupper(substr($feeStructureSchool?->name ?? 'S', 0, 1)) }}
                    @endif
                </div>
                <div class="fee-schedule-school">
                    <h2>{{ $feeStructureSchool?->name ?? 'School Fee Schedule' }}</h2>
                    <p>
                        {{ $feeStructureSchool?->address ?? $feeStructureSchool?->county ?? '' }}
                        @if($feeStructureSchool?->phone) &nbsp;|&nbsp; {{ $feeStructureSchool->phone }} @endif
                        @if($feeStructureSchool?->email) &nbsp;|&nbsp; {{ $feeStructureSchool->email }} @endif
                    </p>
                    @if($feeStructureSchool?->motto)
                        <p><em>{{ $feeStructureSchool->motto }}</em></p>
                    @endif
                </div>
                <div class="fee-schedule-title">
                    Official fee schedule
                    <strong>Vote-head breakdown</strong>
                </div>
            </div>

            <div class="fee-schedule-meta">
                <div><span>Class</span><strong>{{ $firstStructure->schoolClass?->name ?? '—' }}</strong></div>
                <div><span>Curriculum</span><strong>{{ $firstStructure->schoolClass?->curriculum ?? '—' }}</strong></div>
                <div><span>Academic Year</span><strong>{{ $firstStructure->academicYear?->name ?? '—' }}</strong></div>
                <div><span>Term</span><strong>{{ $firstStructure->term?->name ?? '—' }}</strong></div>
            </div>

            <div class="fee-schedule-table-wrap">
                <table class="fee-schedule-table">
                    <thead>
                        <tr><th style="width:50px">No.</th><th>Fee item / Vote-head</th><th>Description</th><th>Amount (KES)</th></tr>
                    </thead>
                    <tbody>
                        @foreach($structures as $index => $structure)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $structure->category?->name ?? 'Fee item' }}</strong></td>
                                <td>{{ $structure->category?->description ?: '—' }}</td>
                                <td>{{ number_format($structure->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="fee-schedule-total">
                <span>Total fees for this term</span>
                <strong>KES {{ number_format($scheduleTotal, 2) }}</strong>
            </div>

            <div class="fee-schedule-signatures">
                <div><span>Prepared by / Bursar</span></div>
                <div><span>Approved by / Head of School</span></div>
            </div>
            <div class="fee-schedule-note">Please use the student's admission number as the payment reference. Keep this schedule for your records.</div>
        </section>
    @endforeach
</div>
@endif

@if($categories->isEmpty())
<div class="fee-screen-only" style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:16px;margin-bottom:16px;display:flex;align-items:center;gap:12px">
    <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b;font-size:1.4rem"></i>
    <div>
        <strong style="color:#92400e">No fee categories configured for this school.</strong>
        <div style="font-size:0.85rem;color:#78350f">Create at least one fee category (e.g. Tuition, Boarding, Activity) before adding fee structures. Click <strong>Add Fee Category</strong> above.</div>
    </div>
</div>
@endif

<div class="card legacy-fee-list">
    <div class="card-header">
        <h5><i class="bi bi-card-checklist"></i> Approved Fee Structures</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Term & Year</th>
                    <th>Fee Category / Vote-Head</th>
                    <th>Amount (KES)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($feeStructures as $fs)
                <tr>
                    <td>
                        <strong style="color:var(--primary)">{{ $fs->schoolClass?->name }}</strong>
                    </td>
                    <td>
                        <div>{{ $fs->academicYear?->name ?? '2026' }}</div>
                        <span class="badge badge-gray">{{ $fs->term?->name ?? 'Term 1' }}</span>
                    </td>
                    <td>
                        <span class="badge badge-outline" style="border:1px solid #cbd5e1;font-weight:600">
                            {{ $fs->category?->name ?? 'Tuition & Academics' }}
                        </span>
                    </td>
                    <td>
                        <strong style="font-family:'Outfit',sans-serif;font-size:1.05rem;color:var(--text-dark)">
                            KES {{ number_format($fs->amount, 2) }}
                        </strong>
                    </td>
                    <td>
                        <span class="badge badge-success">Approved</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="no-data">No fee structures configured yet. Click "Add Fee Item".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Fee Category -->
<div id="modal-category" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:440px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-tag"></i> Add Fee Category / Vote-Head</h5>
            <button onclick="document.getElementById('modal-category').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('finance.category.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Tuition, Boarding, Activity Levy">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Optional description...">
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-category').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Fee Structure Item -->
<div id="modal-fee" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:999;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:520px;background:#fff;border-radius:12px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <h5><i class="bi bi-plus-circle"></i> Add Fee Structure Vote-Head</h5>
            <button onclick="document.getElementById('modal-fee').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('finance.structure.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Target Class *</label>
                    <select name="school_class_id" class="form-control" required>
                        @forelse($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->curriculum }})</option>
                        @empty
                            <option disabled>No classes found for this school</option>
                        @endforelse
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Vote-Head / Category *</label>
                    <select name="fee_category_id" class="form-control" required>
                        @forelse($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @empty
                            <option disabled>No categories — create one first</option>
                        @endforelse
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Academic Year *</label>
                        <select name="academic_year_id" class="form-control" required>
                            @foreach($years as $y)
                                <option value="{{ $y->id }}">{{ $y->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Term *</label>
                        <select name="term_id" class="form-control" required>
                            @foreach($terms as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount (KES) *</label>
                    <input type="number" name="amount" class="form-control" placeholder="e.g. 15000" min="0" step="50" required>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-fee').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Vote-Head</button>
            </div>
        </form>
    </div>
</div>
@endsection
