<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('motto')->nullable();
            $table->string('address')->nullable();
            $table->string('county')->nullable()->default('Nairobi');
            $table->string('logo')->nullable();
            $table->enum('curriculum_type', ['CBC', '8-4-4', 'Hybrid'])->default('Hybrid');
            $table->enum('status', ['active', 'trial', 'suspended', 'expired'])->default('active');
            $table->date('trial_ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('currency')->default('KES');
            $table->string('timezone')->default('Africa/Nairobi');
            $table->string('daraja_shortcode')->nullable()->default('174379');
            $table->string('daraja_consumer_key')->nullable();
            $table->string('daraja_consumer_secret')->nullable();
            $table->string('daraja_passkey')->nullable();
            $table->string('sms_sender_id')->nullable()->default('CLOUDKAZI');
            $table->string('sms_api_key')->nullable();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('plan_name')->default('Standard ERP');
            $table->decimal('amount', 12, 2)->default(35000.00);
            $table->enum('status', ['active', 'expired', 'trial', 'cancelled'])->default('active');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('sms_balance')->default(1000);
            $table->integer('storage_limit_mb')->default(5120);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('school_settings');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('schools');
    }
};
