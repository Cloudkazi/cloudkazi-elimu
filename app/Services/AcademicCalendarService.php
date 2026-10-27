<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\SchoolSetting;
use Carbon\Carbon;

class AcademicCalendarService
{
    /**
     * Get the active timezone for the school
     */
    public static function getSchoolTimezone(?int $schoolId): string
    {
        if (!$schoolId) {
            return config('app.timezone', 'Africa/Nairobi');
        }

        $setting = SchoolSetting::where('school_id', $schoolId)->first();
        return $setting?->timezone ?: config('app.timezone', 'Africa/Nairobi');
    }

    /**
     * Determine the current Academic Year for a school
     * Priority:
     * 1. AcademicYear where current date falls within [start_date, end_date] (prefer is_current = true if multiple)
     * 2. AcademicYear marked is_current = true
     * 3. Most recent AcademicYear by start_date / id
     */
    public static function getCurrentAcademicYear(?int $schoolId): ?AcademicYear
    {
        if (!$schoolId) {
            return null;
        }

        $tz = self::getSchoolTimezone($schoolId);
        $today = Carbon::now($tz)->toDateString();

        // 1. Current date is inside range
        $matchingYears = AcademicYear::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get();

        if ($matchingYears->isNotEmpty()) {
            return $matchingYears->first();
        }

        // 2. is_current flag
        $currentYear = AcademicYear::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('is_current', true)
            ->orderByDesc('start_date')
            ->first();

        if ($currentYear) {
            return $currentYear;
        }

        // 3. Fallback: latest
        return AcademicYear::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Determine the current Academic Term for a school
     * Priority:
     * 1. Term within the current academic year where current date falls within [start_date, end_date]
     * 2. Term within the current academic year marked is_current = true
     * 3. First or latest term within the current academic year
     */
    public static function getCurrentAcademicTerm(?int $schoolId, ?AcademicYear $academicYear = null): ?AcademicTerm
    {
        if (!$schoolId) {
            return null;
        }

        $academicYear = $academicYear ?? self::getCurrentAcademicYear($schoolId);

        $tz = self::getSchoolTimezone($schoolId);
        $today = Carbon::now($tz)->toDateString();

        $query = AcademicTerm::withoutGlobalScopes()
            ->where('school_id', $schoolId);

        if ($academicYear) {
            $query->where('academic_year_id', $academicYear->id);
        }

        // 1. Current date is inside range
        $matchingTerms = (clone $query)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get();

        if ($matchingTerms->isNotEmpty()) {
            return $matchingTerms->first();
        }

        // 2. is_current flag
        $currentTerm = (clone $query)
            ->where('is_current', true)
            ->orderByDesc('start_date')
            ->first();

        if ($currentTerm) {
            return $currentTerm;
        }

        // 3. Fallback: first term by start date or id
        return (clone $query)
            ->orderBy('start_date')
            ->orderBy('id')
            ->first();
    }

    /**
     * Get formatted strings and objects for display
     */
    public static function getAcademicContext(?int $schoolId): array
    {
        if (!$schoolId) {
            return [
                'year' => null,
                'term' => null,
                'year_name' => null,
                'term_name' => null,
            ];
        }

        $year = self::getCurrentAcademicYear($schoolId);
        $term = self::getCurrentAcademicTerm($schoolId, $year);

        return [
            'year' => $year,
            'term' => $term,
            'year_name' => $year?->name ?? 'Not Configured',
            'term_name' => $term?->name ?? 'No Active Term',
        ];
    }
}
