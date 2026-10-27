<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend employees table
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'photo')) {
                $table->string('photo')->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('employees', 'gender')) {
                $table->string('gender', 20)->nullable()->after('photo');
            }
            if (!Schema::hasColumn('employees', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('employees', 'phone')) {
                $table->string('phone', 50)->nullable()->after('date_of_birth');
            }
            if (!Schema::hasColumn('employees', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('employees', 'physical_address')) {
                $table->text('physical_address')->nullable()->after('email');
            }
            if (!Schema::hasColumn('employees', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('physical_address');
            }
            if (!Schema::hasColumn('employees', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 50)->nullable()->after('emergency_contact_name');
            }
            if (!Schema::hasColumn('employees', 'emergency_contact_relationship')) {
                $table->string('emergency_contact_relationship', 50)->nullable()->after('emergency_contact_phone');
            }
            if (!Schema::hasColumn('employees', 'employment_type')) {
                $table->string('employment_type', 30)->default('permanent')->after('department');
            }
            if (!Schema::hasColumn('employees', 'contract_start_date')) {
                $table->date('contract_start_date')->nullable()->after('hire_date');
            }
            if (!Schema::hasColumn('employees', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('contract_start_date');
            }
            if (!Schema::hasColumn('employees', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('basic_salary');
            }
            if (!Schema::hasColumn('employees', 'bank_branch')) {
                $table->string('bank_branch')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('employees', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_branch');
            }
            if (!Schema::hasColumn('employees', 'bank_account_name')) {
                $table->string('bank_account_name')->nullable()->after('bank_account_number');
            }
        });

        // Widen status column to allow 'suspended'
        DB::statement("ALTER TABLE employees MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'active'");

        // 2. Leave Types table
        if (!Schema::hasTable('leave_types')) {
            Schema::create('leave_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->string('name');
                $table->string('code', 20)->nullable();
                $table->unsignedInteger('days_per_year')->default(21);
                $table->boolean('is_paid')->default(true);
                $table->boolean('requires_attachment')->default(false);
                $table->enum('gender_restriction', ['all', 'male', 'female'])->default('all');
                $table->boolean('carries_forward')->default(false);
                $table->unsignedInteger('max_carry_forward_days')->default(0);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->text('description')->nullable();
                $table->timestamps();
                $table->unique(['school_id', 'name']);
            });
        }

        // 3. Leave Requests table
        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
                $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
                $table->date('start_date');
                $table->date('end_date');
                $table->unsignedInteger('days_requested')->default(1);
                $table->text('reason')->nullable();
                $table->string('attachment_path')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();
                $table->index(['school_id', 'employee_id', 'status']);
                $table->index(['start_date', 'end_date']);
            });
        }

        // 4. Leave Allocations table
        if (!Schema::hasTable('leave_allocations')) {
            Schema::create('leave_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
                $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');
                $table->integer('year');
                $table->unsignedInteger('entitled_days')->default(21);
                $table->integer('carried_forward')->default(0);
                $table->integer('adjusted_days')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['school_id', 'employee_id', 'leave_type_id', 'year'], 'leave_alloc_emp_type_yr_unique');
            });
        }

        // 5. Payroll Periods table
        if (!Schema::hasTable('payroll_periods')) {
            Schema::create('payroll_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->string('name'); // e.g. "October 2026"
                $table->unsignedTinyInteger('month'); // 1-12
                $table->unsignedSmallInteger('year'); // 2026
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['draft', 'calculated', 'pending_approval', 'approved', 'finalized', 'paid', 'cancelled'])->default('draft');
                $table->decimal('total_basic_salary', 12, 2)->default(0.00);
                $table->decimal('total_allowances', 12, 2)->default(0.00);
                $table->decimal('total_gross', 12, 2)->default(0.00);
                $table->decimal('total_statutory_deductions', 12, 2)->default(0.00);
                $table->decimal('total_other_deductions', 12, 2)->default(0.00);
                $table->decimal('total_net_salary', 12, 2)->default(0.00);
                $table->decimal('total_employer_contributions', 12, 2)->default(0.00);
                $table->decimal('total_paid', 12, 2)->default(0.00);
                $table->unsignedInteger('employee_count')->default(0);
                $table->foreignId('prepared_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('finalized_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('finalized_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['school_id', 'year', 'month']);
            });
        }

        // 6. Extend payroll_records table
        Schema::table('payroll_records', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_records', 'payroll_period_id')) {
                $table->foreignId('payroll_period_id')->nullable()->after('employee_id')->constrained('payroll_periods')->onDelete('cascade');
            }
            if (!Schema::hasColumn('payroll_records', 'gross_salary')) {
                $table->decimal('gross_salary', 10, 2)->default(0.00)->after('allowances');
            }
            if (!Schema::hasColumn('payroll_records', 'paye')) {
                $table->decimal('paye', 10, 2)->default(0.00)->after('gross_salary');
            }
            if (!Schema::hasColumn('payroll_records', 'nssf_employee')) {
                $table->decimal('nssf_employee', 10, 2)->default(0.00)->after('paye');
            }
            if (!Schema::hasColumn('payroll_records', 'nssf_employer')) {
                $table->decimal('nssf_employer', 10, 2)->default(0.00)->after('nssf_employee');
            }
            if (!Schema::hasColumn('payroll_records', 'shif')) {
                $table->decimal('shif', 10, 2)->default(0.00)->after('nssf_employer');
            }
            if (!Schema::hasColumn('payroll_records', 'housing_levy_employee')) {
                $table->decimal('housing_levy_employee', 10, 2)->default(0.00)->after('shif');
            }
            if (!Schema::hasColumn('payroll_records', 'housing_levy_employer')) {
                $table->decimal('housing_levy_employer', 10, 2)->default(0.00)->after('housing_levy_employee');
            }
            if (!Schema::hasColumn('payroll_records', 'other_deductions')) {
                $table->decimal('other_deductions', 10, 2)->default(0.00)->after('housing_levy_employer');
            }
            if (!Schema::hasColumn('payroll_records', 'taxable_income')) {
                $table->decimal('taxable_income', 10, 2)->default(0.00)->after('other_deductions');
            }
            if (!Schema::hasColumn('payroll_records', 'personal_relief')) {
                $table->decimal('personal_relief', 10, 2)->default(2400.00)->after('taxable_income');
            }
            if (!Schema::hasColumn('payroll_records', 'insurance_relief')) {
                $table->decimal('insurance_relief', 10, 2)->default(0.00)->after('personal_relief');
            }
            if (!Schema::hasColumn('payroll_records', 'total_employer_contributions')) {
                $table->decimal('total_employer_contributions', 10, 2)->default(0.00)->after('insurance_relief');
            }
            if (!Schema::hasColumn('payroll_records', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0.00)->after('net_salary');
            }
            if (!Schema::hasColumn('payroll_records', 'payment_status')) {
                $table->string('payment_status', 30)->default('unpaid')->after('amount_paid');
            }
            if (!Schema::hasColumn('payroll_records', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('payroll_records', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('payroll_records', 'paid_by')) {
                $table->foreignId('paid_by')->nullable()->after('payment_reference')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('payroll_records', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('paid_by');
            }
            if (!Schema::hasColumn('payroll_records', 'allowances_breakdown')) {
                $table->json('allowances_breakdown')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('payroll_records', 'deductions_breakdown')) {
                $table->json('deductions_breakdown')->nullable()->after('allowances_breakdown');
            }
            if (!Schema::hasColumn('payroll_records', 'notes')) {
                $table->text('notes')->nullable()->after('deductions_breakdown');
            }
        });

        // Widen status column to allow all payroll record statuses
        DB::statement("ALTER TABLE payroll_records MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'draft'");

        // 7. Payroll Payments table
        if (!Schema::hasTable('payroll_payments')) {
            Schema::create('payroll_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('payroll_record_id')->constrained('payroll_records')->onDelete('cascade');
                $table->foreignId('payroll_period_id')->nullable()->constrained('payroll_periods')->onDelete('cascade');
                $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
                $table->decimal('amount', 10, 2);
                $table->date('payment_date');
                $table->string('payment_method'); // Bank Transfer, M-Pesa, Cheque, Cash
                $table->string('reference_number')->nullable();
                $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['school_id', 'employee_id']);
                $table->index(['school_id', 'payroll_period_id']);
            });
        }

        // 8. Employee Documents table
        if (!Schema::hasTable('employee_documents')) {
            Schema::create('employee_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
                $table->string('title');
                $table->string('document_type', 50)->default('other'); // contract, cv, id_copy, academic_cert, kra_pin, nhif_card, nssf_card, other
                $table->string('file_path');
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->index(['school_id', 'employee_id']);
            });
        }

        // 9. HR Audit Logs table
        if (!Schema::hasTable('hr_audit_logs')) {
            Schema::create('hr_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->string('action');
                $table->string('auditable_type');
                $table->unsignedBigInteger('auditable_id');
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();
                $table->index(['school_id', 'auditable_type', 'auditable_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_audit_logs');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('payroll_payments');
        Schema::dropIfExists('leave_allocations');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        // Do not drop columns on employees or payroll_records in down() to prevent data loss
    }
};
