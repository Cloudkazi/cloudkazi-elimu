<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds is_staff classification flag to roles table.
     * Staff roles appear in the "Add Staff" dropdown.
     * Non-staff roles (super-admin, school-admin, student, parent) do not.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_staff')->default(false)->after('description')
                  ->comment('True = appears in Add Staff dropdown. False = system/student/parent role.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_staff');
        });
    }
};
