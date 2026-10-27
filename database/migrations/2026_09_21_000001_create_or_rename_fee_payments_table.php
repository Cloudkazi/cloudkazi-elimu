<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename or create fee_payments table
        if (Schema::hasTable('payments') && !Schema::hasTable('fee_payments')) {
            Schema::rename('payments', 'fee_payments');
        } elseif (!Schema::hasTable('fee_payments')) {
            Schema::create('fee_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->string('receipt_number');
                $table->foreignId('student_invoice_id')->nullable()->constrained('student_invoices')->onDelete('set null');
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->decimal('amount', 12, 2);
                $table->enum('payment_method', ['mpesa', 'bank', 'cash', 'cheque'])->default('mpesa');
                $table->string('reference_code');
                $table->date('payment_date');
                $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'receipt_number']);
            });

            // If payments table existed previously, copy any records over
            if (Schema::hasTable('payments')) {
                DB::statement('INSERT INTO fee_payments SELECT * FROM payments');
            }
        }

        // 2. Add boarding and transport profile helper fields to students if not already present
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'fee_profile')) {
                $table->enum('fee_profile', ['regular', 'boarder', 'transport', 'boarder_transport'])->default('regular')->after('curriculum');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('fee_payments') && !Schema::hasTable('payments')) {
            Schema::rename('fee_payments', 'payments');
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'fee_profile')) {
                $table->dropColumn('fee_profile');
            }
        });
    }
};
