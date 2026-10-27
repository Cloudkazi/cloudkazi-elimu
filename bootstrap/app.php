<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\TenantMiddleware::class,
            \App\Http\Middleware\DemoAccountMiddleware::class,
            \App\Http\Middleware\RestrictLibrarian::class,
            \App\Http\Middleware\RestrictHostelWarden::class,  // ← Hostel Warden portal isolation
            \App\Http\Middleware\EnsureSchoolActive::class,   // ← School suspension/expiry enforcement
        ]);

        // Exempt Safaricom Daraja callbacks from CSRF (Student fee payments & SaaS subscription payments)
        $middleware->validateCsrfTokens(except: [
            'mpesa/callback',
            'mpesa/subscription-callback',
        ]);

        $middleware->alias([
            'role'               => \App\Http\Middleware\RoleMiddleware::class,
            'tenant'             => \App\Http\Middleware\TenantMiddleware::class,
            'restrict.librarian' => \App\Http\Middleware\RestrictLibrarian::class,
            'restrict.warden'    => \App\Http\Middleware\RestrictHostelWarden::class,
            'school.feature'     => \App\Http\Middleware\CheckSchoolFeature::class,
            'school.active'      => \App\Http\Middleware\EnsureSchoolActive::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
