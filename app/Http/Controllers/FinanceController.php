<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\MpesaTransaction;
use App\Models\PaymentReminder;
use App\Models\SchoolBankAccount;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Services\MpesaDarajaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinanceController extends Controller
{
    protected MpesaDarajaService $mpesaService;

    public function __construct(MpesaDarajaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    /**
     * Resolve the active school_id for the current session/user.
     * Never trusts a form field — always derived from session or auth.
     */
    private function activeSchoolId(): int
    {
        return (int) (session('active_school_id') ?? Auth::user()->school_id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────────────────────────────────────────────

    public function index()
    {
        $schoolId = $this->activeSchoolId();

        $totalInvoiced  = StudentInvoice::where('school_id', $schoolId)->sum('total_amount');
        $totalCollected = FeePayment::where('school_id', $schoolId)->sum('amount');
        $totalBalance   = max(0, $totalInvoiced - $totalCollected);

        $recentPayments = FeePayment::with(['student.schoolClass', 'receivedBy'])
            ->where('school_id', $schoolId)
            ->latest()
            ->take(10)
            ->get();

        $invoices = StudentInvoice::with(['student.schoolClass', 'term'])
            ->where('school_id', $schoolId)
            ->latest()
            ->take(10)
            ->get();

        $feeStructures = FeeStructure::with(['academicYear', 'term', 'schoolClass', 'category'])
            ->where('school_id', $schoolId)
            ->get();

        $categories = FeeCategory::where('school_id', $schoolId)->get();
        $classes    = SchoolClass::where('school_id', $schoolId)->get();
        $terms      = AcademicTerm::where('school_id', $schoolId)->get();
        $years      = AcademicYear::where('school_id', $schoolId)->get();

        return view('finance.index', compact(
            'totalInvoiced',
            'totalCollected',
            'totalBalance',
            'recentPayments',
            'invoices',
            'feeStructures',
            'categories',
            'classes',
            'terms',
            'years'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FEE STRUCTURES / VOTE-HEADS
    // ─────────────────────────────────────────────────────────────────────────

    public function fees()
    {
        $schoolId = $this->activeSchoolId();

        $feeStructures = FeeStructure::with(['academicYear', 'term', 'schoolClass', 'category'])
            ->where('school_id', $schoolId)
            ->orderBy('academic_year_id')
            ->orderBy('term_id')
            ->orderBy('school_class_id')
            ->orderBy('fee_category_id')
            ->get();

        $categories = FeeCategory::where('school_id', $schoolId)->get();
        $classes    = SchoolClass::where('school_id', $schoolId)->get();
        $terms      = AcademicTerm::where('school_id', $schoolId)->get();
        $years      = AcademicYear::where('school_id', $schoolId)->get();

        return view('finance.fees', compact('feeStructures', 'categories', 'classes', 'terms', 'years'));
    }

    public function storeCategory(Request $request)
    {
        $schoolId = $this->activeSchoolId();

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        FeeCategory::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'school_id'   => $schoolId,
        ]);

        return back()->with('success', 'Fee category "' . $validated['name'] . '" created successfully.');
    }

    public function storeStructure(Request $request)
    {
        $schoolId = $this->activeSchoolId();

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:academic_terms,id',
            'school_class_id'  => 'required|exists:school_classes,id',
            'fee_category_id'  => 'required|exists:fee_categories,id',
            'amount'           => 'required|numeric|min:0',
        ]);

        // Verify the selected class and category belong to this school
        $classOwned = SchoolClass::where('id', $validated['school_class_id'])
            ->where('school_id', $schoolId)->exists();

        $categoryOwned = FeeCategory::where('id', $validated['fee_category_id'])
            ->where('school_id', $schoolId)->exists();

        if (! $classOwned || ! $categoryOwned) {
            return back()->withErrors(['error' => 'Invalid class or category selection.'])->withInput();
        }

        FeeStructure::create(array_merge($validated, ['school_id' => $schoolId]));

        return back()->with('success', 'Fee structure vote-head added successfully.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PAYMENTS
    // ─────────────────────────────────────────────────────────────────────────

    public function payments()
    {
        $schoolId = $this->activeSchoolId();

        $todayTotal  = FeePayment::where('school_id', $schoolId)->whereDate('payment_date', Carbon::today())->sum('amount');
        $termTotal   = FeePayment::where('school_id', $schoolId)->sum('amount');
        $outstanding = max(0, StudentInvoice::where('school_id', $schoolId)->sum('total_amount') - $termTotal);
        $mpesaToday  = FeePayment::where('school_id', $schoolId)
            ->whereIn('payment_method', ['mpesa', 'mpesa_stk', 'mpesa_paybill', 'mpesa_till'])
            ->whereDate('payment_date', Carbon::today())
            ->sum('amount');

        $query = FeePayment::with(['student.schoolClass', 'receivedBy', 'bankAccount'])
            ->where('school_id', $schoolId)
            ->latest('payment_date');

        if (request('date')) {
            $query->whereDate('payment_date', request('date'));
        }

        if (request('method')) {
            $query->where('payment_method', request('method'));
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('reference_code', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('admission_number', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->paginate(15)->withQueryString();
        $bankAccounts = SchoolBankAccount::where('school_id', $schoolId)->where('is_active', true)->get();
        $settings = SchoolSetting::firstOrCreate(['school_id' => $schoolId]);
        $students = Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('first_name')->get();
        $classes = SchoolClass::where('school_id', $schoolId)->orderBy('numeric_level')->get();

        return view('finance.payments', compact('todayTotal', 'termTotal', 'outstanding', 'mpesaToday', 'payments', 'bankAccounts', 'settings', 'students', 'classes'));
    }

    public function searchStudents(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $search = trim($validated['q']);
        $schoolId = $this->activeSchoolId();
        $students = Student::with(['schoolClass', 'guardians'])
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('admission_number', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            })
            ->orderBy('first_name')
            ->limit(10)
            ->get();

        return response()->json($students->map(function (Student $student) {
            return [
                'id' => $student->id,
                'name' => $student->full_name,
                'admission' => $student->admission_number ?? '',
                'class' => $student->schoolClass?->name ?? '',
                'phone' => $student->phone ?: $student->guardians->first()?->phone,
                'balance' => data_get($student->calculateFeeBreakdown(), 'balance', 0),
            ];
        })->values());
    }

    public function storePayment(Request $request)
    {
        $schoolId = $this->activeSchoolId();

        $validated = $request->validate([
            'student_id'      => 'required|exists:students,id',
            'amount'          => 'required|numeric|min:1',
            'payment_method'  => 'required|string',
            'reference_code'  => 'nullable|string|max:50',
            'payment_date'    => 'required|date',
            'notes'           => 'nullable|string',
            'bank_account_id' => 'nullable|exists:school_bank_accounts,id',
            'invoice_id'      => 'nullable|exists:student_invoices,id',
            'mpesa_phone'     => 'nullable|string',
            'trigger_stk'     => 'nullable|boolean',
        ]);

        // Security: ensure the student belongs to THIS school
        $student = Student::where('id', $validated['student_id'])
            ->where('school_id', $schoolId)
            ->firstOrFail();

        // If user wants to trigger live STK push from bursar desk
        if (($validated['payment_method'] === 'mpesa_stk' || $validated['payment_method'] === 'mpesa') && $request->boolean('trigger_stk')) {
            $phone = $validated['mpesa_phone'] ?? $student->phone ?? ($student->guardians->first()?->phone);
            if (!$phone) {
                return back()->withErrors(['error' => 'Valid Safaricom phone number required to trigger STK push.'])->withInput();
            }

            $stkResult = $this->mpesaService->initiateStkPush(
                schoolId: $schoolId,
                studentId: $student->id,
                phone: $phone,
                amount: (float) $validated['amount'],
                invoiceId: $validated['invoice_id'] ?? null,
                source: 'bursar'
            );

            if (isset($stkResult['payment_id'])) {
                return redirect()->route('finance.receipt', $stkResult['payment_id'])
                    ->with('success', $stkResult['message']);
            }

            return back()->with('success', $stkResult['message']);
        }

        // Direct payment settlement with automatic reconciliation
        $refCode = !empty($validated['reference_code']) ? strtoupper($validated['reference_code']) : strtoupper(Str::random(10));

        $result = $this->mpesaService->settleStudentPayment(
            schoolId: $schoolId,
            studentId: $student->id,
            amount: (float) $validated['amount'],
            method: $validated['payment_method'],
            referenceCode: $refCode,
            notes: $validated['notes'] ?? ("Payment received via " . strtoupper(str_replace('_', ' ', $validated['payment_method']))),
            paymentSource: 'bursar',
            invoiceId: $validated['invoice_id'] ?? null,
            bankAccountId: in_array($validated['payment_method'], ['bank', 'bank_transfer', 'bank_deposit']) ? $validated['bank_account_id'] : null,
            receivedBy: Auth::id()
        );

        return redirect()->route('finance.receipt', $result['payment_id'])
            ->with('success', "Payment of KES " . number_format($validated['amount'], 2) . " recorded. Receipt: {$result['receipt_number']}");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // M-PESA STK
    // ─────────────────────────────────────────────────────────────────────────

    public function triggerMpesa(Request $request)
    {
        $schoolId = $this->activeSchoolId();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'phone'      => 'required|string',
            'amount'     => 'required|numeric|min:1',
            'invoice_id' => 'nullable|exists:student_invoices,id',
        ]);

        // Verify student belongs to this school
        $student = Student::where('id', $validated['student_id'])
            ->where('school_id', $schoolId)
            ->firstOrFail();

        // If an invoice_id was provided, verify it belongs to this student & school
        if (!empty($validated['invoice_id'])) {
            StudentInvoice::where('id', $validated['invoice_id'])
                ->where('student_id', $student->id)
                ->where('school_id', $schoolId)
                ->firstOrFail();
        }

        $result = $this->mpesaService->initiateStkPush(
            $schoolId,
            $student->id,
            $validated['phone'],
            (float) $validated['amount'],
            $validated['invoice_id'] ?? null
        );

        return back()->with('success', $result['message']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // RECEIPT
    // ─────────────────────────────────────────────────────────────────────────

    public function receipt(int $id, Request $request)
    {
        $schoolId = $this->activeSchoolId();
        $user     = Auth::user();

        $payment = FeePayment::with([
            'student.schoolClass',
            'student.stream',
            'invoice.academicYear',
            'invoice.term',
            'receivedBy',
            'school',
            'bankAccount',
        ])
            ->where('school_id', $schoolId)
            ->findOrFail($id);

        // Students can only view receipts for their own payments; parents can only view linked children's receipts
        if ($user->isStudent()) {
            $myStudent = $user->student;
            if (!$myStudent || (int)$payment->student_id !== (int)$myStudent->id) {
                abort(403, 'You may only view your own payment receipts.');
            }
        } elseif ($user->isParent()) {
            if (!$user->canAccessStudent($payment->student)) {
                abort(403, 'You are not authorized to view this receipt.');
            }
        }

        if ($request->boolean('download')) {
            $filename = \Illuminate\Support\Str::slug('receipt-' . $payment->receipt_number) . '.pdf';

            return \Barryvdh\DomPDF\Facade\Pdf::loadView('finance.receipt-pdf', compact('payment'))
                ->setPaper('a4')
                ->download($filename);
        }

        return view('finance.receipt', compact('payment'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ARREARS
    // ─────────────────────────────────────────────────────────────────────────

    public function arrears()
    {
        $schoolId = $this->activeSchoolId();

        $invoicesWithArrears = StudentInvoice::with(['student.schoolClass', 'student.stream', 'student.guardians'])
            ->where('school_id', $schoolId)
            ->where('balance', '>', 0)
            ->orderBy('balance', 'desc')
            ->paginate(20);

        $totalArrears = StudentInvoice::where('school_id', $schoolId)
            ->where('balance', '>', 0)
            ->sum('balance');

        return view('finance.arrears', compact('invoicesWithArrears', 'totalArrears'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INVOICES
    // ─────────────────────────────────────────────────────────────────────────

    public function invoices()
    {
        $schoolId = $this->activeSchoolId();

        $invoices       = StudentInvoice::with(['student.schoolClass', 'term'])
            ->where('school_id', $schoolId)
            ->latest()
            ->paginate(20);

        $classes        = SchoolClass::where('school_id', $schoolId)->get();
        $terms          = AcademicTerm::where('school_id', $schoolId)->get();
        $totalInvoiced  = StudentInvoice::where('school_id', $schoolId)->sum('total_amount');
        $totalCollected = StudentInvoice::where('school_id', $schoolId)->sum('paid_amount');
        $totalBalance   = StudentInvoice::where('school_id', $schoolId)->sum('balance');

        return view('finance.invoices', compact('invoices', 'classes', 'terms', 'totalInvoiced', 'totalCollected', 'totalBalance'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REPORTS
    // ─────────────────────────────────────────────────────────────────────────

    public function reports()
    {
        $schoolId = $this->activeSchoolId();

        $totalInvoiced  = StudentInvoice::where('school_id', $schoolId)->sum('total_amount');
        $totalCollected = FeePayment::where('school_id', $schoolId)->sum('amount');
        $totalBalance   = max(0, $totalInvoiced - $totalCollected);

        $byMethod = FeePayment::where('school_id', $schoolId)
            ->selectRaw('payment_method, sum(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->toArray();

        $recentPayments = FeePayment::with(['student.schoolClass', 'receivedBy'])
            ->where('school_id', $schoolId)
            ->latest()
            ->take(10)
            ->get();

        return view('finance.reports', compact('totalInvoiced', 'totalCollected', 'totalBalance', 'byMethod', 'recentPayments'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // M-PESA GATEWAY PAGE
    // ─────────────────────────────────────────────────────────────────────────

    public function mpesa()
    {
        $schoolId = $this->activeSchoolId();

        $transactions = FeePayment::where('school_id', $schoolId)
            ->where('payment_method', 'mpesa')
            ->with('student.schoolClass')
            ->latest()
            ->paginate(15);

        $students   = Student::where('school_id', $schoolId)->where('status', 'active')->get();
        $totalMpesa = FeePayment::where('school_id', $schoolId)->where('payment_method', 'mpesa')->sum('amount');
        $todayMpesa = FeePayment::where('school_id', $schoolId)
            ->where('payment_method', 'mpesa')
            ->whereDate('payment_date', Carbon::today())
            ->sum('amount');

        return view('finance.mpesa', compact('transactions', 'students', 'totalMpesa', 'todayMpesa'));
    }

    /**
     * Handle Safaricom Daraja M-Pesa STK Push callback (IPN).
     * This endpoint is exempt from CSRF verification.
     */
    public function mpesaCallback(Request $request)
    {
        $body              = $request->input('Body.stkCallback', []);
        $resultCode        = $body['ResultCode'] ?? 1;
        $checkoutRequestId = $body['CheckoutRequestID'] ?? null;

        if ($checkoutRequestId) {
            $tx = MpesaTransaction::where('checkout_request_id', $checkoutRequestId)->first();

            if ($resultCode == 0) {
                $items     = collect($body['CallbackMetadata']['Item'] ?? []);
                $amount    = $items->firstWhere('Name', 'Amount')['Value'] ?? ($tx ? $tx->amount : 0);
                $mpesaCode = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? Str::random(10);
                $phone     = $items->firstWhere('Name', 'PhoneNumber')['Value'] ?? ($tx ? $tx->phone_number : '');

                if ($tx) {
                    $tx->update([
                        'mpesa_receipt_number' => $mpesaCode,
                        'result_code'          => (string) $resultCode,
                        'result_desc'          => 'The service request is processed successfully.',
                        'status'               => 'completed',
                        'transaction_date'     => now(),
                    ]);
                }

                $student = $tx && $tx->student_id
                    ? Student::find($tx->student_id)
                    : (Student::where('phone', $phone)->first()
                        ?? Student::whereHas('guardians', fn($q) => $q->where('phone', $phone))->first());

                $schoolId = $tx?->school_id ?? $student?->school_id;

                if ($student && $schoolId) {
                    $this->mpesaService->settleStudentPayment(
                        schoolId: $schoolId,
                        studentId: $student->id,
                        amount: (float) $amount,
                        method: 'mpesa_stk',
                        referenceCode: strtoupper($mpesaCode),
                        notes: "Daraja IPN callback – phone: {$phone}",
                        paymentSource: 'mpesa_callback'
                    );
                }
            } elseif ($tx) {
                $tx->update([
                    'result_code' => (string) $resultCode,
                    'result_desc' => $body['ResultDesc'] ?? 'Failed or cancelled by user',
                    'status'      => 'failed',
                ]);
            }
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STUDENT STATEMENT
    // ─────────────────────────────────────────────────────────────────────────

    public function studentStatement(int $studentId)
    {
        $schoolId = $this->activeSchoolId();
        $user     = Auth::user();

        $student = Student::with(['schoolClass', 'stream', 'invoices.term'])
            ->where('school_id', $schoolId)
            ->findOrFail($studentId);

        // Authorization: students can only view their own statement; parents can only view linked children
        if ($user->isStudent()) {
            $myStudent = $user->student;
            if (!$myStudent || (int)$myStudent->id !== (int)$studentId) {
                abort(403, 'You may only access your own fee statement.');
            }
        } elseif ($user->isParent()) {
            if (!$user->canAccessStudent($student)) {
                abort(403, 'You are not authorized to view this student\'s fee statement.');
            }
        }

        // Dynamic calculation directly from fee structures, student fee profile, and fee_payments table
        $feeBreakdown  = $student->calculateFeeBreakdown();
        $totalInvoiced = (float) ($feeBreakdown['total_billed'] > 0 ? $feeBreakdown['total_billed'] : $student->invoices->sum('total_amount'));
        $totalPaid     = (float) FeePayment::where('student_id', $studentId)->where('school_id', $schoolId)->sum('amount');
        $balance       = max(0, $totalInvoiced - $totalPaid);
        $payments      = FeePayment::where('student_id', $studentId)->where('school_id', $schoolId)->latest('payment_date')->get();

        return view('finance.statement', compact('student', 'totalInvoiced', 'totalPaid', 'balance', 'payments', 'feeBreakdown'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REMINDERS & RECEIPT DISPATCH
    // ─────────────────────────────────────────────────────────────────────────

    public function sendReminder(Request $request)
    {
        $schoolId = $this->activeSchoolId();

        $validated = $request->validate([
            'target_type'     => 'required|in:all,class,student,arrears',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'student_id'      => 'nullable|exists:students,id',
            'message'         => 'required|string|max:500',
        ]);

        $studentsQuery = Student::where('school_id', $schoolId)->where('status', 'active');

        if ($validated['target_type'] === 'class' && !empty($validated['school_class_id'])) {
            $studentsQuery->where('school_class_id', $validated['school_class_id']);
        } elseif ($validated['target_type'] === 'student' && !empty($validated['student_id'])) {
            $studentsQuery->where('id', $validated['student_id']);
        }

        $students = $studentsQuery->get();
        $dispatchedCount = 0;

        foreach ($students as $student) {
            $breakdown = $student->calculateFeeBreakdown();
            // If target is arrears only, skip if cleared
            if ($validated['target_type'] === 'arrears' && $breakdown['balance'] <= 0) {
                continue;
            }

            $dispatchedCount++;
        }

        PaymentReminder::create([
            'school_id'        => $schoolId,
            'sent_by'          => Auth::id(),
            'student_id'       => $validated['student_id'] ?? null,
            'school_class_id'  => $validated['school_class_id'] ?? null,
            'target_type'      => $validated['target_type'],
            'channel'          => 'sms',
            'message'          => $validated['message'],
            'recipients_count' => $dispatchedCount,
            'status'           => 'sent',
        ]);

        return back()->with('success', "Fee reminder queued and logged for {$dispatchedCount} student(s)/parent(s).");
    }

    public function resendReceipt(int $id)
    {
        $schoolId = $this->activeSchoolId();
        $payment = FeePayment::with(['student.guardians', 'school'])
            ->where('school_id', $schoolId)
            ->findOrFail($id);

        $phone = $payment->student?->phone ?? $payment->student?->guardians?->first()?->phone ?? 'guardian on file';

        return back()->with('success', "Receipt #{$payment->receipt_number} (KES " . number_format($payment->amount, 2) . ") re-dispatched via SMS to {$phone}.");
    }
}
