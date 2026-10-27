<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveAllocation;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveCalculationService
{
    /**
     * Recognized Kenyan fixed public holidays (Month-Day format)
     */
    protected static array $fixedHolidays = [
        '01-01', // New Year's Day
        '05-01', // Labour Day
        '06-01', // Madaraka Day
        '10-10', // Mazingira / Utamaduni Day
        '10-20', // Mashujaa Day
        '12-12', // Jamhuri Day
        '12-25', // Christmas Day
        '12-26', // Boxing Day
    ];

    /**
     * Calculate working days between two dates, excluding weekends and Kenyan public holidays.
     */
    public static function calculateWorkingDays(Carbon|string $startDate, Carbon|string $endDate): int
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        $period = CarbonPeriod::create($start, $end);
        $workingDays = 0;

        foreach ($period as $date) {
            // Check weekend (Saturday or Sunday)
            if ($date->isWeekend()) {
                continue;
            }

            // Check fixed public holiday
            if (in_array($date->format('m-d'), self::$fixedHolidays)) {
                continue;
            }

            // Easter-related movable holidays for the year
            $easterHolidays = self::getEasterHolidays($date->year);
            if (in_array($date->format('Y-m-d'), $easterHolidays)) {
                continue;
            }

            $workingDays++;
        }

        return $workingDays;
    }

    /**
     * Compute Good Friday and Easter Monday for a given year.
     */
    protected static function getEasterHolidays(int $year): array
    {
        $easterTimestamp = easter_date($year);
        $easter = Carbon::createFromTimestamp($easterTimestamp);

        $goodFriday = $easter->copy()->subDays(2)->format('Y-m-d');
        $easterMonday = $easter->copy()->addDay()->format('Y-m-d');

        return [$goodFriday, $easterMonday];
    }

    /**
     * Check if employee has overlapping approved or pending leave requests.
     */
    public static function hasOverlap(int $employeeId, Carbon|string $startDate, Carbon|string $endDate, ?int $ignoreRequestId = null): bool
    {
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();

        $query = LeaveRequest::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->where('start_date', '<=', $start)
                          ->where('end_date', '>=', $end);
                  });
            });

        if ($ignoreRequestId) {
            $query->where('id', '!=', $ignoreRequestId);
        }

        return $query->exists();
    }

    /**
     * Get leave balance breakdown for an employee and leave type for a given year.
     */
    public static function getBalance(Employee $employee, LeaveType $leaveType, ?int $year = null): array
    {
        $year = $year ?? (int)date('Y');

        // Check if an explicit allocation exists
        $allocation = LeaveAllocation::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        $entitled = $allocation ? (int)$allocation->entitled_days : (int)$leaveType->days_per_year;
        $carriedForward = $allocation ? (int)$allocation->carried_forward : 0;
        $adjusted = $allocation ? (int)$allocation->adjusted_days : 0;
        $totalAllotted = $entitled + $carriedForward + $adjusted;

        // Approved days taken this year
        $approvedTaken = (int)LeaveRequest::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days_requested');

        // Pending days requested this year
        $pendingRequested = (int)LeaveRequest::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'pending')
            ->whereYear('start_date', $year)
            ->sum('days_requested');

        $remaining = max(0, $totalAllotted - $approvedTaken);

        return [
            'leave_type_id' => $leaveType->id,
            'leave_type_name' => $leaveType->name,
            'year' => $year,
            'entitled_days' => $entitled,
            'carried_forward' => $carriedForward,
            'adjusted_days' => $adjusted,
            'total_allotted' => $totalAllotted,
            'approved_taken' => $approvedTaken,
            'pending_requested' => $pendingRequested,
            'remaining_balance' => $remaining,
        ];
    }
}
