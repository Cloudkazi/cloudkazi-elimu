<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_demo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_demo')->default(false)->after('is_active');
            });
        }

        DB::table('users')
            ->where('email', 'like', '%@demo.com')
            ->update(['is_demo' => true, 'is_active' => false]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_demo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_demo');
            });
        }
    }
};
