<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // School-specific feature overrides
        Schema::create('school_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('feature_key', 60);
            $table->boolean('enabled')->default(true);
            $table->string('source', 30)->default('package')->comment('package|superadmin|override');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'feature_key']);
        });

        // School-level custom population allocations
        Schema::create('school_population_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->unsignedInteger('student_limit')->nullable();
            $table->unsignedInteger('teacher_limit')->nullable();
            $table->unsignedInteger('parent_limit')->nullable();
            $table->unsignedInteger('admin_limit')->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
        });

        // SaaS subscription payments — SEPARATE from student fee payments
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 50)->default('mpesa')->comment('mpesa|bank|paystack|manual');
            $table->string('reference', 100)->nullable();
            $table->string('status', 30)->default('pending')->comment('pending|processing|successful|failed|cancelled|refunded');
            $table->string('mpesa_checkout_request_id', 100)->nullable();
            $table->string('mpesa_merchant_request_id', 100)->nullable();
            $table->string('mpesa_receipt', 50)->nullable();
            $table->string('phone', 20)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();
            $table->unique('reference', 'sp_ref_unique');
        });

        // Platform audit logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('action', 100);
            $table->string('module', 60)->nullable();
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'created_at']);
            $table->index(['action', 'module']);
        });

        // Platform-level settings (Super Admin global config)
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string')->comment('string|integer|boolean|json');
            $table->string('group', 60)->default('general');
            $table->string('label', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('school_population_allocations');
        Schema::dropIfExists('school_features');
        Schema::dropIfExists('platform_settings');
    }
};
