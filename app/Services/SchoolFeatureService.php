<?php

namespace App\Services;

use App\Models\Package;
use App\Models\School;
use App\Models\SchoolFeature;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SchoolFeatureService
{
    /**
     * Map common aliases or friendly names to canonical package feature keys.
     */
    public static function normalizeFeatureKey(string $key): string
    {
        $map = [
            'students'      => 'student_management',
            'student'       => 'student_management',
            'academics'     => 'student_management',
            'teachers'      => 'teacher_management',
            'teacher'       => 'teacher_management',
            'staff'         => 'teacher_management',
            'parents'       => 'parent_portal',
            'parent'        => 'parent_portal',
            'fees'          => 'finance',
            'fee'           => 'finance',
            'exams'         => 'examinations',
            'exam'          => 'examinations',
            'timetables'    => 'timetable',
            'notices'       => 'communication',
            'notice'        => 'communication',
            'sms_bulk'      => 'sms',
            'bulk_sms'      => 'sms',
            'report'        => 'reports',
        ];

        $cleaned = strtolower(trim($key));
        return $map[$cleaned] ?? $cleaned;
    }

    /**
     * Check if a feature is enabled for a school.
     * Resolution order: Super Admin override → subscription status → package features → true (default)
     */
    public static function isEnabled(int $schoolId, string $featureKey): bool
    {
        $featureKey = static::normalizeFeatureKey($featureKey);

        // 1. Check school-specific override
        $override = SchoolFeature::where('school_id', $schoolId)
            ->where('feature_key', $featureKey)
            ->first();

        if ($override) {
            // Check expiry
            if ($override->expires_at && $override->expires_at->isPast()) {
                return static::fromPackage($schoolId, $featureKey);
            }
            return (bool) $override->enabled;
        }

        // 2. Fall back to package
        return static::fromPackage($schoolId, $featureKey);
    }

    /**
     * Alias method for requirement compatibility.
     */
    public static function schoolHasFeature(int $schoolId, string $featureKey): bool
    {
        return static::isEnabled($schoolId, $featureKey);
    }

    /**
     * Check if feature is enabled for the currently authenticated user's school.
     */
    public static function featureEnabledForCurrentSchool(string $featureKey): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $schoolId = $user->school_id ?? session('active_school_id');
        if (!$schoolId) {
            return false;
        }

        return static::isEnabled((int) $schoolId, $featureKey);
    }

    /**
     * Check feature from the school's active subscription package.
     */
    protected static function fromPackage(int $schoolId, string $featureKey): bool
    {
        $featureKey = static::normalizeFeatureKey($featureKey);

        $school = School::find($schoolId);
        if (!$school) {
            return false;
        }

        $subscription = $school->subscription;
        if (!$subscription || !$subscription->package_id) {
            return true; // No restriction if no package configured
        }

        // If subscription is suspended/cancelled, deny non-basic features
        if (in_array($subscription->status, ['suspended', 'cancelled'])) {
            return false;
        }

        $package = $subscription->package;
        if (!$package) {
            return true;
        }

        return $package->isFeatureEnabled($featureKey);
    }

    /**
     * Get all enabled features for a school.
     */
    public static function getEnabledFeatures(int $schoolId): array
    {
        $allKeys = array_keys(Package::allFeatureKeys());
        return array_filter($allKeys, fn ($key) => static::isEnabled($schoolId, $key));
    }

    /**
     * Get full feature state with source info for Super Admin display.
     */
    public static function getFeatureState(int $schoolId, string $featureKey): array
    {
        $featureKey = static::normalizeFeatureKey($featureKey);

        $override = SchoolFeature::where('school_id', $schoolId)
            ->where('feature_key', $featureKey)
            ->first();

        $fromPackage = static::fromPackage($schoolId, $featureKey);
        $isEnabled   = static::isEnabled($schoolId, $featureKey);

        return [
            'feature_key'   => $featureKey,
            'enabled'       => $isEnabled,
            'source'        => $override ? $override->source : 'package',
            'package_value' => $fromPackage,
            'overridden'    => $override !== null,
            'expires_at'    => $override?->expires_at,
        ];
    }

    /**
     * Set school-level feature override.
     */
    public static function setOverride(int $schoolId, string $featureKey, bool $enabled, int $assignedBy, ?string $expiresAt = null): void
    {
        $featureKey = static::normalizeFeatureKey($featureKey);

        SchoolFeature::updateOrCreate(
            ['school_id' => $schoolId, 'feature_key' => $featureKey],
            [
                'enabled'     => $enabled,
                'source'      => 'superadmin',
                'expires_at'  => $expiresAt,
                'assigned_by' => $assignedBy,
            ]
        );

        static::invalidateCache($schoolId);
    }

    /**
     * Reset a school feature to package default.
     */
    public static function resetToPackage(int $schoolId, string $featureKey): void
    {
        $featureKey = static::normalizeFeatureKey($featureKey);

        SchoolFeature::where('school_id', $schoolId)
            ->where('feature_key', $featureKey)
            ->delete();

        static::invalidateCache($schoolId);
    }

    /**
     * Invalidate cached feature permissions for a school.
     */
    public static function invalidateCache(int $schoolId): void
    {
        Cache::forget("school_features_{$schoolId}");
        Cache::forget("school_features_enabled_{$schoolId}");
    }
}
