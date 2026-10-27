<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RestrictLibrarian
{
    /**
     * Allowed route name prefixes for Librarian users.
     */
    protected array $allowedRoutePrefixes = [
        'library.',
        'operations.library',
        'profile.change-password',
        'logout',
        'auth.',
    ];

    /**
     * Allowed URL path patterns for Librarian users.
     */
    protected array $allowedUrlPatterns = [
        'library',
        'library/*',
        'operations/library',
        'operations/library/*',
        'profile/change-password',
        'logout',
        'auth/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isLibrarian()) {
            $route = $request->route();
            $routeName = $route ? $route->getName() : '';

            $isAllowed = false;

            if ($routeName) {
                foreach ($this->allowedRoutePrefixes as $prefix) {
                    if (str_starts_with($routeName, $prefix) || $routeName === rtrim($prefix, '.')) {
                        $isAllowed = true;
                        break;
                    }
                }
            }

            if (!$isAllowed) {
                foreach ($this->allowedUrlPatterns as $pattern) {
                    if ($request->is($pattern)) {
                        $isAllowed = true;
                        break;
                    }
                }
            }

            if (!$isAllowed) {
                abort(403, 'Unauthorized. Librarian accounts are restricted to the Library module.');
            }
        }

        return $next($request);
    }
}
