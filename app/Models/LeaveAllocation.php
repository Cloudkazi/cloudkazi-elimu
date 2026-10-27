<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveAllocation extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'year' => 'integer',
        'entitled_days' => 'integer',
        'carried_forward' => 'integer',
        'adjusted_days' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
