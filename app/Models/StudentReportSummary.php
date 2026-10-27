<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentReportSummary extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    public function examination(): BelongsTo
    {
        return $this->belongsTo(Examination::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function getTotalMarksAttribute(): float
    {
        return (float) ($this->total_score ?? 0);
    }

    public function getAverageScoreAttribute(): float
    {
        return (float) ($this->mean_score ?? 0);
    }
}
