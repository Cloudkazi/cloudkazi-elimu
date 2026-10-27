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
        if (Schema::hasTable('learning_materials') && !Schema::hasColumn('learning_materials', 'status')) {
            Schema::table('learning_materials', function (Blueprint $table) {
                $table->enum('status', ['published', 'draft', 'pending', 'unpublished', 'archived'])
                    ->default('published')
                    ->after('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('learning_materials') && Schema::hasColumn('learning_materials', 'status')) {
            Schema::table('learning_materials', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
