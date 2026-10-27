<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $payment->receipt_number }}</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 12px; }
        .receipt { border: 2px solid #17365d; padding: 24px; }
        .header { text-align: center; border-bottom: 1px dashed #64748b; padding-bottom: 16px; }
        .logo { max-width: 72px; max-height: 72px; margin-bottom: 8px; }
        h1 { margin: 0; font-size: 20px; }
        h2 { margin: 18px 0; text-align: center; font-size: 16px; letter-spacing: 2px; }
        .row { padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .label { display: inline-block; width: 36%; color: #475569; }
        .value { font-weight: bold; }
        .amount { margin: 20px 0; padding: 14px; background: #f1f5f9; border: 1px solid #cbd5e1; font-size: 17px; font-weight: bold; color: #087443; }
        .footer { margin-top: 28px; }
    </style>
</head>
<body>
<div class="receipt">
    <div class="header">
        @if($payment->school?->logo && file_exists(public_path('storage/' . $payment->school->logo)))
            <img class="logo" src="{{ public_path('storage/' . $payment->school->logo) }}" alt="School logo">
        @endif
        <h1>{{ $payment->school?->name ?? 'School' }}</h1>
        @if($payment->school?->address)<div>{{ $payment->school->address }}</div>@endif
        @if($payment->school?->email || $payment->school?->phone)
            <div>{{ $payment->school?->email }}@if($payment->school?->email && $payment->school?->phone) · @endif{{ $payment->school?->phone }}</div>
        @endif
        @if($payment->school?->motto)<div>{{ $payment->school->motto }}</div>@endif
    </div>
    <h2>OFFICIAL SCHOOL FEE RECEIPT</h2>
    <div class="row"><span class="label">Receipt number</span><span class="value">{{ $payment->receipt_number }}</span></div>
    <div class="row"><span class="label">Student</span><span class="value">{{ $payment->student?->full_name }}</span></div>
    <div class="row"><span class="label">Admission number</span><span class="value">{{ $payment->student?->admission_number }}</span></div>
    <div class="row"><span class="label">Class / stream</span><span class="value">{{ $payment->student?->schoolClass?->name }} / {{ $payment->student?->stream?->name ?? 'General' }}</span></div>
    @if($payment->invoice)
    <div class="row"><span class="label">Academic year / term</span><span class="value">{{ $payment->invoice->academicYear?->name ?? '—' }} / {{ $payment->invoice->term?->name ?? '—' }}</span></div>
    @endif
    <div class="row"><span class="label">Payment date</span><span class="value">{{ $payment->payment_date?->format('d F Y') }}</span></div>
    <div class="row"><span class="label">Payment method</span><span class="value">{{ $payment->method_label }}</span></div>
    @if($payment->reference_code)
    <div class="row"><span class="label">Transaction / reference</span><span class="value">{{ $payment->reference_code }}</span></div>
    @endif
    <div class="amount">AMOUNT RECEIVED: KES {{ number_format((float) $payment->amount, 2) }}</div>
    @if($payment->balance_before !== null && $payment->balance_after !== null)
    <div class="row"><span class="label">Total outstanding balance before payment</span><span class="value">KES {{ number_format((float) $payment->balance_before, 2) }}</span></div>
    <div class="row"><span class="label">Total outstanding balance after payment</span><span class="value">KES {{ number_format((float) $payment->balance_after, 2) }}</span></div>
    @endif
    @if($payment->invoice)
    <div class="row"><span class="label">Current related invoice balance</span><span class="value">KES {{ number_format((float) $payment->invoice->balance, 2) }}</span></div>
    @endif
    @if($payment->bankAccount)
    <div class="row"><span class="label">School account</span><span class="value">{{ $payment->bankAccount->bank_name }} · {{ $payment->bankAccount->account_name }} · {{ $payment->bankAccount->account_number }}</span></div>
    @endif
    <div class="footer">
        <div><strong>Received by:</strong> {{ $payment->receivedBy?->name ?? 'Bursar Counter' }}</div>
        <p>System-generated official receipt. Retain this document for your records.</p>
        <p style="margin-top:32px">Authorized signature: ___________________________________</p>
    </div>
</div>
</body>
</html>
