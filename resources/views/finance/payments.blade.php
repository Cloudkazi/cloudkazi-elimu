@extends('layouts.app')
@section('title', 'Fee Payments & Gateway')
@section('breadcrumb', 'Finance › Payments')

@section('content')

<div class="page-header">
    <div class="page-header-text">
        <h1>💳 Fee Payments &amp; Gateway</h1>
        <p>Record, manage, and reconcile student fee payments across M-Pesa, Bank, and Cash</p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('reminder-modal').style.display='flex'">
            <i class="bi bi-bell"></i> Send Fee Reminders
        </button>
        <a href="{{ route('finance.reconciliation') }}" class="btn btn-outline">
            <i class="bi bi-arrow-left-right"></i> Reconciliation
        </a>
        <a href="{{ route('finance.gateway-settings') }}" class="btn btn-outline">
            <i class="bi bi-gear"></i> Gateway Settings
        </a>
        <a href="{{ route('finance.reports') }}" class="btn btn-primary">
            <i class="bi bi-graph-up"></i> Reports
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:20px;display:flex;align-items:center;gap:10px">
    <i class="bi bi-check-circle-fill" style="font-size:1.2rem"></i>
    <div>{{ session('success') }}</div>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:20px">
    <ul style="margin:0;padding-left:20px">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Stats -->
