<?php

namespace App\Providers;

use App\Models\Student;
use App\Models\User;
use App\Policies\StudentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);

        // Define convenient gates for student-specific data access
        Gate::define('view-student-marks', function (User $user, Student $student) {
            return $user->canAccessStudent($student);
        });

        Gate::define('view-student-fees', function (User $user, Student $student) {
            return $user->canAccessStudent($student);
        });

        Gate::define('view-student-timetable', function (User $user, Student $student) {
            return $user->canAccessStudent($student);
        });

        RateLimiter::for('ai-help', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });
    }
}
