@extends('layouts.app')
@section('title', 'Fee Arrears & Defaulters')
@section('breadcrumb', 'Finance › Arrears')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>⚠️ Fee Arrears & Recovery Management</h1>
        <p>Track overdue fee balances, identify defaulters, and send automated SMS reminders to guardians</p>
    </div>
    <div>
        <a href="{{ route('operations.notices') }}" class="btn btn-outline">
            <i class="bi bi-chat-dots"></i> Send Bulk SMS Reminder
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h5><i class="bi bi-exclamation-triangle"></i> Active Fee Defaulters ({{ $invoicesWithArrears->total() }})</h5>
        <span class="badge badge-danger">
            Total Outstanding: KES {{ number_format($totalArrears, 2) }}
        </span>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class / Stream</th>
                    <th>Guardian / Parent</th>
                    <th>Total Invoiced</th>
                    <th>Paid</th>
                    <th>Arrears Balance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoicesWithArrears as $inv)
                @php
                    $primaryGuardian = $inv->student?->guardians->first();
                @endphp
                <tr>
                    <td>
                        <div style="font-weight:700">{{ $inv->student?->full_name }}</div>
                        <div style="font-size:0.75rem;color:var(--text-muted)">{{ $inv->student?->admission_number }}</div>
                    </td>
                    <td>{{ $inv->student?->schoolClass?->name }} ({{ $inv->student?->stream?->name ?? '—' }})</td>
                    <td>
                        <div style="font-weight:600">{{ $primaryGuardian?->first_name }} {{ $primaryGuardian?->last_name }}</div>
                        <div style="font-size:0.8rem;color:var(--primary)"><i class="bi bi-telephone"></i> {{ $primaryGuardian?->phone ?? 'No phone' }}</div>
                    </td>
                    <td>KES {{ number_format($inv->total_amount, 2) }}</td>
                    <td style="color:var(--success)">KES {{ number_format($inv->paid_amount, 2) }}</td>
                    <td>
                        <strong style="color:var(--danger);font-family:'Outfit';font-size:1.05rem">
                            KES {{ number_format($inv->balance, 2) }}
                        </strong>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('finance.payments') }}" class="btn btn-primary btn-sm" title="Receive Payment">
                                <i class="bi bi-cash"></i> Pay
                            </a>
                            <a href="{{ route('students.show', $inv->student_id) }}" class="btn btn-outline btn-sm" title="Profile">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="no-data">Great news! No student accounts are currently in arrears.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoicesWithArrears->hasPages())
        <div class="card-footer">{{ $invoicesWithArrears->links() }}</div>
    @endif
</div>
@endsection