<div class="stats-grid stats-grid-4" style="margin-bottom:20px">
    <div class="stat-card stat-green">
        <div class="stat-icon">✅</div>
        <div class="stat-body">
            <div class="stat-label">Collected Today</div>
            <div class="stat-value">{{ number_format($todayTotal ?? 0) }}</div>
            <div class="stat-trend up"><i class="bi bi-currency-exchange"></i> KES</div>
        </div>
    </div>
    <div class="stat-card stat-blue">
        <div class="stat-icon">📅</div>
        <div class="stat-body">
            <div class="stat-label">This Term</div>
            <div class="stat-value">{{ number_format($termTotal ?? 0) }}</div>
            <div class="stat-trend up"><i class="bi bi-arrow-up"></i> Term collections</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">⏳</div>
        <div class="stat-body">
            <div class="stat-label">Outstanding</div>
            <div class="stat-value">{{ number_format($outstanding ?? 0) }}</div>
            <div class="stat-trend down"><i class="bi bi-exclamation-triangle"></i> Awaiting</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">📱</div>
        <div class="stat-body">
            <div class="stat-label">M-Pesa Today</div>
            <div class="stat-value">{{ number_format($mpesaToday ?? 0) }}</div>
            <div class="stat-trend up"><i class="bi bi-phone"></i> Daraja live</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1.3fr 1.7fr;gap:20px;align-items:start">

    <!-- Record Payment Form -->
    <div class="card">
        <div class="card-header" style="background:var(--primary);color:#fff;border-radius:10px 10px 0 0">
            <h5 style="color:#fff;margin:0"><i class="bi bi-plus-circle"></i> Record Fee Payment</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('finance.store-payment') }}" method="POST" id="payment-form">
                @csrf
                <div class="form-group" style="position:relative">
                    <label class="form-label">Search Student <span style="color:red">*</span></label>
                    <input type="text" class="form-control" id="student-search"
                        placeholder="Type student name or admission number..."
                        autocomplete="off" required>
                    <input type="hidden" name="student_id" id="student_id_hidden">
                    <div id="student-suggestions"
                        style="border:1px solid var(--border);border-top:none;border-radius:0 0 8px 8px;background:white;display:none;position:absolute;width:100%;z-index:100;max-height:220px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,0.1)"></div>
                </div>

                <!-- Student Info Preview -->
                <div id="student-preview" style="display:none;background:var(--surface-subtle);border-radius:8px;padding:12px 14px;margin-bottom:16px;border:1px solid var(--border)">
                    <div style="display:flex;align-items:center;gap:12px">
                        <div class="avatar-text avatar-md" style="background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;font-weight:700" id="sp-avatar">AA</div>
                        <div>
                            <div style="font-weight:700" id="sp-name">—</div>
                            <div style="font-size:0.8rem;color:var(--text-muted)" id="sp-class">—</div>
                        </div>
                        <div style="margin-left:auto;text-align:right">
                            <div style="font-size:0.72rem;color:var(--text-muted)">Current Balance</div>
                            <div style="font-family:'Outfit',sans-serif;font-weight:900;color:var(--danger);font-size:1.15rem" id="sp-balance">—</div>
                        </div>
                    </div>
                </div>

                <div class="two-col">
                    <div class="form-group">
                        <label class="form-label">Amount (KES) <span style="color:red">*</span></label>
                        <input type="number" name="amount" class="form-control" required min="1"
                            id="fee_amount" placeholder="e.g. 15000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Channel <span style="color:red">*</span></label>
                        <select name="payment_method" class="form-select" required id="payment-method-select" onchange="togglePaymentFields(this.value)">
                            <optgroup label="Mobile Money">
                                <option value="mpesa_stk">📱 M-Pesa STK Push (Online)</option>
                                <option value="mpesa_paybill">📱 M-Pesa Paybill</option>
                                <option value="mpesa_till">📱 M-Pesa Till / Buy Goods</option>
                            </optgroup>
                            <optgroup label="Bank Accounts">
                                <option value="bank_transfer">🏦 Bank Transfer (EFT/PesaLink)</option>
                                <option value="bank_deposit">🏛️ Bank Branch Deposit Slip</option>
                            </optgroup>
                            <optgroup label="Cash &amp; Cheque">
                                <option value="cash" selected>💵 Cash Counter</option>
                                <option value="cheque">📄 Bank Cheque</option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                {{-- M-Pesa Dynamic Section --}}
                <div id="mpesa-fields" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;margin-bottom:14px">
                    <div class="form-group" style="margin-bottom:8px">
                        <label class="form-label">M-Pesa Phone Number</label>
                        <input type="text" name="mpesa_phone" id="mpesa_phone" class="form-control" placeholder="254712345678">
                        <small style="color:#15803d">Safaricom phone to receive STK push or payer's registered number</small>
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-top:6px" id="trigger-stk-label">
                        <input type="checkbox" name="trigger_stk" value="1" id="trigger_stk_check" checked style="width:16px;height:16px">
                        <span style="font-size:0.85rem;font-weight:600;color:#166534">Send live STK Push PIN prompt to parent phone right now</span>
                    </label>
                </div>

                {{-- Bank Account Dynamic Section --}}
                <div id="bank-fields" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;margin-bottom:14px">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Credited School Bank Account <span style="color:red">*</span></label>
                        <select name="bank_account_id" class="form-select" id="bank_account_select">
                            <option value="">-- Select Destination Bank Account --</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ $acc->is_primary ? 'selected' : '' }}>
                                    {{ $acc->bank_name }} — {{ $acc->account_number }} ({{ $acc->account_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Reference / Slip / Receipt Code <span style="color:red">*</span></label>
                    <input type="text" name="reference_code" class="form-control" id="reference_code" required
                        placeholder="e.g. M-Pesa Code (QKL9...), Bank Deposit Slip No, Cash Receipt #">
                </div>

                <div class="two-col">
                    <div class="form-group">
                        <label class="form-label">Payment Date <span style="color:red">*</span></label>
                        <input type="date" name="payment_date" class="form-control" required
                            value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes / Remarks</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional payment remarks...">
                    </div>
                </div>

                <button type="submit" class="btn btn-success w-100 btn-lg" style="margin-top:10px">
                    <i class="bi bi-check-circle"></i> Complete Payment &amp; Print Receipt
                </button>
            </form>
        </div>
    </div>

    <!-- Payments Ledger & Filter -->
    <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
            <h5 style="margin:0"><i class="bi bi-clock-history"></i> Payment Records Ledger</h5>
            <form method="GET" action="{{ route('finance.payments') }}" style="display:flex;gap:6px;flex-wrap:wrap">
                <input type="text" name="search" class="form-control" style="width:140px;padding:4px 8px;font-size:0.8rem"
                    placeholder="Search..." value="{{ request('search') }}">
                <select name="method" class="form-select" style="width:110px;padding:4px 8px;font-size:0.8rem">
                    <option value="">All Methods</option>
                    <option value="mpesa_stk" {{ request('method') === 'mpesa_stk' ? 'selected' : '' }}>M-Pesa STK</option>
                    <option value="mpesa_paybill" {{ request('method') === 'mpesa_paybill' ? 'selected' : '' }}>Paybill</option>
                    <option value="bank_transfer" {{ request('method') === 'bank_transfer' ? 'selected' : '' }}>Bank</option>
                    <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>Cash</option>
                </select>
                <input type="date" name="date" class="form-control" style="width:120px;padding:4px 8px;font-size:0.8rem"
                    value="{{ request('date') }}">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                @if(request()->hasAny(['search', 'method', 'date']))
                    <a href="{{ route('finance.payments') }}" class="btn btn-outline btn-sm">Reset</a>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Receipt No</th>
                        <th>Student</th>
                        <th>Channel</th>
                        <th>Ref Code</th>
                        <th class="text-right">Amount (KES)</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                    <tr>
                        <td>
                            <a href="{{ route('finance.receipt', $p->id) }}" target="_blank" style="font-family:monospace;font-weight:700;color:var(--primary)">
                                {{ $p->receipt_number }}
                            </a>
                        </td>
                        <td>
                            <strong>{{ $p->student?->full_name }}</strong>
                            <div style="font-size:0.75rem;color:var(--text-muted)">
                                {{ $p->student?->admission_number }} &bull; {{ $p->student?->schoolClass?->name }}
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $p->method_badge_class }}">
                                <i class="bi {{ $p->method_icon }}"></i> {{ $p->method_label }}
                            </span>
                            @if($p->bankAccount)
                                <div style="font-size:0.72rem;color:var(--text-muted)">{{ $p->bankAccount->bank_name }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-gray" style="font-family:monospace">
                                {{ $p->reference_code }}
                            </span>
                        </td>
                        <td class="text-right">
                            <strong>{{ number_format($p->amount, 2) }}</strong>
                        </td>
                        <td style="font-size:0.78rem;color:var(--text-muted)">
                            {{ $p->payment_date?->format('d M Y') }}
                        </td>
                        <td>
                            <div style="display:flex;gap:4px">
                                <a href="{{ route('finance.receipt', $p->id) }}" target="_blank" class="btn btn-outline btn-sm" title="Print Receipt">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <form action="{{ route('finance.receipt.resend', $p->id) }}" method="POST" style="display:inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline btn-sm" title="Resend SMS Receipt">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="no-data">No payment records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
        <div style="padding:14px">
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Fee Reminder Modal --}}
<div id="reminder-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;width:95%;max-width:540px;box-shadow:0 10px 25px rgba(0,0,0,0.2);overflow:hidden">
        <div style="background:var(--primary);color:#fff;padding:16px 20px;display:flex;justify-content:space-between;align-items:center">
            <h5 style="margin:0;color:#fff"><i class="bi bi-bell"></i> Send Fee Reminder Alerts</h5>
            <button type="button" onclick="document.getElementById('reminder-modal').style.display='none'" style="background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer">&times;</button>
        </div>
        <form action="{{ route('finance.reminder.send') }}" method="POST">
            @csrf
            <div style="padding:20px">
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Target Audience <span style="color:red">*</span></label>
                    <select name="target_type" class="form-select" required onchange="toggleReminderTarget(this.value)">
                        <option value="arrears">All Students with Active Fee Arrears</option>
                        <option value="class">Specific Class</option>
                        <option value="student">Single Student</option>
                    </select>
                </div>
                <div class="form-group" id="class-target-group" style="display:none;margin-bottom:14px">
                    <label class="form-label">Select Class</label>
                    <select name="school_class_id" class="form-select">
                        <option value="">-- Choose Class --</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="student-target-group" style="display:none;margin-bottom:14px">
                    <label class="form-label">Select Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">-- Choose Student --</option>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}">{{ $st->full_name }} ({{ $st->admission_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Reminder Message (SMS / Portal) <span style="color:red">*</span></label>
                    <textarea name="message" class="form-control" rows="4" required>Dear Parent, this is a reminder from {{ auth()->user()?->school?->name ?? 'the school' }} regarding the pending school fee balance. Kindly clear the outstanding fees promptly via M-Pesa Paybill {{ $settings->daraja_paybill_number ?? '174379' }} or via our official bank accounts. Thank you.</textarea>
                    <small style="color:var(--text-muted)">Personalized balance and student name will be referenced in the SMS dispatch.</small>
                </div>
            </div>
            <div style="background:var(--surface-subtle);padding:14px 20px;display:flex;justify-content:flex-end;gap:10px">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('reminder-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send-check"></i> Dispatch Reminders</button>
            </div>
        </form>
    </div>
</div>

<script>
const studentSearch = document.getElementById('student-search');
const suggestionsBox = document.getElementById('student-suggestions');
const studentIdHidden = document.getElementById('student_id_hidden');
const studentPreview = document.getElementById('student-preview');
let searchTimeout;
let searchController;
let studentsData = [];

studentSearch.addEventListener('input', function() {
    const value = this.value.trim();
    studentIdHidden.value = '';
    studentPreview.style.display = 'none';
    clearTimeout(searchTimeout);
    if (searchController) searchController.abort();

    if (value.length < 2) {
        suggestionsBox.style.display = 'none';
        return;
    }

    suggestionsBox.replaceChildren();
    const loading = document.createElement('div');
    loading.textContent = 'Searching students...';
    loading.style.cssText = 'padding:10px;color:#64748b;font-size:0.85rem';
    suggestionsBox.appendChild(loading);
    suggestionsBox.style.display = 'block';

    searchTimeout = setTimeout(async () => {
        if (searchController) searchController.abort();
        searchController = new AbortController();

        try {
            const response = await fetch(`{{ route('finance.students.search') }}?q=${encodeURIComponent(value)}`, {
                headers: { 'Accept': 'application/json' },
                signal: searchController.signal
            });
            if (!response.ok) throw new Error('Unable to search students.');

            studentsData = await response.json();
            renderStudentSuggestions(studentsData);
        } catch (error) {
            if (error.name === 'AbortError') return;
            suggestionsBox.replaceChildren();
            const message = document.createElement('div');
            message.textContent = 'Student search failed. Please try again.';
            message.style.cssText = 'padding:10px;color:#dc2626;font-size:0.85rem';
            suggestionsBox.appendChild(message);
        }
    }, 200);
});

function renderStudentSuggestions(students) {
    suggestionsBox.replaceChildren();
    if (!students.length) {
        const empty = document.createElement('div');
        empty.textContent = 'No matching students found';
        empty.style.cssText = 'padding:10px;color:#94a3b8;font-size:0.85rem';
        suggestionsBox.appendChild(empty);
        return;
    }

    students.forEach(student => {
        const option = document.createElement('button');
        option.type = 'button';
        option.style.cssText = 'display:block;width:100%;text-align:left;padding:10px 14px;cursor:pointer;border:0;border-bottom:1px solid #f1f5f9;background:#fff;font-size:0.88rem';
        option.addEventListener('mouseenter', () => option.style.background = '#f8fafc');
        option.addEventListener('mouseleave', () => option.style.background = '#fff');
        option.addEventListener('click', () => selectStudent(student.id));

        const name = document.createElement('strong');
        name.textContent = student.name;
        const detail = document.createElement('span');
        detail.textContent = ` (${student.admission}) - ${student.class}`;
        detail.style.cssText = 'color:#64748b;font-size:0.8rem';
        const balance = document.createElement('div');
        balance.textContent = `Balance: KES ${Number(student.balance).toLocaleString()}`;
        balance.style.cssText = `font-size:0.75rem;color:${student.balance > 0 ? '#dc2626' : '#16a34a'}`;

        option.append(name, detail, balance);
        suggestionsBox.appendChild(option);
    });
}

function selectStudent(id) {
    const s = studentsData.find(st => String(st.id) === String(id));
    if (!s) return;
    studentIdHidden.value = s.id;
    studentSearch.value = `${s.name} (${s.admission})`;
    suggestionsBox.style.display = 'none';

    // Update preview card
    studentPreview.style.display = 'block';
    document.getElementById('sp-avatar').innerText = s.name.substring(0, 2).toUpperCase();
    document.getElementById('sp-name').innerText = s.name;
    document.getElementById('sp-class').innerText = `${s.admission} • ${s.class}`;
    document.getElementById('sp-balance').innerText = `KES ${Number(s.balance).toLocaleString()}`;

    // Pre-fill phone if empty
    if (s.phone) {
        document.getElementById('mpesa_phone').value = s.phone;
    }
    // Pre-fill suggested amount if balance > 0 and amount empty
    const feeAmount = document.getElementById('fee_amount');
    if (!feeAmount.value && s.balance > 0) {
        feeAmount.value = s.balance;
    }
}

function togglePaymentFields(method) {
    const mpesaFields = document.getElementById('mpesa-fields');
    const bankFields = document.getElementById('bank-fields');
    const triggerStkLabel = document.getElementById('trigger-stk-label');
    const triggerStkCheck = document.getElementById('trigger_stk_check');
    const refInput = document.getElementById('reference_code');

    if (method === 'mpesa_stk') {
        mpesaFields.style.display = 'block';
        bankFields.style.display = 'none';
        triggerStkLabel.style.display = 'flex';
        triggerStkCheck.checked = true;
        refInput.placeholder = 'Optional if triggering live STK push (auto-generated)';
        refInput.required = false;
    } else if (method === 'mpesa_paybill' || method === 'mpesa_till') {
        mpesaFields.style.display = 'block';
        bankFields.style.display = 'none';
        triggerStkLabel.style.display = 'none';
        triggerStkCheck.checked = false;
        refInput.placeholder = 'M-Pesa Receipt Code (e.g. QK891234)';
        refInput.required = true;
    } else if (method === 'bank_transfer' || method === 'bank_deposit') {
        mpesaFields.style.display = 'none';
        bankFields.style.display = 'block';
        refInput.placeholder = 'Bank Deposit Slip No / EFT Reference';
        refInput.required = true;
    } else {
        mpesaFields.style.display = 'none';
        bankFields.style.display = 'none';
        refInput.placeholder = 'Cash Receipt No / Cheque No';
        refInput.required = true;
    }
}

function toggleReminderTarget(target) {
    document.getElementById('class-target-group').style.display = target === 'class' ? 'block' : 'none';
    document.getElementById('student-target-group').style.display = target === 'student' ? 'block' : 'none';
}

document.addEventListener('click', function(e) {
    if (!studentSearch.contains(e.target) && !suggestionsBox.contains(e.target)) {
        suggestionsBox.style.display = 'none';
    }
});
</script>
@endsection
