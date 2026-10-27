<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$demos = App\Models\User::withoutGlobalScopes()
    ->where('is_demo', 1)
    ->orWhere('email', 'like', '%@demo.com')
    ->with('role')
    ->get();

echo "Found " . $demos->count() . " demo accounts:\n";
foreach ($demos as $d) {
    echo "ID: {$d->id}, Email: {$d->email}, Role: {$d->role?->slug}, is_demo: {$d->is_demo}, isDemoAccount: " . ($d->isDemoAccount() ? 'true' : 'false') . "\n";
}
