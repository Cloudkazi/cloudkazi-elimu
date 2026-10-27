<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAvailability extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'available_days' => 'array',
        'available_periods' => 'array',
        'blocked_periods' => 'array',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
