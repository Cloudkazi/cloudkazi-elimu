<?php

namespace App\Http\Middleware;

use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // If user is Platform Super Administrator
        if (Auth::check() && Auth::user()->isSuperAdmin()) {
            Session::forget('active_school_id');
            View::share('currentSchool', null);
            View::share('allSchools', School::where('status', 'active')->get());

            // Protect school data: redirect super admin away from school internal routes
            $path = $request->path();
            if (!str_starts_with($path, 'superadmin') && 
                !str_starts_with($path, 'super-admin') && 
                !str_starts_with($path, 'auth') && 
                !str_starts_with($path, 'profile') && 
                $path !== 'logout') {
                return redirect()->route('superadmin.dashboard');
            }

            return $next($request);
        }

        // For authenticated school users, their school is strictly determined by their school_id
        if (Auth::check() && Auth::user()->school_id) {
            $school = Auth::user()->school;
            // Always keep active_school_id in sync with user's assigned school
            Session::put('active_school_id', $school?->id);
        } elseif (Auth::check()) {
            // Authenticated user with no school assignment (e.g. demo role)
            $school = null;
            Session::forget('active_school_id');
        } else {
            // Unauthenticated guest: do NOT inject any default school
            $school = null;
            Session::forget('active_school_id');
        }

        // Share active tenant school and dynamic academic context with all Blade views
        View::share('currentSchool', $school);
        View::share('allSchools', School::where('status', 'active')->get());

        if ($school) {
            $academicContext = \App\Services\AcademicCalendarService::getAcademicContext($school->id);
            Session::put('academic_year', $academicContext['year_name']);
            Session::put('current_term', $academicContext['term_name']);
            View::share('currentAcademicYear', $academicContext['year']);
            View::share('currentAcademicTerm', $academicContext['term']);
            View::share('currentAcademicYearName', $academicContext['year_name']);
            View::share('currentAcademicTermName', $academicContext['term_name']);
        } else {
            Session::forget('academic_year');
            Session::forget('current_term');
            View::share('currentAcademicYear', null);
            View::share('currentAcademicTerm', null);
            View::share('currentAcademicYearName', null);
            View::share('currentAcademicTermName', null);
        }

        return $next($request);
    }
}
