<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Library
        Schema::create('book_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('book_categories')->onDelete('set null');
            $table->string('title');
            $table->string('author');
            $table->string('isbn')->nullable();
            $table->string('publisher')->nullable();
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->string('shelf_location')->nullable();
            $table->timestamps();
        });

        Schema::create('book_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['issued', 'returned', 'overdue', 'lost'])->default('issued');
            $table->decimal('fine_amount', 8, 2)->default(0.00);
            $table->boolean('fine_paid')->default(false);
            $table->timestamps();
        });

        // Transport
        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('route_name'); // e.g. Route 1 - Westlands / Parklands
            $table->string('vehicle_registration'); // e.g. KDA 123X
            $table->string('driver_name');
            $table->string('driver_phone');
            $table->decimal('termly_fare', 10, 2)->default(12000.00);
            $table->timestamps();
        });

        Schema::create('student_transports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('transport_route_id')->constrained('transport_routes')->onDelete('cascade');
            $table->string('pickup_point');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Hostel
        Schema::create('dormitories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name'); // Kilimanjaro, Mount Kenya, Mara
            $table->enum('gender', ['Male', 'Female'])->default('Male');
            $table->integer('capacity')->default(100);
            $table->string('warden_name')->nullable();
            $table->string('warden_phone')->nullable();
            $table->timestamps();
        });

        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('dormitory_id')->constrained('dormitories')->onDelete('cascade');
            $table->string('room_number'); // Room 1, Cubicle 4
            $table->integer('total_beds')->default(4);
            $table->timestamps();
        });

        Schema::create('student_hostels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('dormitory_id')->constrained('dormitories')->onDelete('cascade');
            $table->foreignId('hostel_room_id')->nullable()->constrained('hostel_rooms')->onDelete('set null');
            $table->string('bed_number')->nullable();
            $table->timestamps();
        });

        // Communication
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->enum('target_audience', ['All', 'Teachers', 'Students', 'Parents'])->default('All');
            $table->date('published_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('recipient_phone');
            $table->string('recipient_name')->nullable();
            $table->text('message');
            $table->enum('status', ['queued', 'sent', 'failed'])->default('sent');
            $table->string('message_type')->default('General'); // Fees, Results, Attendance, Emergency
            $table->decimal('cost', 5, 2)->default(1.00);
            $table->timestamps();
        });

        // Discipline
        Schema::create('discipline_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('incident_date');
            $table->string('title');
            $table->text('description');
            $table->enum('severity', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->string('action_taken')->nullable(); // Verbal Warning, Detention, Suspension
            $table->boolean('parent_notified')->default(false);
            $table->enum('status', ['open', 'in_review', 'resolved'])->default('resolved');
            $table->foreignId('reported_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Inventory & Assets
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('asset_tag')->unique(); // e.g. EQ-2026-001
            $table->enum('category', ['Furniture', 'IT & Computers', 'Lab Equipment', 'Sports', 'Musical', 'Vehicles'])->default('Furniture');
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 10, 2)->default(0.00);
            $table->enum('condition', ['New', 'Good', 'Fair', 'Needs Repair', 'Condemned'])->default('Good');
            $table->string('location')->nullable(); // Science Lab, Staffroom, Library
            $table->timestamps();
        });

        // HR & Payroll
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('employee_no')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('id_number')->nullable();
            $table->string('kra_pin')->nullable();
            $table->string('nssf_no')->nullable();
            $table->string('nhif_no')->nullable();
            $table->string('designation'); // Bursar, Security, Cook, Driver, Secretary, Lab Technician
            $table->string('department')->nullable();
            $table->date('hire_date');
            $table->decimal('basic_salary', 10, 2)->default(0.00);
            $table->enum('status', ['active', 'on_leave', 'terminated'])->default('active');
            $table->timestamps();
        });

        Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('allowances', 10, 2)->default(0.00);
            $table->decimal('deductions', 10, 2)->default(0.00); // PAYE, NSSF, SHIF, Housing Levy
            $table->decimal('net_salary', 10, 2);
            $table->enum('status', ['draft', 'processed', 'paid'])->default('paid');
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });

        // Learning Materials
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('type', ['notes', 'assignment', 'past_paper', 'revision_material'])->default('notes');
            $table->date('due_date')->nullable(); // If assignment
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_materials');
        Schema::dropIfExists('payroll_records');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('discipline_incidents');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('student_hostels');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('dormitories');
        Schema::dropIfExists('student_transports');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('book_issues');
        Schema::dropIfExists('books');
        Schema::dropIfExists('book_categories');
    }
};
