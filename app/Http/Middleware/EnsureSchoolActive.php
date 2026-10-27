<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureSchoolActive — centralized school account status enforcement.
 *
 * Resolution order:
 *  1. Super Admin → always pass through (no school restrictions).
 *  2. Unauthenticated → pass to auth middleware.
 *  3. Determine school_id from user or session.
 *  4. Load school from DB (fresh — never from stale session/cache).
 *  5. If suspended → redirect to suspension page (JSON: 403).
 *  6. If expired/cancelled → redirect to expired page (JSON: 403).
 *  7. Otherwise → pass.
 *
 * Safe paths that must always be reachable even for suspended schools:
 *  - /logout
 *  - /login
 *  - /account-suspended
 *  - /account-expired
 *  - /profile/change-password
 */
class EnsureSchoolActive
{
    /**
     * Paths that suspended/expired school users may still access.
     */
    protected array $allowedPaths = [
        'logout',
        'login',
        'account-suspended',
        'account-expired',
        'profile/change-password',
        'privacy-policy',
        'terms-and-conditions',
        'help-center',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Unauthenticated — let the auth middleware handle it
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // 2. Super Admin bypasses all school restrictions
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // 3. Determine school context: user's assigned school_id takes absolute precedence
        $schoolId = $user->school_id ?: session('active_school_id');

        if (!$schoolId) {
            return $next($request);
        }

        // 4. Always load fresh from DB (never trust cached state)
        $school = School::find($schoolId);

        if (!$school) {
            return $next($request);
        }

        // 5. Check if current path is in the always-allowed list
        $currentPath = $request->path();
        foreach ($this->allowedPaths as $allowed) {
            if ($currentPath === $allowed || str_starts_with($currentPath, $allowed . '/')) {
                return $next($request);
            }
        }

        // 6. Enforce suspension
        if ($school->status === 'suspended') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your school account has been suspended. Please contact CloudKazi support.',
                    'code'    => 'SCHOOL_SUSPENDED',
                ], 403);
            }

            return redirect()->route('school.suspended');
        }

        // 7. Enforce expiry (expired or cancelled)
        if (in_array($school->status, ['expired', 'cancelled'])) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your school subscription has expired. Please contact CloudKazi support.',
                    'code'    => 'SUBSCRIPTION_EXPIRED',
                ], 403);
            }

            return redirect()->route('school.expired');
        }

        return $next($request);
    }
}
