<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\School;
use App\Models\Subscription;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicSchoolRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Package::firstOrCreate(
            ['code' => 'TEST-PUBLIC'],
            [
                'name' => 'Test Public Package',
                'activation_fee' => 0,
                'annual_fee' => 65000,
                'trial_days' => 14,
                'is_public' => true,
                'is_active' => true,
            ]
        );
    }

    public function test_any_user_can_register_a_school_and_it_is_visible_to_superadmin(): void
    {
        $package = Package::where('is_active', true)->where('is_public', true)->firstOrFail();

        $response = $this->post('/school/register', [
            'name' => 'Acme Academy',
            'code' => 'ACM-001',
            'email' => 'info@acme.ac.ke',
            'phone' => '+254700000123',
            'curriculum_type' => 'CBC',
            'county' => 'Nairobi',
            'motto' => 'Learn and Lead',
            'package_id' => $package->id,
            'admin_name' => 'Jane Admin',
            'admin_email' => 'admin@acme.ac.ke',
            'admin_phone' => '+254700000124',
            'admin_password' => 'Secret123!',
        ]);

        $school = School::where('email', 'info@acme.ac.ke')->first();

        $response->assertRedirect(route('school.payment', ['school' => $school?->id ?? 1]));
        $this->assertDatabaseHas('schools', ['email' => 'info@acme.ac.ke', 'name' => 'Acme Academy']);
        $this->assertDatabaseHas('users', ['email' => 'admin@acme.ac.ke']);

        $admin = User::where('email', 'admin@acme.ac.ke')->firstOrFail();
        $this->assertSame(Role::SCHOOL_ADMIN, $admin->role->slug);
        $this->assertTrue(Hash::check('Secret123!', $admin->password));

        $loginResponse = $this->post('/login', [
            'email' => 'admin@acme.ac.ke',
            'password' => 'Secret123!',
        ]);
        $loginResponse->assertRedirect(route('school.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $school = $school ?? School::where('email', 'info@acme.ac.ke')->firstOrFail();
        $this->assertDatabaseHas('subscriptions', [
            'school_id' => $school->id,
            'package_id' => $package->id,
            'plan_name' => $package->name,
            'amount' => $package->annual_fee,
        ]);
    }

    public function test_school_registration_creates_missing_school_admin_role_before_provisioning(): void
    {
        Role::where('slug', Role::SCHOOL_ADMIN)->update(['slug' => 'missing-school-admin']);

        $response = $this->post('/school/register', [
            'name' => 'Missing Role Academy',
            'code' => 'MRA-001',
            'email' => 'info@missingrole.ac.ke',
            'phone' => '+254700000125',
            'curriculum_type' => 'CBC',
            'admin_name' => 'Jane Admin',
            'admin_email' => 'admin@missingrole.ac.ke',
            'admin_password' => 'Secret123!',
            'package_id' => \App\Models\Package::where('is_active', true)->where('is_public', true)->value('id'),
        ]);

        $school = School::where('email', 'info@missingrole.ac.ke')->firstOrFail();
        $adminRole = Role::where('slug', Role::SCHOOL_ADMIN)->firstOrFail();

        $response->assertRedirect(route('school.payment', ['school' => $school->id]));
        $this->assertDatabaseHas('users', [
            'email' => 'admin@missingrole.ac.ke',
            'school_id' => $school->id,
            'role_id' => $adminRole->id,
        ]);
    }

    public function test_user_can_resume_an_unpaid_school_registration_by_searching_the_school(): void
    {
        $school = School::create([
            'name' => 'Bright Star Academy',
            'code' => 'BSA-001',
            'email' => 'admin@brightstar.ac.ke',
            'phone' => '+254700222333',
            'curriculum_type' => 'CBC',
            'county' => 'Nairobi',
            'motto' => 'Bright futures',
            'status' => 'active',
        ]);

        Subscription::create([
            'school_id' => $school->id,
            'plan_name' => 'Standard (Up to 600 Students)',
            'amount' => 65000,
            'status' => 'trial',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'sms_balance' => 2000,
            'storage_limit_mb' => 5120,
        ]);

        $response = $this->post('/school/register/search', [
            'search_email' => 'admin@brightstar.ac.ke',
        ]);

        $response->assertRedirect(route('school.payment', ['school' => $school->id]));
    }
}
