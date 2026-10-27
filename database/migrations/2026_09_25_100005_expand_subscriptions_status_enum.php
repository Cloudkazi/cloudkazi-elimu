<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Expand subscriptions.status enum to include all SaaS lifecycle states.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `subscriptions` MODIFY `status` ENUM(
            'active',
            'trial',
            'pending',
            'grace_period',
            'expired',
            'suspended',
            'cancelled'
        ) NOT NULL DEFAULT 'trial'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `subscriptions` MODIFY `status` ENUM(
            'active',
            'expired',
            'trial',
            'cancelled'
        ) NOT NULL DEFAULT 'trial'");
    }
};
