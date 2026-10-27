<?php

namespace App\Services;

use App\Models\Package;
use App\Models\School;
use App\Models\SchoolFeature;
use App\Models\SchoolSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolSubscriptionService
{
    /**
     * Create a new subscription for a school using a package.
     * Pricing always comes from the package — never from user input.
     */
    public static function createSubscription(School $school, Package $package, array $options = []): Subscription
    {
        return DB::transaction(function () use ($school, $package, $options) {
            $status    = $package->trial_days > 0 ? 'trial' : 'pending';
            $startDate = now();
            $endDate   = $package->trial_days > 0
                ? now()->addDays($package->trial_days)
                : now()->addYear();

            $sub = Subscription::withoutGlobalScopes()->updateOrCreate(
                ['school_id' => $school->id],
                [
                    'package_id'     => $package->id,
                    'plan_name'      => $package->name,
                    'amount'         => $package->annual_fee,
                    'activation_fee' => $package->activation_fee,
                    'annual_fee'     => $package->annual_fee,
                    'status'         => $options['status'] ?? $status,
                    'payment_status' => $options['payment_status'] ?? 'pending',
                    'start_date'     => $options['start_date'] ?? $startDate,
                    'end_date'       => $options['end_date'] ?? $endDate,
                    'student_limit'  => null,
                    'teacher_limit'  => null,
                    'parent_limit'   => null,
                    'notes'          => $options['notes'] ?? null,
                ]
            );

            // Apply package features to school
            static::applyPackageFeatures($school, $package);

            static::invalidateCache($school->id);

            return $sub;
        });
    }

    /**
     * Apply package features to school_features table.
     * Super Admin manual overrides (source = 'superadmin') are preserved!
     */
    public static function applyPackageFeatures(School $school, Package $package): void
    {
        foreach ($package->features as $feature) {
            // Check if there is an active Super Admin override
            $existing = SchoolFeature::where('school_id', $school->id)
                ->where('feature_key', $feature->feature_key)
                ->first();

            if ($existing && $existing->source === 'superadmin') {
                continue; // Do not overwrite explicit Super Admin override
            }

            SchoolFeature::updateOrCreate(
                ['school_id' => $school->id, 'feature_key' => $feature->feature_key],
                ['enabled' => (bool) $feature->enabled, 'source' => 'package', 'assigned_by' => null]
            );
        }

        SchoolFeatureService::invalidateCache($school->id);
    }

    /**
     * Activate subscription after confirmed payment.
     */
    public static function activateSubscription(Subscription $subscription): void
    {
        $subscription->update([
            'status'         => 'active',
            'payment_status' => 'successful',
            'activated_at'   => now(),
            'suspended_at'   => null,
        ]);

        $subscription->school->update(['status' => 'active']);
        static::invalidateCache($subscription->school_id);
    }

    /**
     * Suspend school subscription. Does NOT delete any data.
     */
    public static function suspendSubscription(Subscription $subscription, ?string $reason = null): void
    {
        $subscription->update([
            'status'       => 'suspended',
            'suspended_at' => now(),
            'notes'        => $reason ?? $subscription->notes,
        ]);

        $subscription->school->update(['status' => 'suspended']);
        static::invalidateCache($subscription->school_id);
    }

    /**
     * Reactivate a suspended subscription.
     */
    public static function reactivateSubscription(Subscription $subscription): void
    {
        $subscription->update([
            'status'       => 'active',
            'suspended_at' => null,
        ]);

        $subscription->school->update(['status' => 'active']);
        static::invalidateCache($subscription->school_id);
    }

    /**
     * Cancel a subscription. Does NOT delete school data.
     */
    public static function cancelSubscription(Subscription $subscription, ?string $reason = null): void
    {
        $subscription->update([
            'status'       => 'cancelled',
            'cancelled_at' => now(),
            'notes'        => $reason ?? $subscription->notes,
        ]);

        $subscription->school->update(['status' => 'cancelled']);
        static::invalidateCache($subscription->school_id);
    }

    /**
     * Extend subscription by a given number of months.
     * If the subscription is already expired, extend from today.
     * If active, extend from the current end_date.
     */
    public static function extendSubscription(Subscription $subscription, int $months = 12): void
    {
        $baseDate = ($subscription->end_date && Carbon::parse($subscription->end_date)->isFuture())
            ? Carbon::parse($subscription->end_date)
            : now();

        $newEnd = (clone $baseDate)->addMonths($months);

        $subscription->update([
            'end_date'     => $newEnd,
            'status'       => 'active',
            'suspended_at' => null,
        ]);

        $subscription->school->update(['status' => 'active']);
        static::invalidateCache($subscription->school_id);
    }

    /**
     * Change a school's package. Pricing from DB — never from input.
     */
    public static function changePackage(Subscription $subscription, Package $newPackage): void
    {
        DB::transaction(function () use ($subscription, $newPackage) {
            $subscription->update([
                'package_id'  => $newPackage->id,
                'plan_name'   => $newPackage->name,
                'annual_fee'  => $newPackage->annual_fee,
                'amount'      => $newPackage->annual_fee,
            ]);

            // Re-apply features from new package (Super Admin overrides are preserved)
            static::applyPackageFeatures($subscription->school, $newPackage);

            static::invalidateCache($subscription->school_id);
        });
    }

    /**
     * Check subscriptions and transition expired/grace_period states.
     * Returns an array of statistics on actions taken.
     */
    public static function checkAndTransitionExpiredSubscriptions(): array
    {
        $stats = [
            'grace_period' => 0,
            'expired'      => 0,
        ];

        $now = now();

        // 1. Subscriptions currently active or trial but past end_date
        $activeOrTrialPast = Subscription::withoutGlobalScopes()
            ->with(['school', 'package'])
            ->whereIn('status', ['active', 'trial'])
            ->where('end_date', '<', $now)
            ->get();

        foreach ($activeOrTrialPast as $sub) {
            $graceDays = $sub->package?->grace_period_days ?? 14;
            $graceEnd = Carbon::parse($sub->end_date)->addDays($graceDays);

            if ($now <= $graceEnd) {
                // Enter grace period
                $sub->update([
                    'status' => 'grace_period',
                    'grace_period_ends_at' => $graceEnd,
                ]);
                $sub->school?->update(['status' => 'grace_period']);
                AuditLogService::log(
                    'subscription.grace_period',
                    'subscription',
                    "School '{$sub->school?->name}' entered grace period until {$graceEnd->format('Y-m-d')}",
                    $sub->school_id
                );
                $stats['grace_period']++;
            } else {
                // Expired directly
                $sub->update(['status' => 'expired']);
                $sub->school?->update(['status' => 'expired']);
                AuditLogService::log(
                    'subscription.expired',
                    'subscription',
                    "School '{$sub->school?->name}' subscription expired",
                    $sub->school_id
                );
                $stats['expired']++;
            }

            static::invalidateCache($sub->school_id);
        }

        // 2. Subscriptions currently in grace_period past their grace_period_ends_at
        $gracePast = Subscription::withoutGlobalScopes()
            ->with(['school', 'package'])
            ->where('status', 'grace_period')
            ->where(function ($q) use ($now) {
                $q->where('grace_period_ends_at', '<', $now)
                  ->orWhere(function ($q2) use ($now) {
                      $q2->whereNull('grace_period_ends_at')->where('end_date', '<', $now);
                  });
            })
            ->get();

        foreach ($gracePast as $sub) {
            $sub->update(['status' => 'expired']);
            $sub->school?->update(['status' => 'expired']);
            AuditLogService::log(
                'subscription.expired',
                'subscription',
                "School '{$sub->school?->name}' grace period concluded; subscription expired.",
                $sub->school_id
            );
            $stats['expired']++;
            static::invalidateCache($sub->school_id);
        }

        return $stats;
    }

    /**
     * Invalidate all related caches for a school.
     */
    public static function invalidateCache(int $schoolId): void
    {
        Cache::forget("school_status_{$schoolId}");
        Cache::forget("school_subscription_{$schoolId}");
        SchoolFeatureService::invalidateCache($schoolId);
        PopulationAllocationService::invalidateCache($schoolId);
    }

    /**
     * Get active subscriptions expiring within the next $days days.
     */
    public static function getExpiringSubscriptions(int $days = 30)
    {
        return Subscription::withoutGlobalScopes()
            ->with(['school', 'package'])
            ->where('status', 'active')
            ->whereBetween('end_date', [now(), now()->addDays($days)])
            ->orderBy('end_date')
            ->get();
    }
}
