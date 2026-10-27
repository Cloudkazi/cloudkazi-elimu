<?php

namespace App\Http\Middleware;

use App\Models\Package;
use App\Services\SchoolFeatureService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckSchoolFeature — enforces per-feature access control at the route level.
 *
 * Usage in routes:
 *   ->middleware('school.feature:finance')
 *   ->middleware('school.feature:examinations')
 *   ->middleware('school.feature:timetable')
 *   etc.
 *
 * Super Admin is always exempt.
 * Unauthenticated requests pass through (auth middleware handles those).
 *
 * On denial:
 *   - JSON request  → 403 JSON response
 *   - Browser request → feature-unavailable view or redirect with flash
 */
class CheckSchoolFeature
{
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = Auth::user();

        // Unauthenticated: let auth middleware handle
        if (!$user) {
            return $next($request);
        }

        // Super Admin bypasses feature checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $schoolId = $user->school_id ?: session('active_school_id');

        if (!$schoolId) {
            return $next($request);
        }

        $canonicalKey = SchoolFeatureService::normalizeFeatureKey($featureKey);

        // Check if feature is enabled
        if (!SchoolFeatureService::isEnabled((int) $schoolId, $canonicalKey)) {
            $allFeatures = Package::allFeatureKeys();
            $featureName = $allFeatures[$canonicalKey] ?? ucfirst(str_replace('_', ' ', $canonicalKey));

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "The '{$featureName}' module is not included in your institution's current subscription plan or has been disabled by the system administrator.",
                    'code'    => 'FEATURE_DISABLED',
                    'feature' => $canonicalKey,
                ], 403);
            }

            // Show a dedicated feature-unavailable page
            return response()->view('errors.feature-disabled', [
                'featureKey'  => $canonicalKey,
                'featureName' => $featureName,
            ], 403);
        }

        return $next($request);
    }
}
