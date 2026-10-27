@extends('layouts.app')
@section('title', 'Student Official Documents')
@section('breadcrumb', 'Students › Documents')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📜 Official Student Documents & Certificates</h1>
        <p>Generate certified School Leaving Certificates, Bonafide Letters, and NEMIS Transfer Clearance</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-file-earmark-text"></i> Student Document Generator Roster</h5>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Adm No.</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>NEMIS UPI</th>
                    <th>Document Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $s)
                <tr>
                    <td><span class="badge badge-gray">{{ $s->admission_number }}</span></td>
                    <td><strong>{{ $s->full_name }}</strong></td>
                    <td>{{ $s->schoolClass?->name }}</td>
                    <td><span style="font-family:monospace">{{ $s->nemis_upi ?: 'PENDING' }}</span></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <button class="btn btn-outline btn-sm" onclick="alert('Generating Bonafide Student Letter for {{ $s->full_name }}')">
                                <i class="bi bi-file-earmark-person"></i> Bonafide Letter
                            </button>
                            <button class="btn btn-outline btn-sm" onclick="alert('Generating Leaving Certificate for {{ $s->full_name }}')">
                                <i class="bi bi-award"></i> Leaving Cert
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="no-data">No students enrolled yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($students->hasPages())
        <div class="card-footer">{{ $students->links() }}</div>
    @endif
</div>
@endsection
