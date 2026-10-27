<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Expand schools.status enum to include all SaaS lifecycle states.
     * Non-destructive — existing values remain valid.
     */
    public function up(): void
    {
        // MySQL ALTER TABLE MODIFY to expand enum — safe operation
        DB::statement("ALTER TABLE `schools` MODIFY `status` ENUM(
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
        DB::statement("ALTER TABLE `schools` MODIFY `status` ENUM(
            'active',
            'trial',
            'suspended',
            'expired'
        ) NOT NULL DEFAULT 'trial'");
    }
};
