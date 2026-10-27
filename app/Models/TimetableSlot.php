<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimetableSlot extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'is_break' => 'boolean',
        'is_teaching_period' => 'boolean',
        'duration_minutes' => 'integer',
    ];

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(TimetableAllocation::class);
    }
}
