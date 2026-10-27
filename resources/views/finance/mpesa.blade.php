@extends('layouts.app')
@section('title', 'M-Pesa Daraja Gateway')
@section('breadcrumb', 'Finance › M-Pesa')

@section('content')
<div class="page-header">
    <div class="page-header-text">
        <h1>📱 Safaricom Daraja M-Pesa Gateway</h1>
        <p>Trigger live STK push prompts directly to parent phones and inspect automated reconciliations</p>
    </div>
</div>

<div class="stats-grid stats-grid-3" style="margin-bottom:20px">
    <div class="stat-card stat-green">
        <div class="stat-icon">📱</div>
        <div class="stat-body">
            <div class="stat-label">Total M-Pesa Collections</div>
            <div class="stat-value">KES {{ number_format($totalMpesa) }}</div>
            <div class="stat-trend up"><i class="bi bi-shield-check"></i> Daraja live ledger</div>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon">⚡</div>
        <div class="stat-body">
            <div class="stat-label">Collected Today</div>
            <div class="stat-value">KES {{ number_format($todayMpesa) }}</div>
            <div class="stat-trend up"><i class="bi bi-clock"></i> Today's settlements</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">🔌</div>
        <div class="stat-body">
            <div class="stat-label">API Gateway Status</div>
            <div class="stat-value" style="font-size:1.2rem;color:var(--success)">ONLINE (v2.0)</div>
            <div class="stat-trend up">Shortcode: 174379</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.2fr 1.8fr;gap:20px">
    <!-- STK Push Trigger Card -->
    <div class="card">
        <div class="card-header" style="background:#00A651;color:#fff;border-radius:10px 10px 0 0">
            <h5 style="color:#fff"><i class="bi bi-phone"></i> Initiate Daraja STK Push</h5>
        </div>
        <form action="{{ route('finance.mpesa.stk') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label">Select Student *</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">-- Choose Student --</option>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->admission_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Parent / Payer Phone Number (Safaricom) *</label>
                    <input type="text" name="phone" class="form-control" placeholder="254712345678" required>
                    <small style="color:var(--text-muted)">Must start with 2547... or 2541...</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount (KES) *</label>
                    <input type="number" name="amount" class="form-control" placeholder="e.g. 5000" min="1" required>
                </div>
            </div>
            <div class="card-footer" style="display:flex;justify-content:flex-end">
                <button type="submit" class="btn btn-primary" style="background:#00A651;border-color:#00A651">
                    <i class="bi bi-send-check"></i> Send M-Pesa PIN Prompt
                </button>
            </div>
        </form>
    </div>

    <!-- M-Pesa Transactions Log -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-receipt-cutoff"></i> M-Pesa Daraja Transactions</h5>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Receipt No.</th>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Ref Code</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $t)
                    <tr>
                        <td><strong style="font-family:monospace;color:#00A651">{{ $t->receipt_number }}</strong></td>
                        <td>{{ $t->student?->full_name }}</td>
                        <td><strong>KES {{ number_format($t->amount, 2) }}</strong></td>
                        <td><span class="badge badge-gray" style="font-family:monospace">{{ $t->reference_code }}</span></td>
                        <td style="font-size:0.8rem;color:var(--text-muted)">{{ \Carbon\Carbon::parse($t->payment_date)->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="no-data">No M-Pesa transactions logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer">{{ $transactions->links() }}</div>
        @endif
    </div>
</div>
@endsection
