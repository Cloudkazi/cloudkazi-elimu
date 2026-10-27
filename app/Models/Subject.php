<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    public function teacherSubjectAllocations(): HasMany
    {
        return $this->hasMany(TeacherSubjectAllocation::class);
    }

    public function timetableAllocations(): HasMany
    {
        return $this->hasMany(TimetableAllocation::class);
    }

    public function offerings(): HasMany
    {
        return $this->hasMany(SubjectOffering::class);
    }


    public function getSubjectNameAttribute(): string
    {
        return $this->name ?? '';
    }

    public function getMaxScoreAttribute(): int
    {
        return 100;
    }
}
