@extends('layouts.app')
@section('title', isset($student) ? 'Edit Student' : 'Enroll Student')
@section('breadcrumb', isset($student) ? 'Students › Edit' : 'Students › Enroll New')

@section('content')

<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px">
        <div style="display:flex;align-items:center;gap:14px">
            <div style="width:48px;height:48px;border-radius:12px;background:var(--primary-color,#4f46e5);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0">
                {{ isset($student) ? '✏️' : '👤' }}
            </div>
            <div>
                <h2 style="margin:0;font-size:1.25rem;font-weight:700;color:var(--text-primary,#1e293b)">
                    {{ isset($student) ? 'Edit Student Record' : 'Enroll New Student' }}
                </h2>
                <p style="margin:2px 0 0;font-size:0.875rem;color:var(--text-muted,#64748b)">
                    {{ isset($student) ? 'Update student information and documents' : 'Complete student registration form' }}
                </p>
            </div>
        </div>
        <a href="{{ route('students.index') }}" class="btn btn-outline" style="white-space:nowrap">
            <i class="bi bi-arrow-left"></i> Back to Students
        </a>
    </div>
</div>

<!-- Wizard Steps Card -->
<div class="card" style="margin-bottom:28px">
    <div class="card-body" style="padding:20px 24px">
        <div class="wizard-steps" style="margin:0">
            <div class="wizard-step active">
                <div class="wizard-step-num">1</div>
                <span style="font-size:0.8rem;font-weight:600">Personal Info</span>
            </div>
            <div class="wizard-step-line"></div>
            <div class="wizard-step">
                <div class="wizard-step-num">2</div>
                <span style="font-size:0.8rem;font-weight:600">Academic Placement</span>
            </div>
            <div class="wizard-step-line"></div>
            <div class="wizard-step">
                <div class="wizard-step-num">3</div>
                <span style="font-size:0.8rem;font-weight:600">Parent/Guardian</span>
            </div>
            <div class="wizard-step-line"></div>
            <div class="wizard-step">
                <div class="wizard-step-num">4</div>
                <span style="font-size:0.8rem;font-weight:600">Health & Docs</span>
            </div>
        </div>
    </div>
</div>

