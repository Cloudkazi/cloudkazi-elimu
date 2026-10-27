<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stream extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function classTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'class_teacher_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teacherSubjectAllocations(): HasMany
    {
        return $this->hasMany(TeacherSubjectAllocation::class);
    }

    public function timetableAllocations(): HasMany
    {
        return $this->hasMany(TimetableAllocation::class);
    }

    public function subjectOfferings(): HasMany
    {
        return $this->hasMany(SubjectOffering::class);
    }

    public function getFullNameAttribute(): string
    {
        return ($this->schoolClass ? $this->schoolClass->name : '') . ' - ' . $this->name;
    }
}

