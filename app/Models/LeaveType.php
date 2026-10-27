<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'is_paid' => 'boolean',
        'requires_attachment' => 'boolean',
        'carries_forward' => 'boolean',
        'days_per_year' => 'integer',
        'max_carry_forward_days' => 'integer',
    ];

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveAllocations(): HasMany
    {
        return $this->hasMany(LeaveAllocation::class);
    }
}
