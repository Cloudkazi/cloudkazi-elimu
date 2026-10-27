<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $record->employee?->full_name }} ({{ $record->payroll_month ?? $record->payrollPeriod?->name }})</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #1e293b;
            background: #f1f5f9;
            margin: 0;
            padding: 30px 15px;
        }
        .payslip-container {
            max-width: 780px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.06);
            padding: 40px;
            box-sizing: border-box;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .school-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 4px;
            font-family: 'Outfit', sans-serif;
        }
        .school-subtitle {
            font-size: 0.85rem;
            color: #64748b;
            margin: 0;
        }
        .payslip-badge {
            text-align: right;
        }
        .payslip-badge h2 {
            font-size: 1.3rem;
            margin: 0 0 4px;
            color: #4f46e5;
            font-family: 'Outfit', sans-serif;
        }
        .payslip-period {
            font-weight: 600;
            color: #334155;
            font-size: 0.95rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
            font-size: 0.875rem;
        }
        .info-col div {
            margin-bottom: 6px;
        }
        .info-col strong {
            color: #475569;
            width: 140px;
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 0.875rem;
        }
        th, td {
            padding: 10px 14px;
            text-align: left;
        }
        th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        td {
            border-bottom: 1px solid #f1f5f9;
        }
        .amount-col {
            text-align: right;
            font-family: 'Outfit', monospace;
            font-weight: 600;
        }
        .net-pay-box {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border: 2px solid #86efac;
            border-radius: 10px;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .net-pay-label {
            font-size: 1rem;
            font-weight: 700;
            color: #166534;
        }
        .net-pay-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: #15803d;
            font-family: 'Outfit', sans-serif;
        }
        .payment-status-banner {
            padding: 14px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .status-paid {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
        }
        .status-unpaid {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        .status-partial {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }
        .footer-note {
            font-size: 0.75rem;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            text-align: center;
        }
        .actions-bar {
            max-width: 780px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
        }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-outline { background: #fff; border-color: #cbd5e1; color: #334155; }
        @media print {
            body { background: #fff; padding: 0; }
            .payslip-container { box-shadow: none; border: 1px solid #e2e8f0; border-radius: 0; padding: 20px; }
            .actions-bar { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="actions-bar">
        <a href="javascript:history.back()" class="btn btn-outline">← Back</a>
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print / Download PDF</button>
    </div>

    <div class="payslip-container">
        {{-- Header --}}
        <div class="header">
            <div>
                <h1 class="school-title">{{ $school->name ?? 'Kitui School' }}</h1>
                <p class="school-subtitle">P.O. Box 40-90200, Kitui, Kenya • info@kituischool.sc.ke</p>
                <p class="school-subtitle">Institutional Human Resources & Payroll Registry</p>
            </div>
            <div class="payslip-badge">
                <h2>OFFICIAL PAYSLIP</h2>
                <div class="payslip-period">{{ $record->payroll_month ?? $record->payrollPeriod?->name }}</div>
                <div style="font-size:0.75rem;color:#64748b;margin-top:2px">Ref: PR-{{ sprintf('%05d', $record->id) }}</div>
            </div>
        </div>

        {{-- Payment Status Banner --}}
        @if($record->payment_status === 'paid')
            <div class="payment-status-banner status-paid">
                <div>
                    <strong>✓ CONFIRMED PAID IN FULL</strong>
                    <div style="font-size:0.8rem">Disbursed via {{ $record->payment_method ?? 'Bank Transfer' }} • Ref: {{ $record->payment_reference ?? 'EFT' }}</div>
                </div>
                <div>Paid on: {{ $record->paid_at ? $record->paid_at->format('d M Y') : '—' }}</div>
            </div>
        @elseif($record->payment_status === 'partially_paid')
            <div class="payment-status-banner status-partial">
                <div>
                    <strong>⚠ PARTIALLY PAID</strong>
                    <div style="font-size:0.8rem">Amount Disbursed: KES {{ number_format($record->amount_paid, 2) }} • Outstanding: KES {{ number_format($record->balance_due, 2) }}</div>
                </div>
                <div>Status: Incomplete</div>
            </div>
        @else
            <div class="payment-status-banner status-unpaid">
                <div>
                    <strong>⚠ PAYMENT PENDING / UNPAID</strong>
                    <div style="font-size:0.8rem">This payslip is a statement of calculated salary and does NOT serve as proof of disbursement until paid.</div>
                </div>
                <div>Status: Unpaid</div>
            </div>
        @endif

        {{-- Employee Details Grid --}}
        <div class="info-grid">
            <div class="info-col">
                <div><strong>Employee Name:</strong> {{ $record->employee?->full_name }}</div>
                <div><strong>Employee No:</strong> <span style="font-family:monospace;font-weight:600">{{ $record->employee?->employee_no }}</span></div>
                <div><strong>Designation:</strong> {{ $record->employee?->designation }}</div>
                <div><strong>Department:</strong> {{ $record->employee?->department ?? 'General' }}</div>
                <div><strong>Employment Type:</strong> <span style="text-transform:capitalize">{{ $record->employee?->employment_type ?? 'Permanent' }}</span></div>
            </div>
            <div class="info-col">
                <div><strong>KRA PIN:</strong> <span style="font-family:monospace">{{ $record->employee?->kra_pin ?? '—' }}</span></div>
                <div><strong>NSSF Number:</strong> <span style="font-family:monospace">{{ $record->employee?->nssf_no ?? '—' }}</span></div>
                <div><strong>SHIF/NHIF No:</strong> <span style="font-family:monospace">{{ $record->employee?->nhif_no ?? '—' }}</span></div>
                <div><strong>Bank Name:</strong> {{ $record->employee?->bank_name ?? '—' }}</div>
                <div><strong>Account No:</strong> <span style="font-family:monospace">{{ $record->employee?->bank_account_number ?? '—' }}</span></div>
            </div>
        </div>

        {{-- Earnings Breakdown --}}
        <table>
            <thead>
                <tr>
                    <th>Earnings Item</th>
                    <th class="amount-col">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Monthly Salary</td>
                    <td class="amount-col">{{ number_format($record->basic_salary, 2) }}</td>
                </tr>
                @if($record->allowances > 0)
                <tr>
                    <td>Allowances / Benefits</td>
                    <td class="amount-col">{{ number_format($record->allowances, 2) }}</td>
                </tr>
                @endif
                <tr style="background:#f8fafc;font-weight:700">
                    <td>GROSS SALARY (EARNINGS)</td>
                    <td class="amount-col">{{ number_format($record->gross_salary, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Deductions Breakdown --}}
        <table>
            <thead>
                <tr>
                    <th>Statutory & Authorized Deductions (Kenya)</th>
                    <th class="amount-col">Amount (KES)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>PAYE (Pay As You Earn) Tax Payable</td>
                    <td class="amount-col" style="color:var(--danger)">{{ number_format($record->paye, 2) }}</td>
                </tr>
                <tr>
                    <td>NSSF Contribution (Tier I & Tier II - Employee)</td>
                    <td class="amount-col" style="color:var(--danger)">{{ number_format($record->nssf_employee, 2) }}</td>
                </tr>
                <tr>
                    <td>SHIF (Social Health Insurance Fund - 2.75%)</td>
                    <td class="amount-col" style="color:var(--danger)">{{ number_format($record->shif, 2) }}</td>
                </tr>
                <tr>
                    <td>Affordable Housing Levy (AHL - 1.5% Employee)</td>
                    <td class="amount-col" style="color:var(--danger)">{{ number_format($record->housing_levy_employee, 2) }}</td>
                </tr>
                @if($record->other_deductions > 0)
                <tr>
                    <td>Other Authorized Deductions</td>
                    <td class="amount-col" style="color:var(--danger)">{{ number_format($record->other_deductions, 2) }}</td>
                </tr>
                @endif
                <tr style="background:#fef2f2;font-weight:700;color:#991b1b">
                    <td>TOTAL EMPLOYEE DEDUCTIONS</td>
                    <td class="amount-col">- {{ number_format($record->deductions, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Net Pay Highlight --}}
        <div class="net-pay-box">
            <div>
                <div class="net-pay-label">NET TAKE-HOME SALARY PAYABLE</div>
                <small style="color:#166534">Gross Salary minus Total Statutory Deductions</small>
            </div>
            <div class="net-pay-value">KES {{ number_format($record->net_salary, 2) }}</div>
        </div>

        {{-- Statutory Employer Contributions Memo --}}
        <div style="background:#f8fafc;border-left:3px solid #6366f1;padding:10px 16px;border-radius:4px;font-size:0.8rem;color:#475569;margin-bottom:24px">
            <strong>Employer Statutory Contributions (Not deducted from staff salary):</strong><br>
            NSSF Employer Match: KES {{ number_format($record->nssf_employer, 2) }} • Affordable Housing Levy Employer Match: KES {{ number_format($record->housing_levy_employer, 2) }} • Total Institutional Contribution: KES {{ number_format($record->total_employer_contributions, 2) }}
        </div>

        {{-- Signatures --}}
        <div style="display:flex;justify-content:space-between;margin-top:40px;padding-top:20px;border-top:1px dashed #cbd5e1;font-size:0.85rem">
            <div style="text-align:center;width:200px">
                <div style="border-bottom:1px solid #94a3b8;height:35px;margin-bottom:6px"></div>
                <div>Prepared By (HR / Accounts)</div>
            </div>
            <div style="text-align:center;width:200px">
                <div style="border-bottom:1px solid #94a3b8;height:35px;margin-bottom:6px"></div>
                <div>Authorized Signatory (Principal)</div>
            </div>
            <div style="text-align:center;width:200px">
                <div style="border-bottom:1px solid #94a3b8;height:35px;margin-bottom:6px"></div>
                <div>Employee Signature & Date</div>
            </div>
        </div>

        <div class="footer-note">
            Generated by Cloudkazi SMIS • Official School Management Information System • Printed: {{ now()->format('d M Y H:i:s') }}
        </div>
    </div>

</body>
</html>
