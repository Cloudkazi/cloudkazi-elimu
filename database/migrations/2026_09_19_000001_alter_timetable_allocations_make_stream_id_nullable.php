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
        Schema::table('timetable_allocations', function (Blueprint $table) {
            $table->foreignId('stream_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timetable_allocations', function (Blueprint $table) {
            $table->foreignId('stream_id')->nullable(false)->change();
        });
    }
};
