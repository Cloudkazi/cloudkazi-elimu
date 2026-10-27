<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolFeature;
use App\Models\SchoolPopulationAllocation;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Teacher;
use App\Models\User;
use App\Services\MpesaDarajaService;
use App\Services\PopulationAllocationService;
use App\Services\SchoolFeatureService;
use App\Services\SchoolSubscriptionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSaaSTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;
    protected User $schoolAdmin;
    protected School $school;
    protected Package $package;
    protected Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        $superAdminRole = Role::firstOrCreate(
            ['slug' => Role::SUPER_ADMIN],
            ['name' => 'Super Admin', 'description' => 'System Administrator']
        );
        $adminRole = Role::firstOrCreate(
            ['slug' => Role::SCHOOL_ADMIN],
            ['name' => 'School Admin', 'description' => 'School Administrator']
        );

        // Retrieve or create super admin (role_id: 1, school_id: NULL)
        $this->superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@cloudkazi.com'],
            [
                'name'      => 'CloudKazi Super Administrator',
                'role_id'   => $superAdminRole->id,
                'password'  => 'password',
                'is_active' => true,
            ]
        );

        // Retrieve or create school
        $this->school = School::firstOrCreate(
            ['code' => 'AHS-001'],
            [
                'name'            => 'Alliance High School',
                'email'           => 'info@alliance.ac.ke',
                'phone'           => '0711000000',
                'curriculum_type' => 'CBC',
                'status'          => 'active',
            ]
        );

        // Retrieve standard package or create one
        $this->package = Package::firstOrCreate(
            ['code' => 'PRO-TIER'],
            [
                'name'              => 'Professional Tier',
                'activation_fee'    => 5000,
                'annual_fee'        => 65000,
                'max_students'      => 800,
                'max_teachers'      => 60,
                'max_parents'       => 800,
                'trial_days'        => 30,
                'grace_period_days' => 14,
                'is_public'         => true,
                'is_active'         => true,
            ]
        );

        // Ensure default features for package
        foreach (array_keys(Package::allFeatureKeys()) as $fk) {
            PackageFeature::firstOrCreate(
                ['package_id' => $this->package->id, 'feature_key' => $fk],
                ['enabled' => true]
            );
        }

        // Subscription
        $this->subscription = Subscription::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $this->school->id],
            [
                'package_id'     => $this->package->id,
                'plan_name'      => $this->package->name,
                'amount'         => $this->package->annual_fee,
                'annual_fee'     => $this->package->annual_fee,
                'activation_fee' => $this->package->activation_fee,
                'status'         => 'active',
                'payment_status' => 'successful',
                'start_date'     => now(),
                'end_date'       => now()->addYear(),
            ]
        );

        // School Admin
        $this->schoolAdmin = User::firstOrCreate(
            ['email' => 'admin@alliance.ac.ke'],
            [
                'name'      => 'Principal Alliance',
                'role_id'   => $adminRole->id,
                'school_id' => $this->school->id,
                'password'  => 'password',
                'is_active' => true,
            ]
        );
    }

    public function test_super_admin_can_access_dashboard_with_aggregate_data_only(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('CloudKazi Platform HQ');
        $response->assertSee('Total Schools');
        $response->assertSee('SaaS Revenue Collected');
        $response->assertSee('Recent Platform Activity');
    }

    public function test_staff_account_uses_entered_login_and_opens_its_assigned_portal(): void
    {
        $teacherRole = Role::firstOrCreate(
            ['slug' => Role::TEACHER],
            ['name' => 'Teacher']
        );
        Role::firstOrCreate(
            ['slug' => Role::TRANSPORT_OFFICER],
            ['name' => 'Transport Officer']
        );
        Role::firstOrCreate(
            ['slug' => Role::SCHOOL_ADMIN],
            ['name' => 'School Admin']
        );

        $rolePage = $this->actingAs($this->schoolAdmin)->get(route('school.users'));
        $rolePage->assertOk();
        $rolePage->assertSee('Teacher — Teacher portal');
        $rolePage->assertSee('Transport Officer — Transport portal');
        $rolePage->assertSee('School Admin — School dashboard');

        $response = $this->actingAs($this->schoolAdmin)->post(route('school.users.store'), [
            'name' => 'New Teacher',
            'email' => 'new.teacher@alliance.ac.ke',
            'phone' => '+254700000126',
            'role_id' => $teacherRole->id,
            'password' => 'TeacherSecret123!',
        ]);

        $response->assertRedirect();
        $teacherUser = User::where('email', 'new.teacher@alliance.ac.ke')->firstOrFail();
        $this->assertSame(Role::TEACHER, $teacherUser->role->slug);
        $this->assertTrue(Hash::check('TeacherSecret123!', $teacherUser->password));
        $this->assertDatabaseHas('teachers', ['user_id' => $teacherUser->id]);

        auth()->logout();

        $login = $this->post('/login', [
            'email' => 'new.teacher@alliance.ac.ke',
            'password' => 'TeacherSecret123!',
        ]);

        $login->assertRedirect(route('teacher.dashboard'));
        $this->assertAuthenticatedAs($teacherUser);
    }

    public function test_super_admin_cannot_access_individual_student_records(): void
    {
        $class = SchoolClass::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'Form 1A'],
            ['numeric_level' => 1, 'curriculum' => 'CBC']
        );

        $student = Student::create([
            'school_id'        => $this->school->id,
            'first_name'       => 'Secret',
            'last_name'        => 'Student',
            'admission_number' => 'SEC001' . rand(100, 999),
            'gender'           => 'Male',
            'dob'              => '2010-01-01',
            'admission_date'   => '2026-01-01',
            'school_class_id'  => $class->id,
            'curriculum'       => 'CBC',
            'status'           => 'active',
        ]);

        // Privacy check: User model canAccessStudent must return false for Super Admin
        $this->assertFalse($this->superAdmin->canAccessStudent($student));
    }

    public function test_super_admin_is_redirected_away_from_school_erp_routes(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/students');
        $response->assertRedirect(route('superadmin.dashboard'));
    }

    public function test_super_admin_cannot_switch_school_context(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('auth.switch-school'), [
            'school_id' => $this->school->id,
        ]);

        $response->assertRedirect(route('superadmin.dashboard'));
        $this->assertNull(session('active_school_id'));
    }

    public function test_super_admin_can_view_school_account_oversight(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.schools.account', $this->school->id));

        $response->assertStatus(200);
        $response->assertSee($this->school->name);
        $response->assertSee('Population');
        $response->assertSee('Module Access');
    }

    public function test_super_admin_can_manage_packages(): void
    {
        // Create Package
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.packages.store'), [
            'name'              => 'Premium Enterprise Tier',
            'code'              => 'PREM-ENT-' . rand(100, 999),
            'annual_fee'        => 120000,
            'activation_fee'    => 10000,
            'max_students'      => 1500,
            'max_teachers'      => 100,
            'trial_days'        => 14,
            'grace_period_days' => 7,
            'is_public'         => 1,
            'is_active'         => 1,
            'features'          => ['finance', 'mpesa', 'attendance', 'examinations'],
        ]);

        $response->assertRedirect(route('superadmin.packages'));

        $newPackage = Package::where('name', 'Premium Enterprise Tier')->latest()->firstOrFail();
        $this->assertTrue($newPackage->isFeatureEnabled('finance'));
        $this->assertFalse($newPackage->isFeatureEnabled('library'));

        // Toggle Package
        $this->actingAs($this->superAdmin)->post(route('superadmin.packages.toggle', $newPackage->id));
        $this->assertFalse($newPackage->fresh()->is_active);

        // Duplicate Package
        $this->actingAs($this->superAdmin)->post(route('superadmin.packages.duplicate', $newPackage->id));
        $this->assertDatabaseHas('packages', ['name' => 'Premium Enterprise Tier (Copy)']);
    }

    public function test_super_admin_can_allocate_population_override(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.schools.allocate-population', $this->school->id), [
                'student_limit' => 950,
                'teacher_limit' => 75,
                'parent_limit'  => 950,
                'reason'        => 'School added two Form 1 streams',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('school_population_allocations', [
            'school_id'     => $this->school->id,
            'student_limit' => 950,
        ]);

        // Effective limit service returns the override
        $effectiveLimit = PopulationAllocationService::getStudentLimit($this->school->id);
        $this->assertEquals(950, $effectiveLimit);
    }

    public function test_super_admin_can_override_and_reset_features(): void
    {
        // Override library feature to enabled
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.schools.feature.update', $this->school->id), [
                'feature_key' => 'library',
                'enabled'     => 1,
            ]);

        $response->assertSessionHas('success');
        $this->assertTrue(SchoolFeatureService::isEnabled($this->school->id, 'library'));

        // Reset to package default
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.schools.feature.reset', $this->school->id), [
                'feature_key' => 'library',
            ]);

        $this->assertDatabaseMissing('school_features', [
            'school_id'   => $this->school->id,
            'feature_key' => 'library',
        ]);
    }

    public function test_super_admin_can_suspend_and_reactivate_school(): void
    {
        // Suspend
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.schools.suspend', $this->school->id), [
                'reason' => 'Non-payment of annual invoice',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals('suspended', $this->school->fresh()->status);

        // Reactivate
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.schools.reactivate', $this->school->id));

        $response->assertSessionHas('success');
        $this->assertEquals('active', $this->school->fresh()->status);
    }

    public function test_super_admin_can_record_manual_saas_payment(): void
    {
        $ref = 'BNK-TEST-' . rand(10000, 99999);
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.schools.record-payment', $this->school->id), [
                'amount'         => 65000,
                'payment_method' => 'bank',
                'reference'      => $ref,
                'notes'          => 'Direct bank deposit verified via statement',
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('subscription_payments', [
            'school_id'      => $this->school->id,
            'reference'      => $ref,
            'payment_method' => 'bank',
            'status'         => 'successful',
        ]);
    }

    public function test_active_school_can_access_dashboard(): void
    {
        $this->school->update(['status' => 'active']);
        $this->subscription->update(['status' => 'active']);

        $response = $this->actingAs($this->schoolAdmin)->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_suspended_school_cannot_access_normal_dashboard_and_is_redirected(): void
    {
        $this->school->update(['status' => 'suspended']);
        $this->subscription->update(['status' => 'suspended']);

        // Normal browser request redirects to /account-suspended
        $response = $this->actingAs($this->schoolAdmin)->get(route('dashboard'));
        $response->assertRedirect(route('school.suspended'));

        // Suspended page itself is accessible
        $suspendedPage = $this->actingAs($this->schoolAdmin)->get(route('school.suspended'));
        $suspendedPage->assertStatus(403);
        $suspendedPage->assertSee('Account Suspended');

        // JSON requests receive 403 with error code
        $jsonResponse = $this->actingAs($this->schoolAdmin)->getJson(route('dashboard'));
        $jsonResponse->assertStatus(403);
        $jsonResponse->assertJsonFragment(['code' => 'SCHOOL_SUSPENDED']);
    }

    public function test_suspended_school_cannot_perform_post_or_create_actions(): void
    {
        $this->school->update(['status' => 'suspended']);
        $this->subscription->update(['status' => 'suspended']);

        $class = SchoolClass::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'Form 1A'],
            ['numeric_level' => 1, 'curriculum' => 'CBC']
        );

        $response = $this->actingAs($this->schoolAdmin)->post('/students', [
            'first_name' => 'Bypassed',
            'last_name'  => 'User',
            'gender'     => 'Male',
            'dob'        => '2010-01-01',
            'admission_date' => '2026-01-01',
            'school_class_id' => $class->id,
            'curriculum' => 'CBC',
            'guardian_name' => 'Guardian Test',
            'guardian_phone' => '0711223344',
            'guardian_email' => 'guardian@test.com',
            'guardian_relationship' => 'Parent',
        ]);

        $response->assertRedirect(route('school.suspended'));
        $this->assertDatabaseMissing('students', ['first_name' => 'Bypassed']);
    }

    public function test_disabled_feature_returns_403_and_cannot_be_accessed(): void
    {
        // Explicitly disable finance for this school
        SchoolFeatureService::setOverride($this->school->id, 'finance', false, $this->superAdmin->id);

        // Web request returns 403 feature disabled page
        $response = $this->actingAs($this->schoolAdmin)->get(route('finance.index'));
        $response->assertStatus(403);
        $response->assertSee('Feature Unavailable');

        // Direct POST to finance route is also blocked
        $postResponse = $this->actingAs($this->schoolAdmin)->post(route('finance.payment.store'), []);
        $postResponse->assertStatus(403);

        // JSON request receives 403 JSON
        $jsonResponse = $this->actingAs($this->schoolAdmin)->getJson(route('finance.index'));
        $jsonResponse->assertStatus(403);
        $jsonResponse->assertJsonFragment(['code' => 'FEATURE_DISABLED']);
    }

    public function test_enabled_feature_can_be_accessed(): void
    {
        $this->school->update(['status' => 'active']);
        $this->subscription->update(['status' => 'active']);
        SchoolFeatureService::setOverride($this->school->id, 'finance', true, $this->superAdmin->id);

        $response = $this->actingAs($this->schoolAdmin)->get(route('finance.index'));
        $response->assertStatus(200);
    }

    public function test_expired_subscription_is_enforced(): void
    {
        $this->school->update(['status' => 'expired']);
        $this->subscription->update(['status' => 'expired']);

        $response = $this->actingAs($this->schoolAdmin)->get(route('dashboard'));
        $response->assertRedirect(route('school.expired'));

        $expiredPage = $this->actingAs($this->schoolAdmin)->get(route('school.expired'));
        $expiredPage->assertStatus(403);
        $expiredPage->assertSee('Subscription Expired');
    }

    public function test_subscription_extension_updates_expiry(): void
    {
        $oldEnd = now()->subDays(5);
        $this->subscription->update([
            'end_date' => $oldEnd,
            'status'   => 'expired',
        ]);
        $this->school->update(['status' => 'expired']);

        // Super Admin extends by 12 months
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.schools.extend-subscription', $this->school->id), [
                'months' => 12,
            ]);

        $response->assertSessionHas('success');
        $this->subscription->refresh();
        $this->school->refresh();

        $this->assertEquals('active', $this->subscription->status);
        $this->assertEquals('active', $this->school->status);
        $this->assertTrue($this->subscription->end_date->isFuture());
    }

    public function test_limit_prevents_creation_beyond_allowed_amount(): void
    {
        // Allocate a limit of 1 student
        SchoolPopulationAllocation::create([
            'school_id'     => $this->school->id,
            'student_limit' => 1,
            'assigned_by'   => $this->superAdmin->id,
        ]);
        PopulationAllocationService::invalidateCache($this->school->id);

        $class = SchoolClass::firstOrCreate(
            ['school_id' => $this->school->id, 'name' => 'Form 1A'],
            ['numeric_level' => 1, 'curriculum' => 'CBC']
        );

        // Create 1 student (reaching the limit)
        Student::create([
            'school_id'        => $this->school->id,
            'first_name'       => 'Allowed',
            'last_name'        => 'Student',
            'admission_number' => 'ADM-LIM-001',
            'gender'           => 'Male',
            'dob'              => '2010-01-01',
            'admission_date'   => '2026-01-01',
            'school_class_id'  => $class->id,
            'curriculum'       => 'CBC',
            'status'           => 'active',
        ]);

        // Attempt to create a 2nd student via controller
        $response = $this->actingAs($this->schoolAdmin)->post('/students', [
            'admission_number' => 'ADM-LIM-002',
            'first_name'       => 'Exceeded',
            'last_name'        => 'Student',
            'gender'           => 'Male',
            'dob'              => '2010-01-01',
            'admission_date'   => '2026-01-01',
            'school_class_id'  => $class->id,
            'curriculum'       => 'CBC',
            'guardian_name'    => 'Parent Exceeded',
            'guardian_phone'   => '0799887766',
            'guardian_email'   => 'parent.exceeded@cloudkazi.com',
            'guardian_relationship' => 'Parent',
        ]);

        $response->assertSessionHasErrors('limit');
        $this->assertDatabaseMissing('students', ['admission_number' => 'ADM-LIM-002']);
    }

    public function test_school_a_cannot_access_school_b_data(): void
    {
        $schoolB = School::firstOrCreate(
            ['code' => 'SCH-B-001'],
            [
                'name'            => 'School B Academy',
                'email'           => 'admin@schoolb.com',
                'phone'           => '0722000000',
                'curriculum_type' => 'CBC',
                'status'          => 'active',
            ]
        );

        $classB = SchoolClass::firstOrCreate(
            ['school_id' => $schoolB->id, 'name' => 'Form 1B'],
            ['numeric_level' => 1, 'curriculum' => 'CBC']
        );

        $studentB = Student::create([
            'school_id'        => $schoolB->id,
            'first_name'       => 'Private',
            'last_name'        => 'SchoolB Student',
            'admission_number' => 'SCHB-ADM-999',
            'gender'           => 'Female',
            'dob'              => '2011-01-01',
            'admission_date'   => '2026-01-01',
            'school_class_id'  => $classB->id,
            'curriculum'       => 'CBC',
            'status'           => 'active',
        ]);

        // School Admin from School A accesses School B's student
        // TenantScope prevents School A user from viewing Student B (returns 404)
        $response = $this->actingAs($this->schoolAdmin)->get(route('students.show', $studentB->id));
        $response->assertStatus(404);
    }
}
