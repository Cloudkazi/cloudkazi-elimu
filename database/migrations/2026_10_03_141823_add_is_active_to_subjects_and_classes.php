<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add is_active (soft-deactivation) columns to subjects and school_classes.
     */
    public function up(): void
    {
        if (Schema::hasTable('subjects') && !Schema::hasColumn('subjects', 'is_active')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('lessons_per_week');
            });
        }

        if (Schema::hasTable('school_classes') && !Schema::hasColumn('school_classes', 'is_active')) {
            Schema::table('school_classes', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('numeric_level');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'is_active')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasTable('school_classes') && Schema::hasColumn('school_classes', 'is_active')) {
            Schema::table('school_classes', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
