@extends('layouts.app')
@section('title', 'Students')
@section('breadcrumb', 'Students › All Students')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>👩‍🎓 Student Management</h1>
        <p>Manage enrolled students, admissions and records</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        @unless(auth()->user()?->isHostelWarden())
        <a href="{{ route('students.create') }}" class="btn btn-accent">
            <i class="bi bi-person-plus"></i> Enroll New Student
        </a>
        <a href="{{ route('students.import') }}" class="btn btn-primary" id="btn-import-students">
            <i class="bi bi-file-earmark-arrow-up"></i> Import Students
        </a>
        <a href="{{ route('students.promotions') }}" class="btn btn-outline">
            <i class="bi bi-arrow-up-circle"></i> Promotions
        </a>
        @endunless
        <button class="btn btn-outline" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid stats-grid-4" style="margin-bottom:20px">
    <div class="stat-card stat-blue">
        <div class="stat-icon">👩‍🎓</div>
        <div class="stat-body">
            <div class="stat-label">Total Students</div>
            <div class="stat-value">{{ $totalStudents ?? 0 }}</div>
            <div class="stat-trend up"><i class="bi bi-arrow-up"></i> Active</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">♀</div>
        <div class="stat-body">
            <div class="stat-label">Female Students</div>
            <div class="stat-value">{{ $femaleCount ?? 0 }}</div>
            <div class="stat-trend neutral">{{ ($totalStudents ?? 0) > 0 ? round(($femaleCount ?? 0) / $totalStudents * 100) : 0 }}%</div>
        </div>
    </div>
    <div class="stat-card stat-indigo">
        <div class="stat-icon">♂</div>
        <div class="stat-body">
            <div class="stat-label">Male Students</div>
            <div class="stat-value">{{ $maleCount ?? 0 }}</div>
            <div class="stat-trend neutral">{{ ($totalStudents ?? 0) > 0 ? round(($maleCount ?? 0) / $totalStudents * 100) : 0 }}%</div>
        </div>
    </div>
    @unless(auth()->user()?->isHostelWarden())
    <div class="stat-card stat-orange">
        <div class="stat-icon">⚠️</div>
        <div class="stat-body">
            <div class="stat-label">Fee Defaulters</div>
            <div class="stat-value">{{ $defaulters ?? 0 }}</div>
            <div class="stat-trend down"><i class="bi bi-exclamation-triangle"></i> Need Follow-up</div>
        </div>
    </div>
    @endunless
</div>

<!-- Filter + Search -->
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-funnel"></i> Filter Students</h5>
        <span style="color:var(--text-muted);font-size:0.8rem">{{ $students->total() ?? 0 }} results</span>
    </div>
    <div class="card-body" style="padding-bottom:12px">
        <form method="GET" action="{{ route('students.index') }}">
            <div style="display:grid;grid-template-columns:repeat(5,1fr) auto;gap:12px;align-items:end">
                <div>
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" id="table-search"
                        placeholder="Name / Adm No / Phone..." value="{{ request('search') }}">
                </div>
                <div>
                    <label class="form-label">Class</label>
                    <select name="class_id" class="form-select">
                        <option value="">All Classes</option>
                        @foreach($classes ?? [] as $class)
                        <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                            {{ $class->class_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">All</option>
                        <option value="Male" {{ request('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ request('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        <option value="transferred" {{ request('status') === 'transferred' ? 'selected' : '' }}>Transferred</option>
                        <option value="alumni" {{ request('status') === 'alumni' ? 'selected' : '' }}>Alumni</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Fee Status</label>
                    <select name="fee_status" class="form-select">
                        <option value="">Any</option>
                        <option value="defaulter" {{ request('fee_status') === 'defaulter' ? 'selected' : '' }}>Defaulter</option>
                        <option value="paid" {{ request('fee_status') === 'paid' ? 'selected' : '' }}>Fully Paid</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filter</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Adm No.</th>
                    <th>Class</th>
                    <th>Gender</th>
                    <th>Parent/Guardian</th>
                    @unless(auth()->user()?->isHostelWarden())
                    <th>Fee Balance</th>
                    @endunless
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students ?? [] as $index => $student)
                <tr>
                    <td style="color:var(--text-muted)">{{ $students->firstItem() + $index }}</td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div class="avatar-text avatar-sm" style="background:linear-gradient(135deg,
                                {{ $student->gender === 'Female' ? '#db2777,#9d174d' : '#2563eb,#1d4ed8' }})">
                                {{ strtoupper(substr($student->first_name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight:600">{{ $student->full_name }}</div>
                                <div style="font-size:0.72rem;color:var(--text-muted)">
                                    DOB: {{ $student->date_of_birth?->format('d M Y') ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-gray">{{ $student->admission_number }}</span></td>
                    <td>
                        <span class="badge badge-primary">
                            {{ $student->currentClass?->class_name ?? 'N/A' }}
                        </span>
                        @if($student->currentClass?->stream_name)
                        <span class="badge badge-info" style="margin-left:4px">
                            {{ $student->currentClass->stream_name }}
                        </span>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $student->gender === 'Female' ? 'badge-pink' : 'badge-primary' }}"
                              style="{{ $student->gender === 'Female' ? 'background:#fce7f3;color:#9d174d' : '' }}">
                            {{ $student->gender }}
                        </span>
                    </td>
                    <td>
                        <div style="font-size:0.85rem">{{ $student->guardian_name ?? '—' }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">{{ $student->guardian_phone ?? '' }}</div>
                    </td>
                    @unless(auth()->user()?->isHostelWarden())
                    <td>
                        @php $balance = $student->fee_balance ?? 0; @endphp
                        <span class="badge {{ $balance > 0 ? 'badge-danger' : 'badge-success' }}">
                            {{ $balance > 0 ? 'KES ' . number_format($balance) . ' owed' : 'Cleared' }}
                        </span>
                    </td>
                    @endunless
                    <td>
                        @php
                            $statusColors = [
                                'active' => 'badge-success',
                                'suspended' => 'badge-danger',
                                'transferred' => 'badge-warning',
                                'alumni' => 'badge-teal',
                            ];
                        @endphp
                        <span class="badge {{ $statusColors[$student->status] ?? 'badge-gray' }}">
                            {{ ucfirst($student->status) }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap">
                            <a href="{{ route('students.show', $student) }}" class="btn btn-outline btn-sm" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            @unless(auth()->user()?->isHostelWarden())
                            <a href="{{ route('students.edit', $student) }}" class="btn btn-outline btn-sm" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('students.destroy', $student) }}" method="POST" style="display:inline" onsubmit="return confirm('Are you sure you want to permanently delete this student? This action will remove the student and their associated records.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline btn-sm btn-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <a href="{{ route('finance.student-statement', $student) }}" class="btn btn-outline btn-sm" title="Fee Statement">
                                <i class="bi bi-receipt"></i>
                            </a>
                            @endunless
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9">
                        <div class="no-data">
                            <i class="bi bi-person-x"></i>
                            <div>No students found matching your criteria.</div>
                            <a href="{{ route('students.create') }}" class="btn btn-accent" style="margin-top:12px">
                                <i class="bi bi-person-plus"></i> Enroll First Student
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(isset($students) && $students->hasPages())
    <div class="card-footer">
        {{ $students->links() }}
    </div>
    @endif
</div>

@endsection
