<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'hire_date' => 'date',
        'date_of_birth' => 'date',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'basic_salary' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrollRecords(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    public function payrollPayments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveAllocations(): HasMany
    {
        return $this->hasMany(LeaveAllocation::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOnLeave($query)
    {
        return $query->where('status', 'on_leave');
    }

    /**
     * Check if employee is currently on an approved leave on a given date.
     */
    public function isCurrentlyOnLeave(?Carbon $date = null): bool
    {
        $date = $date ?? Carbon::today();
        return $this->leaveRequests()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }
}
