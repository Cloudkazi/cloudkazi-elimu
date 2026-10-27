<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::with('role')->get()->map(function($u) {
    return [
        'id' => $u->id,
        'name' => $u->name,
        'email' => $u->email,
        'role' => $u->role?->slug,
        'school_id' => $u->school_id,
        'is_demo' => $u->is_demo,
        'is_demo_account' => $u->isDemoAccount(),
        'is_super_admin' => $u->isSuperAdmin(),
        'is_school_admin' => $u->isSchoolAdmin(),
        'dashboard_route' => $u->getDashboardRoute(),
    ];
});

echo json_encode($users, JSON_PRETTY_PRINT);
