<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRecord extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'paye' => 'decimal:2',
        'nssf_employee' => 'decimal:2',
        'nssf_employer' => 'decimal:2',
        'shif' => 'decimal:2',
        'housing_levy_employee' => 'decimal:2',
        'housing_levy_employer' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'deductions' => 'decimal:2',
        'taxable_income' => 'decimal:2',
        'personal_relief' => 'decimal:2',
        'insurance_relief' => 'decimal:2',
        'total_employer_contributions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime',
        'allowances_breakdown' => 'array',
        'deductions_breakdown' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float)$this->net_salary - (float)$this->amount_paid);
    }

    public function isFullyPaid(): bool
    {
        return $this->payment_status === 'paid' || ($this->amount_paid >= $this->net_salary && (float)$this->net_salary > 0);
    }
}
