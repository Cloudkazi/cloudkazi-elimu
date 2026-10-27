@extends('layouts.app')
@section('title', 'Student Promotions & Transitions')
@section('breadcrumb', 'Students › Promotions')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>🚀 Annual Student Promotions & Transitions</h1>
        <p>Advance classes en masse to the next academic level or graduate senior cohorts</p>
    </div>
</div>

<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h5><i class="bi bi-arrow-up-circle"></i> Bulk Class Promotion Wizard</h5>
    </div>
    <form action="{{ route('students.promotions.process') }}" method="POST" id="promo-form">
        @csrf
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
                <div class="form-group">
                    <label class="form-label">Current Class (Promote From) *</label>
                    <select name="from_class_id" id="from-class" class="form-control" onchange="loadStudents(this.value)" required>
                        <option value="">-- Choose Class to Promote --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} (Level {{ $c->numeric_level }} - {{ $c->curriculum }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Target Class (Promote To) *</label>
                    <select name="to_class_id" class="form-control" required>
                        <option value="">-- Choose Destination Class --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} (Level {{ $c->numeric_level }} - {{ $c->curriculum }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="students-container" style="display:none">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <h6>Select Students to Promote:</h6>
                    <button type="button" class="btn btn-outline btn-sm" onclick="toggleAllPromo(true)">Select All</button>
                </div>
                <div id="students-list" style="max-height:300px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;padding:12px">
                    <!-- populated via AJAX or selection -->
                </div>
            </div>
        </div>
        <div class="card-footer" style="display:flex;justify-content:flex-end">
            <button type="submit" class="btn btn-primary" id="btn-submit-promo" disabled>
                <i class="bi bi-check-circle"></i> Execute Promotions
            </button>
        </div>
    </form>
</div>

<script>
function loadStudents(classId) {
    if (!classId) {
        document.getElementById('students-container').style.display = 'none';
        document.getElementById('btn-submit-promo').disabled = true;
        return;
    }

    fetch('{{ route("students.search") }}?class_id=' + classId)
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('students-list');
            list.innerHTML = '';
            if (data.length === 0) {
                list.innerHTML = '<p style="color:var(--text-muted)">No students found in this class.</p>';
            } else {
                data.forEach(s => {
                    const row = document.createElement('div');
                    row.style = 'display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f1f5f9';
                    row.innerHTML = `
                        <input type="checkbox" name="student_ids[]" value="${s.id}" checked class="promo-chk">
                        <strong>${s.first_name} ${s.last_name}</strong>
                        <span class="badge badge-gray">${s.admission_number}</span>
                    `;
                    list.appendChild(row);
                });
                document.getElementById('btn-submit-promo').disabled = false;
            }
            document.getElementById('students-container').style.display = 'block';
        });
}

function toggleAllPromo(check) {
    document.querySelectorAll('.promo-chk').forEach(c => c.checked = check);
}
</script>
@endsection
