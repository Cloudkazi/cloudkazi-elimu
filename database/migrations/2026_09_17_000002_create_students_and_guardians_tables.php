<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('admission_number'); // unique per school
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_names')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->default('Male');
            $table->date('dob');
            $table->date('admission_date');
            $table->foreignId('school_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->foreignId('stream_id')->nullable()->constrained('streams')->onDelete('set null');
            $table->enum('curriculum', ['CBC', '8-4-4'])->default('CBC');
            $table->enum('pathway', ['general', 'STEM', 'Social Sciences', 'Arts & Sports'])->default('general');
            $table->string('nemis_upi')->nullable(); // NEMIS or CBC UPI
            $table->string('assessment_number')->nullable(); // KNEC Assessment No
            $table->string('birth_certificate_no')->nullable();
            $table->string('kcpe_score')->nullable();
            $table->string('passport_photo')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->string('blood_group')->nullable();
            $table->enum('status', ['active', 'transferred', 'suspended', 'alumni'])->default('active');
            $table->timestamps();

            $table->unique(['school_id', 'admission_number']);
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('relationship')->default('Parent'); // Father, Mother, Guardian, Sponsor
            $table->string('occupation')->nullable();
            $table->string('id_number')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();
        });

        Schema::create('student_guardian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('guardian_id')->constrained('guardians')->onDelete('cascade');
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
        });

        Schema::create('student_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('applicant_name');
            $table->enum('gender', ['Male', 'Female'])->default('Male');
            $table->date('dob');
            $table->foreignId('target_class_id')->constrained('school_classes')->onDelete('cascade');
            $table->string('parent_name');
            $table->string('parent_phone');
            $table->string('parent_email')->nullable();
            $table->string('previous_school')->nullable();
            $table->string('previous_marks')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_admissions');
        Schema::dropIfExists('student_guardian');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('students');
    }
};
