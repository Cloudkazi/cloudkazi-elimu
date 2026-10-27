<?php

namespace App\Services;

use App\Models\FeePayment;
use App\Models\MpesaTransaction;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MpesaDarajaService
{
    /**
     * Initiate a real Lipa Na M-Pesa Online STK Push.
     *
     * IMPORTANT:
     * This method NEVER marks a payment as completed.
     * Completion only happens after Safaricom sends a successful callback.
     */
    public function initiateStkPush(
        int $schoolId,
        int $studentId,
        string $phone,
        float $amount,
        ?int $invoiceId = null,
        string $source = 'bursar'
    ): array {
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Payment amount must be greater than zero.',
            ];
        }

        /*
         * Verify student belongs to the active school.
         */
        $student = Student::where('id', $studentId)
            ->where('school_id', $schoolId)
            ->first();

        if (!$student) {
            return [
                'success' => false,
                'message' => 'Student was not found in the selected school.',
            ];
        }

        /*
         * Verify invoice belongs to the same student and school.
         */
        if ($invoiceId) {
            $invoice = StudentInvoice::where('id', $invoiceId)
                ->where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->first();

            if (!$invoice) {
                return [
                    'success' => false,
                    'message' => 'The selected invoice does not belong to this student.',
                ];
            }
        }

        /*
         * Normalize Kenyan phone number.
         */
        $formattedPhone = $this->formatPhoneNumber($phone);

        if (!$this->isValidKenyanPhone($formattedPhone)) {
            return [
                'success' => false,
                'message' => 'Invalid Kenyan M-Pesa phone number. Use format 07XXXXXXXX or 2547XXXXXXXX.',
            ];
        }

        /*
         * Load school-specific Daraja settings.
         */
        $settings = SchoolSetting::where('school_id', $schoolId)->first();

        if (!$settings) {
            Log::error('M-Pesa STK Push failed: school settings not found.', [
                'school_id' => $schoolId,
                'student_id' => $studentId,
            ]);

            return [
                'success' => false,
                'message' => 'M-Pesa payment settings have not been configured for this school.',
            ];
        }

        /*
         * Support both possible passkey column names.
         */
        $consumerKey = trim((string) ($settings->daraja_consumer_key ?? ''));
        $consumerSecret = trim((string) ($settings->daraja_consumer_secret ?? ''));

        $passkey = trim((string) (
            $settings->daraja_online_passkey
            ?? $settings->daraja_passkey
            ?? ''
        ));

        $shortcode = trim((string) (
            $settings->daraja_shortcode
            ?? '174379'
        ));

        $baseUrl = rtrim(
            (string) $settings->getDarajaBaseUrl(),
            '/'
        );

        /*
         * Callback URL.
         *
         * Safaricom must be able to reach this URL from the internet.
         * localhost / 127.0.0.1 will NOT work for the Safaricom callback.
         */
        $callbackUrl = trim((string) (
            $settings->daraja_callback_url
            ?: route('mpesa.callback')
        ));

        /*
         * Validate Daraja configuration BEFORE creating a transaction.
         */
        if ($consumerKey === '' || $consumerSecret === '') {
            Log::error('M-Pesa STK Push failed: Daraja consumer credentials missing.', [
                'school_id' => $schoolId,
                'environment' => $this->getDarajaEnvironment($settings),
            ]);

            return [
                'success' => false,
                'message' => 'M-Pesa Consumer Key and Consumer Secret are not configured.',
            ];
        }

        if ($passkey === '') {
            Log::error('M-Pesa STK Push failed: Daraja online passkey missing.', [
                'school_id' => $schoolId,
                'shortcode' => $shortcode,
            ]);

            return [
                'success' => false,
                'message' => 'M-Pesa Online Passkey is not configured.',
            ];
        }

        if ($shortcode === '') {
            return [
                'success' => false,
                'message' => 'M-Pesa Business Shortcode is not configured.',
            ];
        }

        if ($callbackUrl === '') {
            return [
                'success' => false,
                'message' => 'M-Pesa callback URL is not configured.',
            ];
        }

        /*
         * Warn if callback URL is local.
         *
         * We do NOT block the STK request here because Safaricom can still
         * accept the STK request, but the callback will not reach localhost.
         */
        if ($this->isLocalCallbackUrl($callbackUrl)) {
            Log::warning('M-Pesa callback URL appears to be local. Safaricom cannot reach localhost.', [
                'school_id' => $schoolId,
                'callback_url' => $callbackUrl,
            ]);
        }

        /*
         * Create an internal transaction record.
         *
         * These temporary IDs are replaced with the real Daraja IDs after
         * Safaricom accepts the STK request.
         */
        $merchantRequestId = 'MR-' . strtoupper(Str::random(16));
        $checkoutRequestId = 'PENDING-' . strtoupper(Str::random(20));

        $transaction = MpesaTransaction::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'invoice_id' => $invoiceId,
            'mpesa_type' => 'stk',
            'merchant_request_id' => $merchantRequestId,
            'checkout_request_id' => $checkoutRequestId,
            'phone_number' => $formattedPhone,
            'amount' => $amount,
            'status' => 'initiated',
            'transaction_date' => now(),
        ]);

        try {
            /*
             * Get OAuth access token.
             */
            $token = $this->getDarajaAccessToken($settings);

            if (!$token) {
                $transaction->update([
                    'status' => 'failed',
                    'result_code' => 'TOKEN_ERROR',
                    'result_desc' => 'Unable to obtain Daraja access token.',
                ]);

                return [
                    'success' => false,
                    'message' => 'Unable to connect to M-Pesa. Please check the Daraja Consumer Key and Consumer Secret.',
                    'transaction' => $transaction,
                ];
            }

            /*
             * Generate STK password.
             */
            $timestamp = Carbon::now()->format('YmdHis');

            $password = base64_encode(
                $shortcode . $passkey . $timestamp
            );

            /*
             * Ensure callback URL has no accidental whitespace.
             */
            $callbackUrl = trim($callbackUrl);

            /*
             * Send STK Push to Safaricom.
             */
            $response = Http::acceptJson()
                ->withToken($token)
                ->timeout(30)
                ->post(
                    $baseUrl . '/mpesa/stkpush/v1/processrequest',
                    [
                        'BusinessShortCode' => $shortcode,
                        'Password' => $password,
                        'Timestamp' => $timestamp,
                        'TransactionType' => 'CustomerPayBillOnline',
                        'Amount' => (int) round($amount),
                        'PartyA' => $formattedPhone,
                        'PartyB' => $shortcode,
                        'PhoneNumber' => $formattedPhone,
                        'CallBackURL' => $callbackUrl,
                        'AccountReference' => 'FEES-' . $studentId,
                        'TransactionDesc' => 'School Fee Payment',
                    ]
                );

            $responseData = $response->json() ?? [];

            /*
             * Log safe diagnostic information.
             *
             * NEVER log Consumer Secret or Passkey.
             */
            Log::info('Daraja STK Push response.', [
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'phone' => $formattedPhone,
                'amount' => $amount,
                'http_status' => $response->status(),
                'response_code' => $responseData['ResponseCode'] ?? null,
                'response_description' => $responseData['ResponseDescription'] ?? null,
                'merchant_request_id' => $responseData['MerchantRequestID'] ?? null,
                'checkout_request_id' => $responseData['CheckoutRequestID'] ?? null,
            ]);

            /*
             * HTTP/API failure.
             */
            if (!$response->successful()) {
                $errorMessage = $responseData['errorMessage']
                    ?? $responseData['errorCode']
                    ?? $responseData['ResponseDescription']
                    ?? 'M-Pesa STK Push request failed.';

                $transaction->update([
                    'status' => 'failed',
                    'result_code' => (string) ($responseData['ResponseCode'] ?? $response->status()),
                    'result_desc' => $errorMessage,
                ]);

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'transaction' => $transaction,
                ];
            }

            /*
             * Daraja may return HTTP 200 while ResponseCode indicates failure.
             */
            $responseCode = (string) ($responseData['ResponseCode'] ?? '');

            if ($responseCode !== '' && $responseCode !== '0') {
                $errorMessage = $responseData['ResponseDescription']
                    ?? $responseData['CustomerMessage']
                    ?? 'M-Pesa STK Push was not accepted.';

                $transaction->update([
                    'status' => 'failed',
                    'result_code' => $responseCode,
                    'result_desc' => $errorMessage,
                ]);

                return [
                    'success' => false,
                    'message' => $errorMessage,
                    'transaction' => $transaction,
                ];
            }

            /*
             * Safaricom accepted the STK request.
             *
             * IMPORTANT:
             * This is NOT a completed payment.
             */
            $realMerchantRequestId = $responseData['MerchantRequestID']
                ?? $merchantRequestId;

            $realCheckoutRequestId = $responseData['CheckoutRequestID']
                ?? null;

            if (!$realCheckoutRequestId) {
                $transaction->update([
                    'status' => 'failed',
                    'result_code' => $responseCode ?: 'NO_CHECKOUT_ID',
                    'result_desc' => 'Safaricom accepted the request but did not return a CheckoutRequestID.',
                ]);

                return [
                    'success' => false,
                    'message' => 'M-Pesa did not return a valid CheckoutRequestID.',
                    'transaction' => $transaction,
                ];
            }

            $transaction->update([
                'merchant_request_id' => $realMerchantRequestId,
                'checkout_request_id' => $realCheckoutRequestId,
                'status' => 'initiated',
                'result_code' => $responseCode ?: '0',
                'result_desc' => $responseData['ResponseDescription']
                    ?? 'STK Push sent successfully. Waiting for customer response.',
            ]);

            return [
                'success' => true,
                'message' => 'STK Push sent successfully to ' . $formattedPhone . '. Please check the phone and enter the M-Pesa PIN.',
                'transaction' => $transaction->fresh(),
                'checkout_request_id' => $realCheckoutRequestId,
                'merchant_request_id' => $realMerchantRequestId,
            ];
        } catch (\Throwable $e) {
            /*
             * Never simulate a successful payment here.
             */
            Log::error('Daraja STK Push exception.', [
                'school_id' => $schoolId,
                'student_id' => $studentId,
                'phone' => $formattedPhone,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $transaction->update([
                'status' => 'failed',
                'result_code' => 'EXCEPTION',
                'result_desc' => 'M-Pesa connection error: ' . Str::limit($e->getMessage(), 250),
            ]);

            return [
                'success' => false,
                'message' => 'Unable to send the M-Pesa STK Push. Please check your M-Pesa configuration and try again.',
                'transaction' => $transaction,
            ];
        }
    }

    /**
     * Process a Safaricom STK callback.
     *
     * This method can be called by FinanceController::mpesaCallback().
     *
     * IMPORTANT:
     * Only ResultCode = 0 is treated as a successful payment.
     */
    public function processStkCallback(array $callback): array
    {
        $body = data_get($callback, 'Body.stkCallback', []);

        if (!$body) {
            Log::warning('M-Pesa callback received without stkCallback body.');

            return [
                'success' => false,
                'message' => 'Invalid M-Pesa callback payload.',
            ];
        }

        $resultCode = (int) data_get($body, 'ResultCode', 1);
        $resultDesc = (string) data_get(
            $body,
            'ResultDesc',
            'Unknown M-Pesa transaction result.'
        );

        $checkoutRequestId = data_get($body, 'CheckoutRequestID');
        $merchantRequestId = data_get($body, 'MerchantRequestID');

        if (!$checkoutRequestId) {
            Log::warning('M-Pesa callback missing CheckoutRequestID.', [
                'merchant_request_id' => $merchantRequestId,
                'result_code' => $resultCode,
            ]);

            return [
                'success' => false,
                'message' => 'M-Pesa callback did not contain CheckoutRequestID.',
            ];
        }

        /*
         * Find the exact STK transaction.
         */
        $transaction = MpesaTransaction::where(
            'checkout_request_id',
            $checkoutRequestId
        )->first();

        if (!$transaction) {
            Log::warning('M-Pesa callback transaction not found.', [
                'checkout_request_id' => $checkoutRequestId,
                'merchant_request_id' => $merchantRequestId,
                'result_code' => $resultCode,
            ]);

            /*
             * Still return success to Safaricom so it does not keep retrying.
             */
            return [
                'success' => false,
                'message' => 'Transaction not found.',
                'checkout_request_id' => $checkoutRequestId,
            ];
        }

        /*
         * Handle duplicate callback.
         */
        if (
            $transaction->status === 'completed'
            && $transaction->mpesa_receipt_number
        ) {
            Log::info('Duplicate M-Pesa callback ignored.', [
                'transaction_id' => $transaction->id,
                'checkout_request_id' => $checkoutRequestId,
                'receipt' => $transaction->mpesa_receipt_number,
            ]);

            return [
                'success' => true,
                'message' => 'Callback already processed.',
                'transaction' => $transaction,
            ];
        }

        /*
         * Failed / cancelled / timed-out transaction.
         */
        if ($resultCode !== 0) {
            $transaction->update([
                'result_code' => (string) $resultCode,
                'result_desc' => $resultDesc,
                'status' => 'failed',
                'transaction_date' => now(),
            ]);

            Log::info('M-Pesa STK transaction failed or was cancelled.', [
                'transaction_id' => $transaction->id,
                'checkout_request_id' => $checkoutRequestId,
                'result_code' => $resultCode,
                'result_desc' => $resultDesc,
            ]);

            return [
                'success' => false,
                'message' => $resultDesc,
                'transaction' => $transaction,
            ];
        }

        /*
         * Extract CallbackMetadata safely.
         */
        $items = collect(
            data_get($body, 'CallbackMetadata.Item', [])
        );

        $amountItem = $items->firstWhere('Name', 'Amount');
        $receiptItem = $items->firstWhere('Name', 'MpesaReceiptNumber');
        $phoneItem = $items->firstWhere('Name', 'PhoneNumber');
        $transactionDateItem = $items->firstWhere('Name', 'TransactionDate');

        $amount = (float) data_get(
            $amountItem,
            'Value',
            $transaction->amount
        );

        $mpesaReceipt = data_get(
            $receiptItem,
            'Value'
        );

        $phone = data_get(
            $phoneItem,
            'Value',
            $transaction->phone_number
        );

        /*
         * Never mark a successful payment without a real M-Pesa receipt.
         */
        if (!$mpesaReceipt) {
            Log::error('Successful M-Pesa callback has no receipt number.', [
                'transaction_id' => $transaction->id,
                'checkout_request_id' => $checkoutRequestId,
                'result_code' => $resultCode,
            ]);

            $transaction->update([
                'result_code' => (string) $resultCode,
                'result_desc' => 'Successful callback received but M-Pesa receipt number was missing.',
                'status' => 'failed',
            ]);

            return [
                'success' => false,
                'message' => 'M-Pesa receipt number was missing from the callback.',
                'transaction' => $transaction,
            ];
        }

        $mpesaReceipt = strtoupper(trim((string) $mpesaReceipt));

        /*
         * Idempotency check using the M-Pesa receipt number.
         */
        $existingPayment = FeePayment::where(
            'reference_code',
            $mpesaReceipt
        )->first();

        if ($existingPayment) {
            $transaction->update([
                'mpesa_receipt_number' => $mpesaReceipt,
                'result_code' => (string) $resultCode,
                'result_desc' => $resultDesc,
                'status' => 'completed',
                'transaction_date' => now(),
            ]);

            Log::warning('M-Pesa receipt already exists as a fee payment.', [
                'transaction_id' => $transaction->id,
                'receipt' => $mpesaReceipt,
                'existing_payment_id' => $existingPayment->id,
            ]);

            return [
                'success' => true,
                'message' => 'M-Pesa payment was already recorded.',
                'transaction' => $transaction->fresh(),
                'payment' => $existingPayment,
            ];
        }

        /*
         * Complete the transaction and create the fee payment atomically.
         */
        return DB::transaction(function () use (
            $transaction,
            $resultCode,
            $resultDesc,
            $mpesaReceipt,
            $amount,
            $phone
        ) {
            /*
             * Lock transaction to prevent two callbacks from settling it
             * simultaneously.
             */
            $lockedTransaction = MpesaTransaction::where(
                'id',
                $transaction->id
            )
                ->lockForUpdate()
                ->first();

            if (!$lockedTransaction) {
                return [
                    'success' => false,
                    'message' => 'M-Pesa transaction could not be locked.',
                ];
            }

            /*
             * Check again after acquiring the lock.
             */
            if (
                $lockedTransaction->status === 'completed'
                && $lockedTransaction->mpesa_receipt_number
            ) {
                return [
                    'success' => true,
                    'message' => 'M-Pesa callback was already processed.',
                    'transaction' => $lockedTransaction,
                ];
            }

            /*
             * Verify the student exists within the same school.
             */
            $student = Student::where('id', $lockedTransaction->student_id)
                ->where('school_id', $lockedTransaction->school_id)
                ->first();

            if (!$student) {
                Log::error('M-Pesa callback student not found.', [
                    'transaction_id' => $lockedTransaction->id,
                    'student_id' => $lockedTransaction->student_id,
                    'school_id' => $lockedTransaction->school_id,
                ]);

                $lockedTransaction->update([
                    'result_code' => (string) $resultCode,
                    'result_desc' => 'Student linked to M-Pesa transaction was not found.',
                    'status' => 'failed',
                ]);

                return [
                    'success' => false,
                    'message' => 'Student linked to the M-Pesa transaction was not found.',
                ];
            }

            /*
             * Update M-Pesa transaction.
             */
            $lockedTransaction->update([
                'mpesa_receipt_number' => $mpesaReceipt,
                'result_code' => (string) $resultCode,
                'result_desc' => $resultDesc,
                'status' => 'completed',
                'transaction_date' => now(),
                'phone_number' => $this->formatPhoneNumber((string) $phone),
            ]);

            /*
             * Settle into the existing finance system.
             *
             * No fake receipt is generated here.
             * The real Safaricom receipt is used as reference_code.
             */
            $settlement = $this->settleStudentPayment(
                schoolId: (int) $lockedTransaction->school_id,
                studentId: (int) $lockedTransaction->student_id,
                amount: $amount > 0
                    ? $amount
                    : (float) $lockedTransaction->amount,
                method: 'mpesa_stk',
                referenceCode: $mpesaReceipt,
                notes: 'M-Pesa STK Push payment confirmed by Safaricom. Phone: ' . $phone,
                paymentSource: 'mpesa_callback',
                invoiceId: $lockedTransaction->invoice_id
            );

            Log::info('M-Pesa STK payment successfully settled.', [
                'transaction_id' => $lockedTransaction->id,
                'student_id' => $lockedTransaction->student_id,
                'school_id' => $lockedTransaction->school_id,
                'amount' => $amount,
                'receipt' => $mpesaReceipt,
            ]);

            return [
                'success' => true,
                'message' => 'M-Pesa payment confirmed and student account updated.',
                'transaction' => $lockedTransaction->fresh(),
                'settlement' => $settlement,
                'mpesa_receipt' => $mpesaReceipt,
            ];
        });
    }

    /**
     * Simulate successful STK Push.
     *
     * Kept for backwards compatibility with existing code.
     *
     * IMPORTANT:
     * This method is intentionally disabled.
     * The system must never create fake successful payments.
     */
    public function simulateStkSuccess(
        MpesaTransaction $transaction,
        ?int $invoiceId = null,
        string $source = 'bursar'
    ): array {
        Log::warning('Attempted M-Pesa simulation blocked.', [
            'transaction_id' => $transaction->id,
            'school_id' => $transaction->school_id,
            'student_id' => $transaction->student_id,
        ]);

        return [
            'success' => false,
            'message' => 'M-Pesa simulation is disabled. A payment can only be completed after confirmation from Safaricom.',
            'transaction' => $transaction,
        ];
    }

    /**
     * Process an incoming M-Pesa Paybill payment (C2B).
     */
    public function processPaybillPayment(
        int $schoolId,
        int $studentId,
        float $amount,
        string $mpesaCode,
        string $phone,
        ?int $invoiceId = null,
        string $source = 'bursar'
    ): array {
        $mpesaCode = strtoupper(trim($mpesaCode));

        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Payment amount must be greater than zero.',
            ];
        }

        if ($mpesaCode === '') {
            return [
                'success' => false,
                'message' => 'M-Pesa receipt number is required.',
            ];
        }

        /*
         * Prevent duplicate receipt recording.
         */
        $existingPayment = FeePayment::where(
            'reference_code',
            $mpesaCode
        )->first();

        if ($existingPayment) {
            return [
                'success' => false,
                'message' => 'This M-Pesa receipt has already been recorded.',
                'payment' => $existingPayment,
            ];
        }

        $student = Student::where('id', $studentId)
            ->where('school_id', $schoolId)
            ->first();

        if (!$student) {
            return [
                'success' => false,
                'message' => 'Student was not found in this school.',
            ];
        }

        if ($invoiceId) {
            $invoice = StudentInvoice::where('id', $invoiceId)
                ->where('student_id', $studentId)
                ->where('school_id', $schoolId)
                ->first();

            if (!$invoice) {
                return [
                    'success' => false,
                    'message' => 'The selected invoice does not belong to this student.',
                ];
            }
        }

        $transaction = MpesaTransaction::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'invoice_id' => $invoiceId,
            'mpesa_type' => 'paybill',
            'merchant_request_id' => 'PB-' . strtoupper(Str::random(8)),
            'checkout_request_id' => 'PB_CO_' . strtoupper(Str::random(12)),
            'phone_number' => $this->formatPhoneNumber($phone),
            'amount' => $amount,
            'mpesa_receipt_number' => $mpesaCode,
            'result_code' => '0',
            'result_desc' => 'Paybill payment recorded successfully.',
            'status' => 'completed',
            'transaction_date' => now(),
        ]);

        return $this->settleStudentPayment(
            schoolId: $schoolId,
            studentId: $studentId,
            amount: $amount,
            method: 'mpesa_paybill',
            referenceCode: $mpesaCode,
            notes: 'M-Pesa Paybill payment received from ' . $phone,
            paymentSource: $source,
            invoiceId: $invoiceId
        );
    }

    /**
     * Process an incoming M-Pesa Till / Buy Goods payment.
     */
    public function processTillPayment(
        int $schoolId,
        int $studentId,
        float $amount,
        string $mpesaCode,
        string $phone,
        ?int $invoiceId = null,
        string $source = 'bursar'
    ): array {
        $mpesaCode = strtoupper(trim($mpesaCode));

        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Payment amount must be greater than zero.',
            ];
        }

        if ($mpesaCode === '') {
            return [
                'success' => false,
                'message' => 'M-Pesa receipt number is required.',
            ];
        }

        /*
         * Prevent duplicate receipt recording.
         */
        $existingPayment = FeePayment::where(
            'reference_code',
            $mpesaCode
        )->first();

        if ($existingPayment) {
            return [
                'success' => false,
                'message' => 'This M-Pesa receipt has already been recorded.',
                'payment' => $existingPayment,
            ];
        }

        $student = Student::where('id', $studentId)
            ->where('school_id', $schoolId)
            ->first();

        if (!$student) {
            return [
                'success' => false,
                'message' => 'Student was not found in this school.',
            ];
        }

        if ($invoiceId) {
            $invoice = StudentInvoice::where('id', $invoiceId)
                ->where('student_id', $studentId)
                ->where('school_id', $schoolId)
                ->first();

            if (!$invoice) {
                return [
                    'success' => false,
                    'message' => 'The selected invoice does not belong to this student.',
                ];
            }
        }

        $transaction = MpesaTransaction::create([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'invoice_id' => $invoiceId,
            'mpesa_type' => 'till',
            'merchant_request_id' => 'TILL-' . strtoupper(Str::random(8)),
            'checkout_request_id' => 'TILL_CO_' . strtoupper(Str::random(12)),
            'phone_number' => $this->formatPhoneNumber($phone),
            'amount' => $amount,
            'mpesa_receipt_number' => $mpesaCode,
            'result_code' => '0',
            'result_desc' => 'Buy Goods Till payment recorded successfully.',
            'status' => 'completed',
            'transaction_date' => now(),
        ]);

        return $this->settleStudentPayment(
            schoolId: $schoolId,
            studentId: $studentId,
            amount: $amount,
            method: 'mpesa_till',
            referenceCode: $mpesaCode,
            notes: 'M-Pesa Buy Goods Till payment received from ' . $phone,
            paymentSource: $source,
            invoiceId: $invoiceId
        );
    }

    /**
     * Settle student payment, create FeePayment and reconcile invoices.
     */
    public function settleStudentPayment(
        int $schoolId,
        int $studentId,
        float $amount,
        string $method,
        string $referenceCode,
        string $notes,
        string $paymentSource = 'bursar',
        ?int $invoiceId = null,
        ?int $bankAccountId = null,
        ?int $receivedBy = null
    ): array {
        return DB::transaction(function () use (
            $schoolId,
            $studentId,
            $amount,
            $method,
            $referenceCode,
            $notes,
            $paymentSource,
            $invoiceId,
            $bankAccountId,
            $receivedBy
        ) {
            /*
             * Validate student.
             */
            $student = Student::where('id', $studentId)
                ->where('school_id', $schoolId)
                ->first();

            if (!$student) {
                throw new \RuntimeException(
                    'Student does not belong to the selected school.'
                );
            }

            /*
             * Normalize reference.
             */
            $referenceCode = strtoupper(trim($referenceCode));

            /*
             * Prevent duplicate payment references.
             */
            $existingPayment = FeePayment::where(
                'reference_code',
                $referenceCode
            )->first();

            if ($existingPayment) {
                return [
                    'success' => true,
                    'message' => 'Payment was already recorded.',
                    'receipt_number' => $existingPayment->receipt_number,
                    'payment_id' => $existingPayment->id,
                    'payment' => $existingPayment,
                    'duplicate' => true,
                ];
            }

            $studentInvoices = StudentInvoice::where('school_id', $schoolId)
                ->where('student_id', $studentId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $hasInvoices = $studentInvoices->isNotEmpty();
            $balanceBefore = $hasInvoices
                ? (float) $studentInvoices->sum('balance')
                : null;

            /*
             * Select preferred invoice.
             */
            $targetInvoice = null;

            if ($invoiceId) {
                $targetInvoice = StudentInvoice::where('id', $invoiceId)
                    ->where('school_id', $schoolId)
                    ->where('student_id', $studentId)
                    ->lockForUpdate()
                    ->first();
            }

            /*
             * If no invoice was supplied, allocate to oldest outstanding
             * invoice.
             */
            if (!$targetInvoice) {
                $targetInvoice = StudentInvoice::where(
                    'student_id',
                    $studentId
                )
                    ->where('school_id', $schoolId)
                    ->where('balance', '>', 0)
                    ->orderBy('created_at')
                    ->lockForUpdate()
                    ->first();
            }

            /*
             * Generate internal CloudKazi receipt.
             *
             * This is different from the M-Pesa receipt/reference.
             */
            $receiptNumber = $this->generateUniqueReceiptNumber();

            /*
             * Create FeePayment.
             */
            $payment = FeePayment::create([
                'school_id' => $schoolId,
                'receipt_number' => $receiptNumber,
                'student_invoice_id' => $targetInvoice?->id,
                'student_id' => $studentId,
                'amount' => $amount,
                'payment_method' => $method,
                'reference_code' => $referenceCode,
                'payment_date' => now()->toDateString(),
                'received_by' => $receivedBy,
                'notes' => $notes,
                'payment_source' => $paymentSource,
                'bank_account_id' => $bankAccountId,
            ]);

            /*
             * Automatically distribute payment across outstanding invoices.
             */
            $this->reconcileInvoicesForStudent(
                schoolId: $schoolId,
                studentId: $studentId,
                amountToAllocate: $amount,
                preferredInvoice: $targetInvoice
            );

            $balanceAfter = $hasInvoices
                ? (float) StudentInvoice::where('school_id', $schoolId)
                    ->where('student_id', $studentId)
                    ->sum('balance')
                : null;
            $payment->update([
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
            ]);

            return [
                'success' => true,
                'message' => 'Payment of KES ' .
                    number_format($amount, 2) .
                    ' processed successfully. Receipt: ' .
                    $receiptNumber,
                'receipt_number' => $receiptNumber,
                'payment_id' => $payment->id,
                'mpesa_receipt' => $referenceCode,
                'payment' => $payment,
                'invoice_id' => $targetInvoice?->id,
            ];
        });
    }

    /**
     * Automatically reconcile payments across student invoices.
     */
    public function reconcileInvoicesForStudent(
        int $schoolId,
        int $studentId,
        float $amountToAllocate,
        ?StudentInvoice $preferredInvoice = null
    ): void {
        $remaining = max(0, $amountToAllocate);

        /*
         * Preferred invoice first.
         */
        if (
            $preferredInvoice
            && (float) $preferredInvoice->balance > 0
            && $remaining > 0
        ) {
            $allocated = min(
                $remaining,
                (float) $preferredInvoice->balance
            );

            $newPaid = (float) $preferredInvoice->paid_amount + $allocated;

            $newBalance = max(
                0,
                (float) $preferredInvoice->total_amount
                - $newPaid
                - (float) $preferredInvoice->waiver_amount
            );

            $preferredInvoice->update([
                'paid_amount' => $newPaid,
                'balance' => $newBalance,
                'status' => $newBalance <= 0
                    ? 'paid'
                    : 'partially_paid',
            ]);

            $remaining -= $allocated;
        }

        /*
         * Allocate any remaining amount to other invoices,
         * oldest first.
         */
        if ($remaining > 0) {
            $otherInvoices = StudentInvoice::where(
                'school_id',
                $schoolId
            )
                ->where('student_id', $studentId)
                ->when(
                    $preferredInvoice,
                    fn ($q) => $q->where(
                        'id',
                        '!=',
                        $preferredInvoice->id
                    )
                )
                ->where('balance', '>', 0)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->get();

            foreach ($otherInvoices as $invoice) {
                if ($remaining <= 0) {
                    break;
                }

                $allocated = min(
                    $remaining,
                    (float) $invoice->balance
                );

                $newPaid = (float) $invoice->paid_amount + $allocated;

                $newBalance = max(
                    0,
                    (float) $invoice->total_amount
                    - $newPaid
                    - (float) $invoice->waiver_amount
                );

                $invoice->update([
                    'paid_amount' => $newPaid,
                    'balance' => $newBalance,
                    'status' => $newBalance <= 0
                        ? 'paid'
                        : 'partially_paid',
                ]);

                $remaining -= $allocated;
            }
        }
    }

    /**
     * Normalize a Kenyan phone number to 254XXXXXXXXX.
     */
    public function formatPhoneNumber(string $phone): string
    {
        $phone = trim($phone);

        /*
         * Remove spaces, brackets, dashes and other characters.
         */
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        /*
         * +2547XXXXXXXX -> 2547XXXXXXXX
         */
        if (str_starts_with($phone, '+254')) {
            return substr($phone, 1);
        }

        /*
         * 07XXXXXXXX -> 2547XXXXXXXX
         */
        if (str_starts_with($phone, '0')) {
            return '254' . substr($phone, 1);
        }

        /*
         * 2547XXXXXXXX -> unchanged
         */
        if (str_starts_with($phone, '254')) {
            return $phone;
        }

        /*
         * 7XXXXXXXX / 1XXXXXXXX -> 2547XXXXXXXX / 2541XXXXXXXX
         */
        $digitsOnly = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($digitsOnly) === 9) {
            return '254' . $digitsOnly;
        }

        return $digitsOnly;
    }

    /**
     * Validate Kenyan Safaricom/Airtel/Telkom style mobile number.
     */
    private function isValidKenyanPhone(string $phone): bool
    {
        return (bool) preg_match(
            '/^254[17][0-9]{8}$/',
            $phone
        );
    }

    /**
     * Get Daraja OAuth access token.
     */
    private function getDarajaAccessToken(
        SchoolSetting $settings
    ): ?string {
        $baseUrl = rtrim(
            (string) $settings->getDarajaBaseUrl(),
            '/'
        );

        $consumerKey = trim(
            (string) ($settings->daraja_consumer_key ?? '')
        );

        $consumerSecret = trim(
            (string) ($settings->daraja_consumer_secret ?? '')
        );

        if ($consumerKey === '' || $consumerSecret === '') {
            return null;
        }

        $credentials = base64_encode(
            $consumerKey . ':' . $consumerSecret
        );

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'Authorization' => 'Basic ' . $credentials,
                ])
                ->timeout(15)
                ->get(
                    $baseUrl .
                    '/oauth/v1/generate?grant_type=client_credentials'
                );

            if (!$response->successful()) {
                Log::error('Daraja OAuth request failed.', [
                    'http_status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json() ?? [];

            if (empty($data['access_token'])) {
                Log::error('Daraja OAuth response did not contain access token.', [
                    'response' => $data,
                ]);

                return null;
            }

            return $data['access_token'];
        } catch (\Throwable $e) {
            Log::error('Daraja OAuth exception.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generate a unique internal CloudKazi receipt.
     */
    private function generateUniqueReceiptNumber(): string
    {
        do {
            $receipt = 'REC-' . strtoupper(Str::random(8));
        } while (
            FeePayment::where(
                'receipt_number',
                $receipt
            )->exists()
        );

        return $receipt;
    }

    /**
     * Determine Daraja environment for logging.
     */
    private function getDarajaEnvironment(
        SchoolSetting $settings
    ): string {
        return (string) (
            $settings->daraja_environment
            ?? 'sandbox'
        );
    }

    /**
     * Determine whether callback URL points to a local machine.
     */
    private function isLocalCallbackUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!$host) {
            return true;
        }

        $host = strtolower($host);

        return in_array($host, [
            'localhost',
            '127.0.0.1',
            '0.0.0.0',
            '::1',
        ], true)
        || str_ends_with($host, '.local');
    }

    // ─── SAAS SUBSCRIPTION STK PUSH ─────────────────────────────────────────────
    /**
     * Initiate M-Pesa STK Push for a SaaS SUBSCRIPTION payment.
     *
     * This is COMPLETELY SEPARATE from student fee payments.
     * Does NOT create a student_id reference.
     * Does NOT update student invoices.
     * Uses CloudKazi platform Daraja credentials (from .env), not school credentials.
     *
     * Completion is handled by processSubscriptionCallback(), not the student mpesaCallback.
     */
    public function initiateSubscriptionStkPush(
        int $schoolId,
        string $phone,
        float $amount,
        string $reference
    ): array {
        $formattedPhone = $this->formatPhoneNumber($phone);

        if (!$this->isValidKenyanPhone($formattedPhone)) {
            throw new \InvalidArgumentException('Invalid Kenyan M-Pesa phone number.');
        }

        // Use platform-level Daraja credentials from .env
        $consumerKey    = config('services.mpesa.consumer_key', '');
        $consumerSecret = config('services.mpesa.consumer_secret', '');
        $passkey        = config('services.mpesa.passkey', '');
        $shortcode      = config('services.mpesa.shortcode', '174379');
        $environment    = config('services.mpesa.environment', 'sandbox');
        $callbackUrl    = config('services.mpesa.subscription_callback_url', url('/mpesa/subscription-callback'));

        $baseUrl = $environment === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        if (empty($consumerKey) || empty($consumerSecret)) {
            throw new \RuntimeException('SaaS M-Pesa credentials not configured. Set MPESA_CONSUMER_KEY and MPESA_CONSUMER_SECRET in .env');
        }

        // Get OAuth token
        $tokenResponse = Http::withBasicAuth($consumerKey, $consumerSecret)
            ->timeout(15)
            ->get("{$baseUrl}/oauth/v1/generate?grant_type=client_credentials");

        if (!$tokenResponse->successful()) {
            throw new \RuntimeException('Failed to get SaaS M-Pesa OAuth token.');
        }

        $accessToken = $tokenResponse->json('access_token');
        $timestamp   = now()->format('YmdHis');
        $password    = base64_encode($shortcode . $passkey . $timestamp);

        $stkResponse = Http::withToken($accessToken)
            ->timeout(30)
            ->post("{$baseUrl}/mpesa/stkpush/v1/processrequest", [
                'BusinessShortCode' => $shortcode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'TransactionType'   => 'CustomerPayBillOnline',
                'Amount'            => (int) ceil($amount),
                'PartyA'            => $formattedPhone,
                'PartyB'            => $shortcode,
                'PhoneNumber'       => $formattedPhone,
                'CallBackURL'       => $callbackUrl,
                'AccountReference'  => 'CLOUDKAZI-SUB',
                'TransactionDesc'   => "CloudKazi Subscription - School #{$schoolId}",
            ]);

        if (!$stkResponse->successful()) {
            throw new \RuntimeException('SaaS M-Pesa STK Push failed: ' . ($stkResponse->json('errorMessage') ?? $stkResponse->body()));
        }

        return $stkResponse->json();
    }

    /**
     * Process SaaS subscription M-Pesa callback.
     * SEPARATE from student fee mpesaCallback.
     * Only activates subscription after verified receipt.
     */
    public function processSubscriptionCallback(array $callbackData): void
    {
        $body      = $callbackData['Body']['stkCallback'] ?? [];
        $resultCode = $body['ResultCode'] ?? -1;
        $checkoutId = $body['CheckoutRequestID'] ?? null;

        if (!$checkoutId) {
            Log::warning('SaaS subscription callback missing CheckoutRequestID');
            return;
        }

        $payment = \App\Models\SubscriptionPayment::where('mpesa_checkout_request_id', $checkoutId)->first();
        if (!$payment) {
            Log::warning("SaaS subscription payment not found for CheckoutRequestID: {$checkoutId}");
            return;
        }

        if ($resultCode !== 0) {
            $payment->update([
                'status'         => 'failed',
                'failure_reason' => $body['ResultDesc'] ?? 'M-Pesa STK failed',
            ]);
            return;
        }

        // Extract receipt from callback metadata
        $items   = collect($body['CallbackMetadata']['Item'] ?? []);
        $receipt = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;

        if (!$receipt) {
            $payment->update(['status' => 'failed', 'failure_reason' => 'No M-Pesa receipt in callback']);
            return;
        }

        // Check duplicate receipts
        if (\App\Models\SubscriptionPayment::where('mpesa_receipt', $receipt)->where('id', '!=', $payment->id)->exists()) {
            Log::warning("Duplicate SaaS M-Pesa receipt: {$receipt}");
            return;
        }

        $payment->update([
            'status'        => 'successful',
            'mpesa_receipt' => $receipt,
            'paid_at'       => now(),
        ]);

        // Only activate subscription after confirmed receipt
        $subscription = $payment->subscription;
        if ($subscription) {
            \App\Services\SchoolSubscriptionService::activateSubscription($subscription);
        }

        Log::info("SaaS subscription payment activated. Receipt: {$receipt}, School: {$payment->school_id}");
    }
}