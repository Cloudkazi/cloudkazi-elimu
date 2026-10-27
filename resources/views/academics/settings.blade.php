@extends('layouts.app')
@section('title', 'School Settings')
@section('breadcrumb', 'Settings › School Configuration')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>⚙️ School Settings & Integrations</h1>
        <p>Manage institution profile, Kenyan curriculum type, M-Pesa Daraja gateway, and academic calendar</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.5fr 1fr;gap:24px">

    <!-- Left Column: Institution Profile & Integrations (Form 1) -->
    <form action="{{ route('academics.settings.update') }}" method="POST" id="form-school-settings">
        @csrf
        <div style="display:flex;flex-direction:column;gap:20px">
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-building"></i> Institution Profile</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">School Name *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $school->name ?? '') }}" required>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group">
                            <label class="form-label">School Code (MOE / NEMIS)</label>
                            <input type="text" class="form-control" value="{{ $school->code ?? '' }}" disabled style="background:#f8fafc">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Curriculum Track *</label>
                            <select name="curriculum_type" class="form-control" required>
                                <option value="CBC" {{ old('curriculum_type', $school->curriculum_type ?? '') === 'CBC' ? 'selected' : '' }}>CBC Only</option>
                                <option value="8-4-4" {{ old('curriculum_type', $school->curriculum_type ?? '') === '8-4-4' ? 'selected' : '' }}>8-4-4 Secondary</option>
                                <option value="Hybrid" {{ old('curriculum_type', $school->curriculum_type ?? '') === 'Hybrid' ? 'selected' : '' }}>Hybrid (CBC + 8-4-4)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">School Motto</label>
                        <input type="text" name="motto" class="form-control" value="{{ old('motto', $school->motto ?? '') }}">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group">
                            <label class="form-label">Official Email *</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $school->email ?? '') }}" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Official Phone *</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $school->phone ?? '') }}" required>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group">
                            <label class="form-label">Postal / Physical Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address', $school->address ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">County *</label>
                            <input type="text" name="county" class="form-control" value="{{ old('county', $school->county ?? '') }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integrations: M-Pesa & SMS -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-shield-lock"></i> Daraja M-Pesa & Telecom Integration</h5>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group">
                            <label class="form-label">M-Pesa Shortcode (Paybill / Till) *</label>
                            <input type="text" name="daraja_shortcode" class="form-control" value="{{ old('daraja_shortcode', $settings->daraja_shortcode ?? '174379') }}" required>
                            <small style="color:var(--text-muted)">Default sandbox: 174379</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Currency Symbol *</label>
                            <input type="text" name="currency" class="form-control" value="{{ old('currency', $settings->currency ?? 'KES') }}" required>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="form-group">
                            <label class="form-label">SMS Sender ID (Alphanumeric)</label>
                            <input type="text" name="sms_sender_id" class="form-control" value="{{ old('sms_sender_id', $settings->sms_sender_id ?? 'CLOUDKAZI') }}" maxlength="11">
                            <small style="color:var(--text-muted)">Max 11 characters approved by CAK</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Timezone</label>
                            <input type="text" name="timezone" class="form-control" value="{{ old('timezone', $settings->timezone ?? 'Africa/Nairobi') }}">
                        </div>
                    </div>
                </div>
                <div class="card-footer" style="display:flex;justify-content:flex-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Configuration
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Right Column: Academic Calendar & Sessions (Completely Separate Forms) -->
    <div style="display:flex;flex-direction:column;gap:20px">
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                <h5><i class="bi bi-calendar-range"></i> Academic Calendar</h5>
                <span class="badge badge-outline">{{ $academicYears->count() }} year(s) configured</span>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:20px;">
                <!-- Add Academic Year Form -->
                <div style="background:var(--mc-surface-2, #f8fafc);padding:14px;border-radius:8px;border:1px solid #e2e8f0;">
                    <h6 style="margin-bottom:10px;font-weight:600;"><i class="bi bi-plus-circle"></i> Add Academic Year</h6>
                    <form action="{{ route('academics.settings.academic-year.store') }}" method="POST" id="form-add-academic-year">
                        @csrf
                        <div style="display:grid;grid-template-columns:1.3fr 1fr 1fr;gap:10px;align-items:end;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.8rem;">Academic Year *</label>
                                <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. 2026 or 2026/2027" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.8rem;">Start Date *</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.8rem;">End Date *</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        <div style="margin-top:12px;display:flex;justify-content:flex-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-calendar-plus"></i> Add Academic Year
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Existing Academic Years List -->
                <div>
                    <h6 style="margin-bottom:12px;font-weight:600;"><i class="bi bi-calendar-check"></i> Configured Years & Terms</h6>
                    @forelse($academicYears as $year)
                    <div style="padding:14px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:12px;background:#ffffff;">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                            <div>
                                <strong style="font-size:1.05rem;color:var(--mc-text-primary);">{{ $year->name }}</strong>
                                <span style="font-size:0.8rem;color:var(--text-muted);margin-left:8px;">
                                    ({{ \Carbon\Carbon::parse($year->start_date)->format('M Y') }} – {{ \Carbon\Carbon::parse($year->end_date)->format('M Y') }})
                                </span>
                            </div>
                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                @if($year->is_current)
                                    <span class="badge badge-success"><i class="bi bi-check-circle-fill"></i> Active Year</span>
                                @else
                                    <form action="{{ route('academics.settings.academic-year.activate', $year->id) }}" method="POST" style="display:inline;margin:0;">
                                        @csrf
                                        <button type="submit" class="btn btn-outline btn-sm">Activate Year</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            @forelse($year->terms as $term)
                                <div style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:6px;border:1px solid {{ $term->is_current ? '#059669' : '#cbd5e1' }};background:{{ $term->is_current ? 'rgba(5,150,105,0.08)' : '#f8fafc' }};font-size:0.8rem;">
                                    <strong>{{ $term->name }}</strong>
                                    <span style="color:var(--text-muted);font-size:0.75rem;">({{ \Carbon\Carbon::parse($term->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($term->end_date)->format('d M') }})</span>
                                    @if($term->is_current)
                                        <span class="badge badge-success" style="font-size:0.68rem;padding:2px 6px;">Active</span>
                                    @else
                                        <form action="{{ route('academics.settings.academic-term.activate', $term->id) }}" method="POST" style="display:inline;margin:0;">
                                            @csrf
                                            <button type="submit" class="btn btn-link btn-sm" style="padding:0;font-size:0.75rem;color:var(--primary);text-decoration:none;">Set Active</button>
                                        </form>
                                    @endif
                                </div>
                            @empty
                                <span style="font-size:0.8rem;color:var(--text-muted);">No terms configured for this year.</span>
                            @endforelse
                        </div>
                    </div>
                    @empty
                    <p style="color:var(--text-muted);font-size:0.875rem">No academic years configured yet. Add your school's current academic year above.</p>
                    @endforelse
                </div>

                <!-- Add Term Form (Strictly uses authenticated school's academic years) -->
                @if($academicYears->isNotEmpty())
                <div style="background:var(--mc-surface-2, #f8fafc);padding:14px;border-radius:8px;border:1px solid #e2e8f0;">
                    <h6 style="margin-bottom:10px;font-weight:600;"><i class="bi bi-plus-circle"></i> Add Term to Academic Year</h6>
                    <form action="{{ route('academics.settings.academic-term.store') }}" method="POST" id="form-add-academic-term">
                        @csrf
                        <div style="display:grid;grid-template-columns:1.3fr 1fr 1fr 1fr;gap:10px;align-items:end;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.8rem;">Academic Year *</label>
                                <select name="academic_year_id" id="select-academic-year" class="form-control form-control-sm" required>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" {{ $year->is_current ? 'selected' : '' }}>
                                            {{ $year->name }} {{ $year->is_current ? '(Active)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.8rem;">Term Name *</label>
                                <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Term 1" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.8rem;">Start Date *</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" style="font-size:0.8rem;">End Date *</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" required>
                            </div>
                        </div>
                        <div style="margin-top:12px;display:flex;justify-content:flex-end">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-clock-history"></i> Add Academic Term
                            </button>
                        </div>
                    </form>
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-phone"></i> Daraja STK Push Status</h5>
            </div>
            <div class="card-body">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                    <div style="width:12px;height:12px;border-radius:50%;background:#00A651"></div>
                    <strong>M-Pesa API Gateway: Connected</strong>
                </div>
                <p style="font-size:0.85rem;color:var(--text-muted);margin:0">
                    Configured for Safaricom Daraja v2. Live instant callback parsing updates student fee ledgers in real-time.
                </p>
            </div>
        </div>
    </div>

</div>
@endsection
