<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SubscriptionPayment;
use App\Models\Subscription;
use App\Models\Package;
use Illuminate\Support\Facades\DB;

// Package distribution
$pkgDistribution = Package::withCount(['subscriptions' => function ($q) {
    $q->withoutGlobalScopes()->where('status', 'active');
}])->get();

foreach ($pkgDistribution as $p) {
    echo "Package: {$p->name} => Active Subscriptions: {$p->subscriptions_count}\n";
}

// Monthly SaaS revenue
$revenueByMonth = SubscriptionPayment::where('status', 'successful')
    ->selectRaw("DATE_FORMAT(payment_date, '%b %Y') as month, SUM(amount) as total")
    ->groupBy('month')
    ->orderBy(DB::raw("MIN(payment_date)"))
    ->get();

print_r($revenueByMonth->toArray());
