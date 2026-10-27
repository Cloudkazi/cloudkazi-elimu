<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Timetable extends Model
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

    public function slots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class)->orderBy('period_number');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(TimetableAllocation::class);
    }

    public function generationSetting(): HasOne
    {
        return $this->hasOne(TimetableGenerationSetting::class);
    }

    /**
     * Retrieve the active published timetable for a school,
     * scoped strictly by current academic year, current term, and published status.
     */
    public static function getActivePublished(int $schoolId): ?self
    {
        $currentYear = AcademicYear::where('school_id', $schoolId)->where('is_current', true)->first();
        $currentTerm = $currentYear
            ? AcademicTerm::where('school_id', $schoolId)->where('academic_year_id', $currentYear->id)->where('is_current', true)->first()
            : null;

        $baseQuery = self::where('school_id', $schoolId)->where('status', 'published');

        if ($currentYear && $currentTerm) {
            $timetable = (clone $baseQuery)
                ->where('academic_year_id', $currentYear->id)
                ->where('term_id', $currentTerm->id)
                ->latest('id')
                ->first();
            if ($timetable) {
                return $timetable;
            }
        }

        if ($currentYear) {
            $timetable = (clone $baseQuery)
                ->where('academic_year_id', $currentYear->id)
                ->latest('id')
                ->first();
            if ($timetable) {
                return $timetable;
            }
        }

        return $baseQuery->latest('id')->first();
    }
}
