<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SchoolClass extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    public function streams(): HasMany
    {
        return $this->hasMany(Stream::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function teacherSubjectAllocations(): HasMany
    {
        return $this->hasMany(TeacherSubjectAllocation::class);
    }

    public function timetableAllocations(): HasManyThrough
    {
        return $this->hasManyThrough(TimetableAllocation::class, Stream::class);
    }

    public function subjectOfferings(): HasMany
    {
        return $this->hasMany(SubjectOffering::class);
    }


    public function getClassNameAttribute(): string
    {
        return $this->name ?? '';
    }

    public function getStreamNameAttribute(): ?string
    {
        return $this->streams()->first()?->name;
    }
}
