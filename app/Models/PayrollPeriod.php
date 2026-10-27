<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'finalized_at' => 'datetime',
        'total_basic_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_gross' => 'decimal:2',
        'total_statutory_deductions' => 'decimal:2',
        'total_other_deductions' => 'decimal:2',
        'total_net_salary' => 'decimal:2',
        'total_employer_contributions' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'employee_count' => 'integer',
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function getUnpaidAmountAttribute(): float
    {
        return max(0, (float)$this->total_net_salary - (float)$this->total_paid);
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, ['finalized', 'paid']);
    }
}
