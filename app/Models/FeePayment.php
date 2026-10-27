<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    use BelongsToSchool;

    protected $table = 'fee_payments';

    protected $guarded = ['id'];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(SchoolBankAccount::class, 'bank_account_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Human-friendly payment method label
     */
    public function getMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'mpesa', 'mpesa_stk' => 'M-Pesa STK Push',
            'mpesa_paybill' => 'M-Pesa Paybill',
            'mpesa_till' => 'M-Pesa Till',
            'bank', 'bank_transfer' => 'Bank Transfer',
            'bank_deposit' => 'Bank Deposit',
            'cash' => 'Cash Counter',
            'cheque' => 'Bank Cheque',
            default => strtoupper((string) $this->payment_method),
        };
    }

    /**
     * UI Badge styling class for payment method
     */
    public function getMethodBadgeClassAttribute(): string
    {
        return match ($this->payment_method) {
            'mpesa', 'mpesa_stk', 'mpesa_paybill', 'mpesa_till' => 'badge-success',
            'bank', 'bank_transfer', 'bank_deposit' => 'badge-primary',
            'cash' => 'badge-secondary',
            'cheque' => 'badge-warning',
            default => 'badge-gray',
        };
    }

    /**
     * UI Icon for payment method
     */
    public function getMethodIconAttribute(): string
    {
        return match ($this->payment_method) {
            'mpesa', 'mpesa_stk', 'mpesa_paybill', 'mpesa_till' => 'bi-phone',
            'bank', 'bank_transfer', 'bank_deposit' => 'bi-bank',
            'cash' => 'bi-cash-coin',
            'cheque' => 'bi-file-earmark-text',
            default => 'bi-credit-card',
        };
    }
}
