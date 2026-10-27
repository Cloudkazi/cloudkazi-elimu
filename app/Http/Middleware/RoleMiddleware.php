<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Super Admin is restricted to /superadmin/* paths.
        // TenantMiddleware already enforces this redirect.
        // Super Admin must NOT bypass school ERP role checks.
        if ($user->isSuperAdmin()) {
            $path = $request->path();
            if (
                str_starts_with($path, 'superadmin') ||
                str_starts_with($path, 'super-admin') ||
                str_starts_with($path, 'auth') ||
                str_starts_with($path, 'profile') ||
                $path === 'logout'
            ) {
                return $next($request);
            }
            // Redirect Super Admin away from school ERP routes
            return redirect()->route('superadmin.dashboard')
                ->with('warning', 'Platform administrators access the Super Admin area only.');
        }

        if (empty($roles)) {
            return $next($request);
        }

        if ($user->hasRole($roles)) {
            return $next($request);
        }

        abort(403, 'Unauthorized. Your role (' . ($user->role->name ?? 'User') . ') does not have access to this module.');
    }
}

