<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->decimal('activation_fee', 10, 2)->default(0);
            $table->decimal('annual_fee', 10, 2)->default(0);
            $table->unsignedInteger('max_students')->nullable()->comment('null = unlimited');
            $table->unsignedInteger('max_teachers')->nullable();
            $table->unsignedInteger('max_parents')->nullable();
            $table->unsignedInteger('max_admins')->nullable();
            $table->unsignedInteger('trial_days')->default(30);
            $table->unsignedInteger('grace_period_days')->default(14);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('package_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->string('feature_key', 60);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['package_id', 'feature_key']);
        });

        // Seed one legacy/standard package so existing subscriptions can reference it
        $packageId = DB::table('packages')->insertGetId([
            'name'             => 'Standard (Legacy)',
            'code'             => 'STD-LEGACY',
            'description'      => 'Auto-created legacy package for existing subscriptions.',
            'activation_fee'   => 0,
            'annual_fee'       => 30000,
            'max_students'     => 600,
            'max_teachers'     => 50,
            'max_parents'      => 600,
            'max_admins'       => 5,
            'trial_days'       => 30,
            'grace_period_days'=> 14,
            'is_public'        => false,
            'is_active'        => true,
            'sort_order'       => 0,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Give it all features
        $features = [
            'student_management','teacher_management','parent_portal','student_portal',
            'finance','mpesa','examinations','attendance','timetable',
            'library','transport','hostel','sms','reports','communication',
        ];
        foreach ($features as $fk) {
            DB::table('package_features')->insert([
                'package_id'  => $packageId,
                'feature_key' => $fk,
                'enabled'     => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('package_features');
        Schema::dropIfExists('packages');
    }
};
