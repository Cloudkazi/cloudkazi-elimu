<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolPopulationAllocation;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;

class PopulationAllocationService
{
    /**
     * Cache TTL in seconds for limits
     */
    protected const CACHE_TTL = 300;

    /**
     * Check if a school can create a new entity of given resource type.
     * Supported resources: 'students', 'teachers', 'parents', 'admins'
     */
    public static function canCreate(int $schoolId, string $resource): bool
    {
        $limit = static::getLimit($schoolId, $resource);
        if ($limit === null) {
            return true; // Unlimited
        }

        $current = static::getUsage($schoolId, $resource);
        return $current < $limit;
    }

    public static function canCreateStudent(int $schoolId): bool
    {
        return static::canCreate($schoolId, 'students');
    }

    public static function canCreateTeacher(int $schoolId): bool
    {
        return static::canCreate($schoolId, 'teachers');
    }

    public static function canCreateParent(int $schoolId): bool
    {
        return static::canCreate($schoolId, 'parents');
    }

    public static function canCreateAdmin(int $schoolId): bool
    {
        return static::canCreate($schoolId, 'admins');
    }

    /**
     * Get effective limit for a resource.
     * Resolution: school custom allocation → subscription override → package limit → null (unlimited)
     */
    public static function getLimit(int $schoolId, string $resource): ?int
    {
        return match ($resource) {
            'students', 'student' => static::getStudentLimit($schoolId),
            'teachers', 'teacher' => static::getTeacherLimit($schoolId),
            'parents', 'parent'   => static::getParentLimit($schoolId),
            'admins', 'admin'     => static::getAdminLimit($schoolId),
            default               => null,
        };
    }

    /**
     * Get current usage count for a resource.
     */
    public static function getUsage(int $schoolId, string $resource): int
    {
        $school = School::find($schoolId);
        if (!$school) {
            return 0;
        }

        return match ($resource) {
            'students', 'student' => $school->students()->count(),
            'teachers', 'teacher' => $school->teachers()->count(),
            'parents', 'parent'   => $school->users()->whereHas('role', fn ($q) => $q->where('slug', Role::PARENT))->count(),
            'admins', 'admin'     => $school->users()->whereHas('role', fn ($q) => $q->whereIn('slug', [Role::SCHOOL_ADMIN, Role::PRINCIPAL]))->count(),
            default               => 0,
        };
    }

    /**
     * Get remaining capacity for a resource. Null if unlimited.
     */
    public static function getRemaining(int $schoolId, string $resource): ?int
    {
        $limit = static::getLimit($schoolId, $resource);
        if ($limit === null) {
            return null;
        }

        $current = static::getUsage($schoolId, $resource);
        return max(0, $limit - $current);
    }

    /**
     * Get effective student limit for a school.
     */
    public static function getStudentLimit(int $schoolId): ?int
    {
        $alloc = static::latestAllocation($schoolId);
        if ($alloc && $alloc->student_limit !== null) {
            return (int) $alloc->student_limit;
        }

        $sub = static::subscription($schoolId);
        if ($sub && $sub->student_limit !== null) {
            return (int) $sub->student_limit;
        }

        $pkgLimit = $sub?->package?->max_students;
        return $pkgLimit !== null ? (int) $pkgLimit : null;
    }

    public static function getTeacherLimit(int $schoolId): ?int
    {
        $alloc = static::latestAllocation($schoolId);
        if ($alloc && $alloc->teacher_limit !== null) {
            return (int) $alloc->teacher_limit;
        }

        $sub = static::subscription($schoolId);
        if ($sub && $sub->teacher_limit !== null) {
            return (int) $sub->teacher_limit;
        }

        $pkgLimit = $sub?->package?->max_teachers;
        return $pkgLimit !== null ? (int) $pkgLimit : null;
    }

    public static function getParentLimit(int $schoolId): ?int
    {
        $alloc = static::latestAllocation($schoolId);
        if ($alloc && $alloc->parent_limit !== null) {
            return (int) $alloc->parent_limit;
        }

        $sub = static::subscription($schoolId);
        if ($sub && $sub->parent_limit !== null) {
            return (int) $sub->parent_limit;
        }

        $pkgLimit = $sub?->package?->max_parents;
        return $pkgLimit !== null ? (int) $pkgLimit : null;
    }

    public static function getAdminLimit(int $schoolId): ?int
    {
        $alloc = static::latestAllocation($schoolId);
        if ($alloc && $alloc->admin_limit !== null) {
            return (int) $alloc->admin_limit;
        }

        $sub = static::subscription($schoolId);
        if ($sub && $sub->admin_limit !== null) {
            return (int) $sub->admin_limit;
        }

        $pkgLimit = $sub?->package?->max_admins;
        return $pkgLimit !== null ? (int) $pkgLimit : null;
    }

    /**
     * Get usage summary for a school (aggregate counts only — no individual records).
     */
    public static function getUsageSummary(School $school): array
    {
        $studentLimit   = static::getStudentLimit($school->id);
        $teacherLimit   = static::getTeacherLimit($school->id);
        $parentLimit    = static::getParentLimit($school->id);

        $studentCurrent = $school->students()->count();
        $teacherCurrent = $school->teachers()->count();
        $parentCurrent  = $school->users()
            ->whereHas('role', fn ($q) => $q->where('slug', Role::PARENT))
            ->count();

        return [
            'students' => [
                'licensed' => $studentLimit,
                'current'  => $studentCurrent,
                'remaining'=> $studentLimit !== null ? max(0, $studentLimit - $studentCurrent) : null,
                'percent'  => ($studentLimit && $studentLimit > 0) ? round($studentCurrent / $studentLimit * 100, 1) : null,
            ],
            'teachers' => [
                'licensed' => $teacherLimit,
                'current'  => $teacherCurrent,
                'remaining'=> $teacherLimit !== null ? max(0, $teacherLimit - $teacherCurrent) : null,
                'percent'  => ($teacherLimit && $teacherLimit > 0) ? round($teacherCurrent / $teacherLimit * 100, 1) : null,
            ],
            'parents' => [
                'licensed' => $parentLimit,
                'current'  => $parentCurrent,
                'remaining'=> $parentLimit !== null ? max(0, $parentLimit - $parentCurrent) : null,
                'percent'  => ($parentLimit && $parentLimit > 0) ? round($parentCurrent / $parentLimit * 100, 1) : null,
            ],
        ];
    }

    /**
     * Invalidate cached limits for a school.
     */
    public static function invalidateCache(int $schoolId): void
    {
        Cache::forget("school_limits_{$schoolId}");
        Cache::forget("school_student_limit_{$schoolId}");
        Cache::forget("school_teacher_limit_{$schoolId}");
        Cache::forget("school_parent_limit_{$schoolId}");
    }

    protected static function latestAllocation(int $schoolId): ?SchoolPopulationAllocation
    {
        return SchoolPopulationAllocation::where('school_id', $schoolId)->latest()->first();
    }

    protected static function subscription(int $schoolId): ?Subscription
    {
        return Subscription::withoutGlobalScopes()
            ->with('package')
            ->where('school_id', $schoolId)
            ->latest()
            ->first();
    }
}
