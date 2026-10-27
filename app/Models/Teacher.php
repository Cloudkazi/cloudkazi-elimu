<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(TeacherSubjectAllocation::class);
    }

    public function subjectAllocations(): HasMany
    {
        return $this->hasMany(TeacherSubjectAllocation::class);
    }

    public function timetableAllocations(): HasMany
    {
        return $this->hasMany(TimetableAllocation::class);
    }

    public function getNameAttribute(): string
    {
        return $this->user ? $this->user->name : 'Staff';
    }
}
