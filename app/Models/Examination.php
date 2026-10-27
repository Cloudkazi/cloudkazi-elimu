<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examination extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function marks844(): HasMany
    {
        return $this->hasMany(ExamMark844::class);
    }

    public function cbcAssessments(): HasMany
    {
        return $this->hasMany(CbcAssessment::class);
    }

    public function reportSummaries(): HasMany
    {
        return $this->hasMany(StudentReportSummary::class);
    }

    public function getExamNameAttribute(): string
    {
        return $this->name ?? '';
    }
}
