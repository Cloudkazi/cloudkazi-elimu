<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware to restrict Bursar role from accessing non‑finance routes.
 * If a Bursar attempts to access any route not prefixed with 'finance.',
 * a 403 Forbidden response is returned.
 */
class RestrictBursar
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && $user->isBursar()) {
            $route = $request->route();
            $routeName = $route ? $route->getName() : '';
            // Allow only finance routes for Bursar
            if (!str_starts_with($routeName, 'finance.')) {
                abort(403, 'Bursar role is limited to finance modules.');
            }
        }
        return $next($request);
    }
}
