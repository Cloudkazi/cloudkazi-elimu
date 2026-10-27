<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #{{ $payment->receipt_number }} — CloudKazi Elimu</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 40px auto; max-width: 680px; color: #1e293b; }
        .receipt-box { border: 2px solid #0f172a; padding: 24px; border-radius: 8px; position: relative; }
        .watermark { position: absolute; top: 40%; left: 25%; font-size: 4rem; color: rgba(0, 166, 81, 0.08); font-weight: 900; transform: rotate(-30deg); pointer-events: none; }
        .header { text-align: center; border-bottom: 2px dashed #94a3b8; padding-bottom: 14px; margin-bottom: 20px; }
        .header img { max-width: 72px; max-height: 72px; object-fit: contain; margin-bottom: 8px; }
        .header h2 { margin: 0; font-size: 1.4rem; color: #0f172a; text-transform: uppercase; }
        .header p { margin: 4px 0 0 0; font-size: 0.85rem; color: #64748b; }
        .title { font-size: 1.1rem; font-weight: 800; letter-spacing: 2px; text-align: center; margin-bottom: 20px; color: #1e3a8a; }
        .row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem; }
        .row span.lbl { color: #64748b; font-weight: 500; }
        .row span.val { font-weight: 700; color: #0f172a; }
        .amount-box { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 6px; padding: 12px 16px; margin: 20px 0; display: flex; justify-content: space-between; align-items: center; }
        .amount-val { font-size: 1.3rem; font-weight: 900; color: #00A651; }
        .footer { margin-top: 30px; border-top: 1px solid #cbd5e1; padding-top: 16px; display: flex; justify-content: space-between; font-size: 0.8rem; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <a href="{{ route('finance.payments') }}" style="color: #2563eb; text-decoration: none; font-weight: 600;">← Back to Payments</a>
    <div style="display:flex;gap:8px">
        <a href="{{ route('finance.receipt', ['id' => $payment->id, 'download' => 1]) }}" style="padding: 8px 18px; background: #dc2626; color: #fff; border-radius: 6px; text-decoration:none; font-weight:600;">Download PDF</a>
        <button onclick="window.print()" style="padding: 8px 18px; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Print Official Receipt</button>
    </div>
</div>

<div class="receipt-box">
    <div class="watermark">OFFICIAL RECEIPT</div>

    <div class="header">
        @if($payment->school?->logo && file_exists(public_path('storage/' . $payment->school->logo)))
            <img src="{{ public_path('storage/' . $payment->school->logo) }}" alt="{{ $payment->school->name }} logo">
        @endif
        <h2>{{ $payment->school?->name ?? 'NAIROBI PREMIER ACADEMY' }}</h2>
        <p>{{ $payment->school?->address }}</p>
        <p>{{ $payment->school?->motto ?? 'Strive for Excellence and Integrity' }}</p>
        <p>Email: {{ $payment->school?->email ?? 'info@school.ac.ke' }} | Phone: {{ $payment->school?->phone ?? '+254 711 223 344' }}</p>
    </div>

    <div class="title">OFFICIAL FEE PAYMENT RECEIPT</div>

    <div class="row">
        <span class="lbl">Receipt Number:</span>
        <span class="val" style="font-family: monospace; font-size: 1rem; color: #2563eb;">{{ $payment->receipt_number }}</span>
    </div>
    <div class="row">
        <span class="lbl">Payment Date:</span>
        <span class="val">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d F Y, H:i A') }}</span>
    </div>
    <div class="row">
        <span class="lbl">Student Name:</span>
        <span class="val">{{ $payment->student?->full_name }}</span>
    </div>
    <div class="row">
        <span class="lbl">Admission Number:</span>
        <span class="val">{{ $payment->student?->admission_number }}</span>
    </div>
    <div class="row">
        <span class="lbl">Class / Stream:</span>
        <span class="val">{{ $payment->student?->schoolClass?->name }} ({{ $payment->student?->stream?->name ?? 'General' }})</span>
    </div>
    @if($payment->invoice)
    <div class="row">
        <span class="lbl">Academic Year / Term:</span>
        <span class="val">{{ $payment->invoice->academicYear?->name ?? '—' }} / {{ $payment->invoice->term?->name ?? '—' }}</span>
    </div>
    @endif
    <div class="row">
        <span class="lbl">Payment Channel:</span>
        <span class="val">
            {{ $payment->method_label }} &bull; Ref: {{ $payment->reference_code }}
            @if($payment->bankAccount)
                <div style="font-size:0.8rem;color:#64748b;font-weight:normal">A/C: {{ $payment->bankAccount->bank_name }} ({{ $payment->bankAccount->account_number }})</div>
            @endif
        </span>
    </div>

    <div class="amount-box">
        <div>
            <div style="font-size: 0.8rem; color: #64748b;">AMOUNT RECEIVED</div>
            <div style="font-size: 0.85rem; font-weight: 600;">Kenya Shillings</div>
        </div>
        <div class="amount-val">
            KES {{ number_format($payment->amount, 2) }}
        </div>
    </div>

    @if($payment->balance_before !== null && $payment->balance_after !== null)
    <div class="row">
        <span class="lbl">Total outstanding balance before payment:</span>
        <span class="val">KES {{ number_format((float) $payment->balance_before, 2) }}</span>
    </div>
    <div class="row" style="background:#f1f5f9;padding:8px;border-radius:4px">
        <span class="lbl">Total outstanding balance after payment:</span>
        <span class="val">KES {{ number_format((float) $payment->balance_after, 2) }}</span>
    </div>
    @endif

    @if($payment->invoice)
    <div class="row" style="background:#f1f5f9;padding:8px;border-radius:4px">
        <span class="lbl">Current related invoice balance:</span>
        <span class="val" style="color:{{ $payment->invoice->balance > 0 ? '#dc2626' : '#00A651' }}">
            KES {{ number_format($payment->invoice->balance, 2) }}
        </span>
    </div>
    @endif

    <div class="footer">
        <div>
            <div><strong>Received By:</strong> {{ $payment->receivedBy?->name ?? 'Bursar Counter' }}</div>
            <div style="color: #94a3b8; font-size: 0.75rem; margin-top: 4px;">System-generated and validated by CloudKazi SMIS</div>
        </div>
        <div style="text-align: right;">
            <div><strong>Official Stamp & Signature</strong></div>
            <div style="margin-top: 25px; border-bottom: 1px dashed #64748b; width: 140px;"></div>
        </div>
    </div>
</div>

</body>
</html>
