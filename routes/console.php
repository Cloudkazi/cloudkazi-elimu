<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('saas:check-expired', function () {
    $this->info('Checking SaaS school subscription statuses...');
    $stats = \App\Services\SchoolSubscriptionService::checkAndTransitionExpiredSubscriptions();
    $this->info("Done: {$stats['grace_period']} entered grace period, {$stats['expired']} expired.");
})->purpose('Check school subscriptions and transition expired/grace period states');

Artisan::command('saas:send-renewal-reminders {--days=30 : Days threshold to look ahead}', function () {
    $days = (int) $this->option('days');
    $this->info("Scanning for active school subscriptions expiring within {$days} days...");
    $expiring = \App\Services\SchoolSubscriptionService::getExpiringSubscriptions($days);

    if ($expiring->isEmpty()) {
        $this->info('No subscriptions expiring in this period.');
        return;
    }

    $this->table(
        ['School', 'Package', 'Expires At', 'Days Left'],
        $expiring->map(fn ($sub) => [
            $sub->school?->name ?? 'Unknown',
            $sub->package?->name ?? $sub->plan_name,
            $sub->end_date?->format('Y-m-d') ?? 'N/A',
            $sub->daysRemaining(),
        ])
    );
})->purpose('List or notify schools whose subscriptions are expiring soon');

