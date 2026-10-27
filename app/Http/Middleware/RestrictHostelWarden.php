<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RestrictHostelWarden
{
    /**
     * Allowed route name prefixes for Hostel Warden users.
     */
    protected array $allowedRoutePrefixes = [
        'warden.',
        'hostel.',
        'operations.hostel',
        'operations.notices',
        'communication.index',
        'profile.change-password',
        'logout',
        'auth.',
        'dashboard',
        'school.dashboard',
        'school.suspended',
        'school.expired',
    ];

    /**
     * Explicitly permitted student route names (read-only views relevant to boarding).
     */
    protected array $allowedStudentRoutes = [
        'students.index',
        'students.search',
        'students.show',
    ];

    /**
     * Allowed URL path patterns for Hostel Warden users.
     */
    protected array $allowedUrlPatterns = [
        'warden',
        'warden/*',
        'dashboard',
        'school/dashboard',
        'hostel',
        'hostel/*',
        'operations/hostel',
        'operations/hostel/*',
        'operations/notices',
        'communication',
        'profile/change-password',
        'profile/change-password/*',
        'logout',
        'auth/*',
        'account-suspended',
        'account-expired',
    ];

    /**
     * Explicitly permitted student URL patterns (read-only).
     */
    protected array $allowedStudentUrlPatterns = [
        'students',
        'students/search',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isHostelWarden()) {
            $route = $request->route();
            $routeName = $route ? $route->getName() : '';
            $path = trim($request->path(), '/');

            // 1. Block any non-GET request to communications (e.g. sending SMS or creating announcements)
            if ($request->is('communication/*') || $request->is('operations/notices/*') || str_starts_with($routeName, 'communication.announcements') || $routeName === 'communication.send_sms') {
                abort(403, 'Unauthorized. Hostel Wardens cannot broadcast notices or dispatch SMS.');
            }

            // 2. Block administrative mutations or non-whitelisted student actions
            if ($request->is('students/*') || str_starts_with($routeName, 'students.')) {
                // Disallow creating, importing, editing, deleting, or promoting students
                if (
                    $request->isMethod('POST') ||
                    $request->isMethod('PUT') ||
                    $request->isMethod('PATCH') ||
                    $request->isMethod('DELETE') ||
                    $request->is('students/create') ||
                    $request->is('students/import*') ||
                    $request->is('students/promotions*') ||
                    $request->is('students/*/edit') ||
                    $request->is('students/documents*') ||
                    in_array($routeName, [
                        'students.create',
                        'students.store',
                        'students.edit',
                        'students.update',
                        'students.destroy',
                        'students.import',
                        'students.import.process',
                        'students.import.template',
                        'students.promotions',
                        'students.promotions.process',
                        'students.documents',
                    ])
                ) {
                    abort(403, 'Unauthorized. Student enrollment and administrative changes are restricted to School Administrators.');
                }

                // Allow viewing specific student profile: students/{id} (numeric ID only)
                if (preg_match('#^students/[0-9]+$#', $path) && $request->isMethod('GET')) {
                    return $next($request);
                }

                if (!in_array($routeName, $this->allowedStudentRoutes) && !in_array($path, $this->allowedStudentUrlPatterns)) {
                    abort(403, 'Unauthorized. Hostel Wardens can only view student lists and accommodation details.');
                }

                return $next($request);
            }

            // 3. Whitelist check for other warden routes
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
                abort(403, 'Unauthorized. Hostel Warden accounts are restricted to the Warden Portal, Hostel & Boarding, and authorized student records.');
            }
        }

        return $next($request);
    }
}
