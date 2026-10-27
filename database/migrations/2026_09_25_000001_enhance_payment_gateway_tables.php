<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Extend school_settings with payment gateway configuration ─────
        Schema::table('school_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('school_settings', 'daraja_environment')) {
                $table->string('daraja_environment')->default('sandbox')->after('daraja_passkey');
            }
            if (!Schema::hasColumn('school_settings', 'daraja_paybill_number')) {
                $table->string('daraja_paybill_number')->nullable()->after('daraja_environment');
            }
            if (!Schema::hasColumn('school_settings', 'daraja_till_number')) {
                $table->string('daraja_till_number')->nullable()->after('daraja_paybill_number');
            }
            if (!Schema::hasColumn('school_settings', 'daraja_business_name')) {
                $table->string('daraja_business_name')->nullable()->after('daraja_till_number');
            }
            if (!Schema::hasColumn('school_settings', 'daraja_callback_url')) {
                $table->string('daraja_callback_url')->nullable()->after('daraja_business_name');
            }
            // Payment method toggles (stored as JSON flags)
            if (!Schema::hasColumn('school_settings', 'enabled_payment_methods')) {
                $table->json('enabled_payment_methods')->nullable()->after('daraja_callback_url');
                // Default: all methods enabled
            }
            if (!Schema::hasColumn('school_settings', 'preferred_payment_methods')) {
                $table->json('preferred_payment_methods')->nullable()->after('enabled_payment_methods');
            }
        });

        // ── 2. School bank accounts (multiple per school) ────────────────────
        if (!Schema::hasTable('school_bank_accounts')) {
            Schema::create('school_bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->string('bank_name');           // KCB, Equity, Co-op, NCBA, DTB, etc.
                $table->string('account_name');
                $table->string('account_number');
                $table->string('branch')->nullable();
                $table->string('bank_code')->nullable();  // SWIFT / sort code
                $table->string('api_key')->nullable();    // For future bank API integration
                $table->string('api_secret')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_primary')->default(false);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ── 3. Payment reminders log ─────────────────────────────────────────
        if (!Schema::hasTable('payment_reminders')) {
            Schema::create('payment_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade');
                $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->onDelete('set null');
                $table->enum('target_type', ['all', 'class', 'student', 'arrears'])->default('arrears');
                $table->enum('channel', ['sms', 'email', 'system'])->default('sms');
                $table->text('message');
                $table->integer('recipients_count')->default(0);
                $table->enum('status', ['sent', 'failed', 'partial'])->default('sent');
                $table->timestamps();
            });
        }

        // ── 4. Extend fee_payments payment_method to cover all gateway types ─
        // We use a safe ALTER on SQLite/MySQL by adding a new column and migrating
        // For MySQL: modify the enum; gracefully skip if column already has values
        if (Schema::hasTable('fee_payments') && Schema::hasColumn('fee_payments', 'payment_method')) {
            try {
                // MySQL: extend the enum
                DB::statement("ALTER TABLE fee_payments MODIFY COLUMN payment_method ENUM('mpesa','mpesa_stk','mpesa_paybill','mpesa_till','bank','bank_transfer','bank_deposit','cash','cheque') NOT NULL DEFAULT 'mpesa'");
            } catch (\Exception $e) {
                // SQLite or already correct — skip silently
            }
        }

        // ── 5. Add mpesa_type column to mpesa_transactions ───────────────────
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('mpesa_transactions', 'mpesa_type')) {
                $table->enum('mpesa_type', ['stk', 'paybill', 'till'])->default('stk')->after('school_id');
            }
            if (!Schema::hasColumn('mpesa_transactions', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->constrained('student_invoices')->onDelete('set null')->after('student_id');
            }
        });

        // ── 6. Add payment_source to fee_payments for traceability ───────────
        Schema::table('fee_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_payments', 'payment_source')) {
                $table->enum('payment_source', ['bursar', 'parent_portal', 'mpesa_callback', 'admin'])->default('bursar')->after('notes');
            }
            if (!Schema::hasColumn('fee_payments', 'bank_account_id')) {
                $table->foreignId('bank_account_id')->nullable()->constrained('school_bank_accounts')->onDelete('set null')->after('payment_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_payments', function (Blueprint $table) {
            if (Schema::hasColumn('fee_payments', 'bank_account_id')) {
                $table->dropForeign(['bank_account_id']);
                $table->dropColumn('bank_account_id');
            }
            if (Schema::hasColumn('fee_payments', 'payment_source')) {
                $table->dropColumn('payment_source');
            }
        });

        Schema::table('mpesa_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('mpesa_transactions', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
                $table->dropColumn('invoice_id');
            }
            if (Schema::hasColumn('mpesa_transactions', 'mpesa_type')) {
                $table->dropColumn('mpesa_type');
            }
        });

        Schema::dropIfExists('payment_reminders');
        Schema::dropIfExists('school_bank_accounts');

        Schema::table('school_settings', function (Blueprint $table) {
            $cols = ['daraja_environment','daraja_paybill_number','daraja_till_number',
                     'daraja_business_name','daraja_callback_url','enabled_payment_methods','preferred_payment_methods'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('school_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
