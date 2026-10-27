<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolPopulationAllocation extends Model
{
    protected $guarded = ['id'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
