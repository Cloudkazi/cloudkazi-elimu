<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DemoAccountMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        if (! $user || ! $user->isDemoAccount()) {
            return $next($request);
        }

        // Demo users must never access Super Admin routes
        $path = $request->path();
        if (str_starts_with($path, 'superadmin') || str_starts_with($path, 'super-admin')) {
            abort(403, 'This area is restricted to platform administrators.');
        }

        // Demo users are strictly read-only: block any state-modifying write requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            // Allow logout
            if ($request->routeIs('logout') || $path === 'logout') {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This is a demo account. Write operations are disabled in read-only demo mode.',
                ], 403);
            }

            return back()->with('warning', 'This is a demo account. Write operations are disabled in read-only demo mode.');
        }

        return $next($request);
    }
}
