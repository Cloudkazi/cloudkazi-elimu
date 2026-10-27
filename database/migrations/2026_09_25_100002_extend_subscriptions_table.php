<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend subscriptions table safely (non-destructive)
        Schema::table('subscriptions', function (Blueprint $table) {
            // package linkage — nullable so existing rows are not broken
            $table->unsignedBigInteger('package_id')->nullable()->after('school_id');
            // financial
            $table->decimal('activation_fee', 10, 2)->nullable()->after('amount');
            $table->decimal('annual_fee', 10, 2)->nullable()->after('activation_fee');
            $table->string('payment_status', 30)->default('pending')->after('status');
            // population overrides stored on subscription level
            $table->unsignedInteger('student_limit')->nullable()->after('payment_status');
            $table->unsignedInteger('teacher_limit')->nullable()->after('student_limit');
            $table->unsignedInteger('parent_limit')->nullable()->after('teacher_limit');
            $table->unsignedInteger('admin_limit')->nullable()->after('parent_limit');
            // lifecycle timestamps
            $table->timestamp('grace_period_ends_at')->nullable()->after('end_date');
            $table->timestamp('activated_at')->nullable()->after('grace_period_ends_at');
            $table->timestamp('suspended_at')->nullable()->after('activated_at');
            $table->timestamp('cancelled_at')->nullable()->after('suspended_at');
            // notes
            $table->text('notes')->nullable()->after('cancelled_at');
        });

        // Patch existing subscriptions: assign legacy package id
        $legacyPkgId = DB::table('packages')->where('code', 'STD-LEGACY')->value('id');
        if ($legacyPkgId) {
            DB::table('subscriptions')->whereNull('package_id')->update([
                'package_id'     => $legacyPkgId,
                'payment_status' => 'successful',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'package_id', 'activation_fee', 'annual_fee', 'payment_status',
                'student_limit', 'teacher_limit', 'parent_limit', 'admin_limit',
                'grace_period_ends_at', 'activated_at', 'suspended_at', 'cancelled_at', 'notes',
            ]);
        });
    }
};
