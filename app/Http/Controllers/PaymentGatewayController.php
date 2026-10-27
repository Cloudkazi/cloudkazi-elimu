<?php

namespace App\Http\Controllers;

use App\Models\FeePayment;
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

class PaymentGatewayController extends Controller
{
    protected MpesaDarajaService $mpesaService;

    public function __construct(MpesaDarajaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    private function activeSchoolId(): int
    {
        return (int) (session('active_school_id') ?? Auth::user()->school_id);
    }

    /**
     * Show Payment Gateway Settings & Bank Accounts
     */
    public function settings()
    {
        $schoolId = $this->activeSchoolId();
        $settings = SchoolSetting::firstOrCreate(['school_id' => $schoolId]);
        $bankAccounts = SchoolBankAccount::where('school_id', $schoolId)->latest()->get();

        $defaultMethods = [
            'mpesa_stk' => 'M-Pesa STK Push',
            'mpesa_paybill' => 'M-Pesa Paybill',
            'mpesa_till' => 'M-Pesa Buy Goods Till',
            'bank_transfer' => 'Bank Transfer (EFT/RTGS/PesaLink)',
            'bank_deposit' => 'Bank Branch Deposit',
            'cash' => 'Cash Counter',
            'cheque' => 'Bank Cheque',
        ];

        return view('finance.gateway_settings', compact('settings', 'bankAccounts', 'defaultMethods'));
    }

    /**
     * Update M-Pesa Daraja & Gateway configuration
     */
    public function updateSettings(Request $request)
    {
        $schoolId = $this->activeSchoolId();

        $validated = $request->validate([
            'daraja_environment' => 'required|in:sandbox,production',
            'daraja_shortcode' => 'nullable|string|max:30',
            'daraja_consumer_key' => 'nullable|string|max:255',
            'daraja_consumer_secret' => 'nullable|string|max:255',
            'daraja_passkey' => 'nullable|string|max:255',
            'daraja_paybill_number' => 'nullable|string|max:30',
            'daraja_till_number' => 'nullable|string|max:30',
            'daraja_business_name' => 'nullable|string|max:100',
            'daraja_callback_url' => 'nullable|url|max:255',
            'enabled_methods' => 'nullable|array',
        ]);

        $settings = SchoolSetting::firstOrCreate(['school_id' => $schoolId]);
        $settings->update([
            'daraja_environment' => $validated['daraja_environment'],
            'daraja_shortcode' => $validated['daraja_shortcode'] ?? $settings->daraja_shortcode,
            'daraja_consumer_key' => $validated['daraja_consumer_key'],
            'daraja_consumer_secret' => $validated['daraja_consumer_secret'],
            'daraja_passkey' => $validated['daraja_passkey'],
            'daraja_paybill_number' => $validated['daraja_paybill_number'],
            'daraja_till_number' => $validated['daraja_till_number'],
            'daraja_business_name' => $validated['daraja_business_name'],
            'daraja_callback_url' => $validated['daraja_callback_url'],
            'enabled_payment_methods' => $validated['enabled_methods'] ?? array_keys([
                'mpesa_stk' => 1, 'mpesa_paybill' => 1, 'mpesa_till' => 1,
                'bank_transfer' => 1, 'bank_deposit' => 1, 'cash' => 1, 'cheque' => 1,
            ]),
        ]);

        return back()->with('success', 'Payment gateway settings updated successfully.');
    }

    /**
     * Store new School Bank Account
     */
    public function storeBankAccount(Request $request)
    {
        $schoolId = $this->activeSchoolId();

        $validated = $request->validate([
            'bank_name' => 'required|string|max:100',
            'account_name' => 'required|string|max:150',
            'account_number' => 'required|string|max:50',
            'branch' => 'nullable|string|max:100',
            'bank_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:255',
            'is_primary' => 'nullable|boolean',
        ]);

        $isPrimary = $request->boolean('is_primary');

        if ($isPrimary) {
            SchoolBankAccount::where('school_id', $schoolId)->update(['is_primary' => false]);
        }

        // If this is the first account, make it primary automatically
        $hasAccounts = SchoolBankAccount::where('school_id', $schoolId)->exists();
        if (!$hasAccounts) {
            $isPrimary = true;
        }

        SchoolBankAccount::create([
            'school_id' => $schoolId,
            'bank_name' => $validated['bank_name'],
            'account_name' => $validated['account_name'],
            'account_number' => $validated['account_number'],
            'branch' => $validated['branch'] ?? null,
            'bank_code' => $validated['bank_code'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
            'is_primary' => $isPrimary,
        ]);

        return back()->with('success', "Bank account {$validated['bank_name']} ({$validated['account_number']}) added successfully.");
    }

    /**
     * Toggle Bank Account Status
     */
    public function toggleBankAccount(SchoolBankAccount $bankAccount)
    {
        $schoolId = $this->activeSchoolId();
        if ($bankAccount->school_id !== $schoolId) {
            abort(403);
        }

        $bankAccount->update(['is_active' => !$bankAccount->is_active]);

        $status = $bankAccount->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Bank account {$bankAccount->bank_name} {$status}.");
    }

    /**
     * Set Bank Account as Primary
     */
    public function setPrimaryBankAccount(SchoolBankAccount $bankAccount)
    {
        $schoolId = $this->activeSchoolId();
        if ($bankAccount->school_id !== $schoolId) {
            abort(403);
        }

        SchoolBankAccount::where('school_id', $schoolId)->update(['is_primary' => false]);
        $bankAccount->update(['is_primary' => true, 'is_active' => true]);

        return back()->with('success', "{$bankAccount->bank_name} ({$bankAccount->account_number}) set as primary account.");
    }

    /**
     * Delete Bank Account
     */
    public function destroyBankAccount(SchoolBankAccount $bankAccount)
    {
        $schoolId = $this->activeSchoolId();
        if ($bankAccount->school_id !== $schoolId) {
            abort(403);
        }

        // Check if there are attached payments
        if ($bankAccount->payments()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete bank account with recorded payments. You can deactivate it instead.']);
        }

        $bankAccount->delete();
        return back()->with('success', 'Bank account removed.');
    }

    /**
     * Reconciliation Dashboard
     */
    public function reconciliation()
    {
        $schoolId = $this->activeSchoolId();

        $mpesaTransactions = MpesaTransaction::with(['student.schoolClass', 'invoice'])
            ->where('school_id', $schoolId)
            ->latest('transaction_date')
            ->paginate(15, ['*'], 'mpesa_page');

        $bankPayments = FeePayment::with(['student.schoolClass', 'bankAccount', 'receivedBy'])
            ->where('school_id', $schoolId)
            ->whereIn('payment_method', ['bank', 'bank_transfer', 'bank_deposit'])
            ->latest('payment_date')
            ->paginate(15, ['*'], 'bank_page');

        $totalMpesa = FeePayment::where('school_id', $schoolId)
            ->whereIn('payment_method', ['mpesa', 'mpesa_stk', 'mpesa_paybill', 'mpesa_till'])
            ->sum('amount');

        $totalBank = FeePayment::where('school_id', $schoolId)
            ->whereIn('payment_method', ['bank', 'bank_transfer', 'bank_deposit'])
            ->sum('amount');

        $unallocatedInvoices = StudentInvoice::where('school_id', $schoolId)
            ->where('balance', '>', 0)
            ->count();

        $totalArrears = StudentInvoice::where('school_id', $schoolId)->sum('balance');

        $students = Student::where('school_id', $schoolId)->where('status', 'active')->get();
        $bankAccounts = SchoolBankAccount::where('school_id', $schoolId)->where('is_active', true)->get();

        return view('finance.reconciliation', compact(
            'mpesaTransactions',
            'bankPayments',
            'totalMpesa',
            'totalBank',
            'unallocatedInvoices',
            'totalArrears',
            'students',
            'bankAccounts'
        ));
    }

    /**
     * Parent Portal self-service payment submission (M-Pesa STK Push)
     */
    public function parentInitiatePayment(Request $request)
    {
        $user = Auth::user();
        if (!$user->isParent()) {
            abort(403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'invoice_id' => 'nullable|exists:student_invoices,id',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        if (!$user->canAccessStudent($student)) {
            abort(403, 'Unauthorized student selection.');
        }

        $result = $this->mpesaService->initiateStkPush(
            schoolId: $student->school_id,
            studentId: $student->id,
            phone: $validated['phone'],
            amount: (float) $validated['amount'],
            invoiceId: $validated['invoice_id'] ?? null,
            source: 'parent_portal'
        );

        return back()->with('success', $result['message']);
    }
}
