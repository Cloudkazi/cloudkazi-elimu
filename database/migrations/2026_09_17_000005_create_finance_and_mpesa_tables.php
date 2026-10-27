<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // Tuition, Boarding, Development, Activity, Examination, Uniform, Transport
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('academic_terms')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('fee_category_id')->constrained('fee_categories')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });

        Schema::create('student_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('invoice_number');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('academic_terms')->onDelete('cascade');
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->decimal('waiver_amount', 12, 2)->default(0.00);
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->enum('status', ['unpaid', 'partially_paid', 'paid'])->default('unpaid');
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'invoice_number']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('receipt_number');
            $table->foreignId('student_invoice_id')->nullable()->constrained('student_invoices')->onDelete('set null');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['mpesa', 'bank', 'cash', 'cheque'])->default('mpesa');
            $table->string('reference_code'); // M-Pesa Transaction ID or Bank Slip
            $table->date('payment_date');
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'receipt_number']);
        });

        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('set null');
            $table->string('merchant_request_id')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->string('phone_number');
            $table->decimal('amount', 10, 2);
            $table->string('mpesa_receipt_number')->nullable();
            $table->string('result_code')->nullable();
            $table->text('result_desc')->nullable();
            $table->enum('status', ['initiated', 'completed', 'failed'])->default('initiated');
            $table->timestamp('transaction_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('student_invoices');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_categories');
    }
};