<form action="{{ isset($student) ? route('students.update', $student) : route('students.store') }}"
      method="POST" enctype="multipart/form-data">
    @csrf
    @if(isset($student)) @method('PUT') @endif

    <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">

        <!-- Main Form -->
        <div>
            <!-- SECTION 1: Personal Information -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-person-circle"></i> Personal Information</h5>
                    <span class="badge badge-primary">Step 1</span>
                </div>
                <div class="card-body">
                    <div class="three-col">
                        <div class="form-group">
                            <label class="form-label">First Name <span style="color:red">*</span></label>
                            <input type="text" name="first_name" class="form-control" required
                                value="{{ old('first_name', $student?->first_name ?? '') }}"
                                placeholder="e.g. Amina">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Middle / Other Names</label>
                            <input type="text" name="other_names" class="form-control"
                                value="{{ old('other_names', old('middle_name', $student?->other_names ?? '')) }}"
                                placeholder="Optional">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name <span style="color:red">*</span></label>
                            <input type="text" name="last_name" class="form-control" required
                                value="{{ old('last_name', $student?->last_name ?? '') }}"
                                placeholder="e.g. Odhiambo">
                        </div>
                    </div>
                    <div class="two-col">
                        <div class="form-group">
                            <label class="form-label">Date of Birth <span style="color:red">*</span></label>
                            <input type="date" name="dob" class="form-control" required
                                value="{{ old('dob', old('date_of_birth', $student?->dob?->format('Y-m-d') ?? '')) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gender <span style="color:red">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="Male" {{ old('gender', $student?->gender ?? '') === 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender', $student?->gender ?? '') === 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="two-col">
                        <div class="form-group">
                            <label class="form-label">Birth Certificate No.</label>
                            <input type="text" name="birth_certificate_no" class="form-control"
                                value="{{ old('birth_certificate_no', $student->birth_certificate_no ?? '') }}"
                                placeholder="e.g. 1234567">
                        </div>
                        <div class="form-group">
                            <label class="form-label">NEMIS UPI</label>
                            <input type="text" name="nemis_upi" class="form-control"
                                value="{{ old('nemis_upi', $student->nemis_upi ?? '') }}"
                                placeholder="NEMIS UPI Number">
                        </div>
                    </div>
                    <div class="two-col">
                        <div class="form-group">
                            <label class="form-label">Nationality</label>
                            <input type="text" name="nationality" class="form-control"
                                value="{{ old('nationality', $student->nationality ?? 'Kenyan') }}"
                                placeholder="Kenyan">
                        </div>
                        <div class="form-group">
                            <label class="form-label">County of Origin</label>
                            <select name="county" class="form-select">
                                <option value="">Select County</option>
                                @foreach(['Nairobi','Mombasa','Kisumu','Nakuru','Eldoret','Kiambu','Machakos','Meru','Nyeri','Kakamega','Kisii','Migori','Bungoma','Uasin Gishu','Siaya','Homabay','Bomet','Kericho','Nyandarua','Kirinyaga','Muranga','Nyamira','Embu','Isiolo','Marsabit','Samburu','Trans Nzoia','West Pokot','Baringo','Laikipia','Nandi','Elgeyo-Marakwet','Vihiga','Busia','Tran Mara','Narok','Kajiado','Makueni','Kitui','Tana River','Kilifi','Kwale','Taita-Taveta','Lamu','Garissa','Mandera','Wajir','Turkana','Pokot','Samburu'] as $county)
                                <option value="{{ $county }}"
                                    {{ old('county', $student->county ?? '') === $county ? 'selected' : '' }}>
                                    {{ $county }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Physical Address / Home Village</label>
                        <textarea name="address" class="form-control" rows="2"
                            placeholder="Physical address...">{{ old('address', $student?->address ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: Academic Placement -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-mortarboard"></i> Academic Placement</h5>
                    <span class="badge badge-primary">Step 2</span>
                </div>
                <div class="card-body">
                    <div class="two-col">
                        <div class="form-group">
                            <label class="form-label">Class / Grade <span style="color:red">*</span></label>
                            <select name="school_class_id" id="school_class_id" class="form-select" required onchange="filterStreamsByClass()">
                                <option value="">Select Class</option>
                                @foreach($classes ?? [] as $class)
                                <option value="{{ $class->id }}"
                                    {{ old('school_class_id', $student?->school_class_id ?? '') == $class->id ? 'selected' : '' }}>
                                    {{ $class->name ?? $class->class_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stream / Section</label>
                            <select name="stream_id" id="stream_id" class="form-select">
                                <option value="">Select Stream (Optional)</option>
                                @foreach($streams ?? [] as $stream)
                                <option value="{{ $stream->id }}"
                                    data-class="{{ $stream->school_class_id }}"
                                    {{ old('stream_id', $student?->stream_id ?? '') == $stream->id ? 'selected' : '' }}>
                                    {{ $stream->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="two-col">
                        <div class="form-group">
                            <label class="form-label">Admission Number</label>
                            <input type="text" name="admission_number" class="form-control"
                                value="{{ old('admission_number', $student?->admission_number ?? $suggestedAdmission ?? $nextAdmNo ?? '') }}"
                                placeholder="Auto-generated if blank">
                            <div class="form-text">Auto-suggested (or leave blank to auto-generate)</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Admission Date <span style="color:red">*</span></label>
                            <input type="date" name="admission_date" class="form-control" required
                                value="{{ old('admission_date', $student?->admission_date?->format('Y-m-d') ?? date('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="two-col">
                        <div class="form-group">
                            <label class="form-label">Curriculum <span style="color:red">*</span></label>
                            <select name="curriculum" class="form-select" required>
                                <option value="CBC" {{ old('curriculum', old('curriculum_type', $student->curriculum ?? '')) === 'CBC' ? 'selected' : '' }}>CBC (Competency Based)</option>
                                <option value="8-4-4" {{ old('curriculum', old('curriculum_type', $student->curriculum ?? '')) === '8-4-4' ? 'selected' : '' }}>8-4-4</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Student Status</label>
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', $student?->status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="suspended" {{ old('status', $student?->status ?? '') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                <option value="transferred" {{ old('status', $student?->status ?? '') === 'transferred' ? 'selected' : '' }}>Transferred</option>
                            </select>
                        </div>
                    </div>
                    <div class="two-col">
                        <div class="form-group">
                            <label class="form-label">Fee Profile &amp; Boarding Status <span style="color:red">*</span></label>
                            <select name="fee_profile" class="form-select" required>
                                <option value="regular" {{ old('fee_profile', $student?->fee_profile ?? 'regular') === 'regular' ? 'selected' : '' }}>Regular Day Scholar (Tuition + Other)</option>
                                <option value="boarder" {{ old('fee_profile', $student?->fee_profile ?? '') === 'boarder' ? 'selected' : '' }}>Boarder (Tuition + Boarding/Hostel)</option>
                                <option value="transport" {{ old('fee_profile', $student?->fee_profile ?? '') === 'transport' ? 'selected' : '' }}>Day Scholar + Transport (Tuition + Bus)</option>
                                <option value="boarder_transport" {{ old('fee_profile', $student?->fee_profile ?? '') === 'boarder_transport' ? 'selected' : '' }}>Boarder + Transport (All Inclusive)</option>
                            </select>
                            <div class="form-text">Controls dynamic fee categories: tuition, boarding, transport, other charges</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Previous School (If Transfer)</label>
                            <input type="text" name="previous_school" class="form-control"
                                value="{{ old('previous_school', $student->previous_school ?? '') }}"
                                placeholder="Name of previous school">
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: Parent/Guardian -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-person-heart"></i> Parent / Guardian Information</h5>
                    <span class="badge badge-primary">Step 3</span>
                </div>
                <div class="card-body">
                    <div class="two-col">
                        <div class="form-group">
                            <label class="form-label">Guardian Full Name <span style="color:red">*</span></label>
                            <input type="text" name="guardian_name" class="form-control" required
                                value="{{ old('guardian_name', $student->guardian_name ?? '') }}"
                                placeholder="Parent/Guardian full name">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Relationship</label>
                            <select name="guardian_relationship" class="form-select">
                                <option value="Father" {{ old('guardian_relationship', $student->guardian_relationship ?? '') === 'Father' ? 'selected' : '' }}>Father</option>
                                <option value="Mother" {{ old('guardian_relationship', $student->guardian_relationship ?? '') === 'Mother' ? 'selected' : '' }}>Mother</option>
                                <option value="Guardian" {{ old('guardian_relationship', $student->guardian_relationship ?? '') === 'Guardian' ? 'selected' : '' }}>Guardian</option>
                                <option value="Sibling" {{ old('guardian_relationship', $student->guardian_relationship ?? '') === 'Sibling' ? 'selected' : '' }}>Sibling</option>
                            </select>
                        </div>
                    </div>
                    <div class="two-col">
                        <div class="form-group">
                            <label class="form-label">Phone Number <span style="color:red">*</span></label>
                            <input type="tel" name="guardian_phone" class="form-control" required
                                value="{{ old('guardian_phone', $student->guardian_phone ?? '') }}"
                                placeholder="07XX XXX XXX">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address <span style="color:red">*</span></label>
                            <input type="email" name="guardian_email" class="form-control" required
                                value="{{ old('guardian_email', $student->guardian_email ?? '') }}"
                                placeholder="parent@email.com">
                        </div>
                    </div>
                    <small class="form-text">A student signs in with their admission number and this phone number. The parent/guardian signs in with this email and phone number.</small>
                    <div class="form-group">
                        <label class="form-label">National ID No.</label>
                        <input type="text" name="guardian_national_id" class="form-control"
                            value="{{ old('guardian_national_id', $student->guardian_national_id ?? '') }}"
                            placeholder="National ID Number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Occupation</label>
                        <input type="text" name="guardian_occupation" class="form-control"
                            value="{{ old('guardian_occupation', $student->guardian_occupation ?? '') }}"
                            placeholder="e.g. Farmer, Business, Teacher...">
                    </div>
                </div>
            </div>

            <!-- SECTION 4: Health & Notes -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="bi bi-heart-pulse"></i> Health & Additional Info</h5>
                    <span class="badge badge-primary">Step 4</span>
                </div>
                <div class="card-body">
                    <div class="two-col">
                        <div class="form-group">
                            <label class="form-label">Blood Group</label>
                            <select name="blood_group" class="form-select">
                                <option value="">Not Known</option>
                                @foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg)
                                <option value="{{ $bg }}" {{ old('blood_group', $student->blood_group ?? '') === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Special Needs / Disability</label>
                            <select name="special_needs" class="form-select">
                                <option value="None" {{ old('special_needs', $student->special_needs ?? '') === 'None' ? 'selected' : '' }}>None</option>
                                <option value="Visual" {{ old('special_needs', $student->special_needs ?? '') === 'Visual' ? 'selected' : '' }}>Visual Impairment</option>
                                <option value="Hearing" {{ old('special_needs', $student->special_needs ?? '') === 'Hearing' ? 'selected' : '' }}>Hearing Impairment</option>
                                <option value="Physical" {{ old('special_needs', $student->special_needs ?? '') === 'Physical' ? 'selected' : '' }}>Physical Disability</option>
                                <option value="Other" {{ old('special_needs', $student->special_needs ?? '') === 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Known Allergies / Medical Conditions</label>
                        <textarea name="medical_conditions" class="form-control" rows="2"
                            placeholder="List any allergies, chronic conditions, or medications...">{{ old('medical_conditions', $student->medical_conditions ?? '') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Additional Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                            placeholder="Any other relevant information...">{{ old('notes', $student->notes ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Photo + Submit -->
        <div>
            <!-- Photo Upload -->
            <div class="card" style="margin-bottom:16px">
                <div class="card-header"><h5><i class="bi bi-camera"></i> Student Photo</h5></div>
                <div class="card-body" style="text-align:center">
                    <div style="width:120px;height:120px;border-radius:50%;background:var(--bg);border:3px dashed var(--border);
                        display:flex;align-items:center;justify-content:center;margin:0 auto 16px;overflow:hidden">
                        <img id="photo-preview"
                            src="{{ isset($student) && $student->photo ? asset('storage/'.$student->photo) : '' }}"
                            style="width:100%;height:100%;object-fit:cover;display:{{ isset($student) && $student->photo ? 'block' : 'none' }}">
                        <i class="bi bi-person" style="font-size:48px;color:var(--text-muted);
                            display:{{ isset($student) && $student->photo ? 'none' : 'block' }}" id="photo-placeholder"></i>
                    </div>
                    <input type="file" name="photo" id="photo-input" accept="image/*"
                        data-preview="photo-preview" style="display:none"
                        onchange="previewPhoto(this)">
                    <button type="button" class="btn btn-outline btn-sm"
                        onclick="document.getElementById('photo-input').click()">
                        <i class="bi bi-upload"></i> Upload Photo
                    </button>
                    <div class="form-text" style="margin-top:8px">JPEG/PNG, max 2MB</div>
                </div>
            </div>

            <!-- Fee Assignment -->
            <div class="card" style="margin-bottom:16px">
                <div class="card-header"><h5><i class="bi bi-cash"></i> Fee Structure</h5></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Assign Fee Structure</label>
                        <select name="fee_structure_id" class="form-select">
                            <option value="">Auto (Based on Class)</option>
                            @foreach($feeStructures ?? [] as $fee)
                            <option value="{{ $fee->id }}"
                                {{ old('fee_structure_id', $student->fee_structure_id ?? '') == $fee->id ? 'selected' : '' }}>
                                {{ $fee->name }} — KES {{ number_format($fee->total_amount) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Boarding Status</label>
                        <select name="boarding_status" class="form-select">
                            <option value="day_scholar" {{ old('boarding_status', $student->boarding_status ?? '') === 'day_scholar' ? 'selected' : '' }}>Day Scholar</option>
                            <option value="boarder" {{ old('boarding_status', $student->boarding_status ?? '') === 'boarder' ? 'selected' : '' }}>Boarder</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Submit Card -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-accent w-100 btn-lg" style="margin-bottom:10px">
                        <i class="bi bi-check-circle"></i>
                        {{ isset($student) ? 'Update Student' : 'Enroll Student' }}
                    </button>
                    <a href="{{ route('students.index') }}" class="btn btn-outline w-100">
                        <i class="bi bi-x"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function filterStreamsByClass() {
    const classSelect = document.getElementById('school_class_id');
    const streamSelect = document.getElementById('stream_id');
    if (!classSelect || !streamSelect) return;

    const selectedClass = classSelect.value;
    const streamOptions = streamSelect.querySelectorAll('option[data-class]');
    
    streamOptions.forEach(option => {
        if (!selectedClass || option.getAttribute('data-class') === selectedClass) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
            if (option.selected) {
                streamSelect.value = '';
            }
        }
    });
}
document.addEventListener('DOMContentLoaded', filterStreamsByClass);

function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photo-preview').src = e.target.result;
            document.getElementById('photo-preview').style.display = 'block';
            document.getElementById('photo-placeholder').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

@endsection
