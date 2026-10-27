<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolFeature;
use App\Models\SchoolPopulationAllocation;
use App\Models\SchoolSetting;
use App\Models\SmsLog;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Teacher;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\PopulationAllocationService;
use App\Services\SchoolFeatureService;
use App\Services\SchoolSubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    // ─── SECURITY GUARD ─────────────────────────────────────────────────────────
    protected function requireSuperAdmin(): void
    {
        if (!Auth::user()?->isSuperAdmin()) {
            abort(403, 'This area is restricted to platform administrators.');
        }
    }

    // ─── DASHBOARD ───────────────────────────────────────────────────────────────
    public function dashboard()
    {
        $this->requireSuperAdmin();

        // Platform-level aggregate metrics only — no individual school ERP records
        $totalSchools     = School::count();
        $activeSchools    = School::where('status', 'active')->count();
        $trialSchools     = School::where('status', 'trial')->count();
        $pendingSchools   = School::where('status', 'pending')->count();
        $gracePeriodSchools = School::where('status', 'grace_period')->count();
        $expiredSchools   = School::where('status', 'expired')->count();
        $suspendedSchools = School::where('status', 'suspended')->count();
        $cancelledSchools = School::where('status', 'cancelled')->count();

        $activeSubscriptions = Subscription::withoutGlobalScopes()->where('status', 'active')->count();
        $totalRevenue        = SubscriptionPayment::where('status', 'successful')->sum('amount');
        $outstanding         = Subscription::withoutGlobalScopes()
            ->whereIn('status', ['pending', 'trial'])
            ->where('payment_status', '!=', 'successful')
            ->count();

        // Aggregate counts only
        $totalStudents  = Student::withoutGlobalScopes()->count();
        $totalTeachers  = Teacher::withoutGlobalScopes()->count();

        // Schools expiring soon
        $expiringSoon = Subscription::withoutGlobalScopes()
            ->with('school')
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<=', now()->addDays(60))
            ->orderBy('end_date')
            ->get();

        // Recent platform audit activity
        $recentActivity = AuditLog::with('user')
            ->whereNotNull('school_id')
            ->orWhereIn('module', ['package', 'school', 'subscription', 'billing'])
            ->latest()
            ->take(10)
            ->get();

        // Chart 1: Package subscription distribution
        $packages = Package::withCount(['subscriptions' => function ($q) {
            $q->withoutGlobalScopes()->where('status', 'active');
        }])->orderBy('sort_order')->get();
        $pkgLabels = $packages->pluck('name')->toArray();
        $pkgCounts = $packages->pluck('subscriptions_count')->toArray();

        // Chart 2: Tenant lifecycle breakdown
        $statusLabels = ['Active', 'Trial', 'Grace Period', 'Expired', 'Suspended'];
        $statusCounts = [$activeSchools, $trialSchools, $gracePeriodSchools, $expiredSchools, $suspendedSchools];

        return view('superadmin.dashboard', compact(
            'totalSchools', 'activeSchools', 'trialSchools', 'pendingSchools',
            'gracePeriodSchools', 'expiredSchools', 'suspendedSchools', 'cancelledSchools',
            'activeSubscriptions', 'totalRevenue', 'outstanding',
            'totalStudents', 'totalTeachers',
            'expiringSoon', 'recentActivity',
            'pkgLabels', 'pkgCounts', 'statusLabels', 'statusCounts'
        ));
    }

    // ─── SCHOOLS ─────────────────────────────────────────────────────────────────
    public function schools(Request $request)
    {
        $this->requireSuperAdmin();

        $query = School::with(['subscription.package'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        $schools = $query->paginate(15)->withQueryString();

        return view('superadmin.schools.index', compact('schools'));
    }

    public function schoolAccount(School $school)
    {
        $this->requireSuperAdmin();

        $subscription = Subscription::withoutGlobalScopes()
            ->with('package')
            ->where('school_id', $school->id)
            ->latest()
            ->first();

        // Aggregate usage — no individual records
        $usage = PopulationAllocationService::getUsageSummary($school);

        $packages      = Package::where('is_active', true)->orderBy('sort_order')->get();
        $auditLogs     = AuditLog::where('school_id', $school->id)->latest()->take(20)->get();
        $payments      = SubscriptionPayment::where('school_id', $school->id)->with('package')->latest()->take(10)->get();
        $allFeatures   = Package::allFeatureKeys();
        $featureStates = [];
        foreach (array_keys($allFeatures) as $key) {
            $featureStates[$key] = SchoolFeatureService::getFeatureState($school->id, $key);
        }

        return view('superadmin.schools.account', compact(
            'school', 'subscription', 'usage', 'packages',
            'auditLogs', 'payments', 'allFeatures', 'featureStates'
        ));
    }

    public function createSchool()
    {
        $this->requireSuperAdmin();
        $packages = Package::where('is_active', true)->orderBy('sort_order')->get();
        return view('superadmin.schools.create', compact('packages'));
    }

    public function storeSchool(Request $request)
    {
        $this->requireSuperAdmin();
        return $this->provisionSchool($request, false);
    }

    // ─── SCHOOL LIFECYCLE ACTIONS ────────────────────────────────────────────────
    public function suspendSchool(Request $request, School $school)
    {
        $this->requireSuperAdmin();

        $request->validate(['reason' => 'nullable|string|max:500']);

        $subscription = Subscription::withoutGlobalScopes()->where('school_id', $school->id)->latest()->first();
        if ($subscription) {
            SchoolSubscriptionService::suspendSubscription($subscription, $request->reason);
        } else {
            $school->update(['status' => 'suspended']);
            SchoolSubscriptionService::invalidateCache($school->id);
        }

        AuditLogService::log(
            'school.suspended', 'school',
            "Suspended school: {$school->name}. Reason: " . ($request->reason ?? 'Not specified'),
            $school->id,
            ['status' => $school->status],
            ['status' => 'suspended']
        );

        return back()->with('success', "School '{$school->name}' has been suspended.");
    }

    public function reactivateSchool(School $school)
    {
        $this->requireSuperAdmin();

        $subscription = Subscription::withoutGlobalScopes()->where('school_id', $school->id)->latest()->first();
        if ($subscription) {
            SchoolSubscriptionService::reactivateSubscription($subscription);
        } else {
            $school->update(['status' => 'active']);
            SchoolSubscriptionService::invalidateCache($school->id);
        }

        AuditLogService::log('school.reactivated', 'school', "Reactivated school: {$school->name}", $school->id);

        return back()->with('success', "School '{$school->name}' has been reactivated.");
    }

    public function cancelSchool(Request $request, School $school)
    {
        $this->requireSuperAdmin();
        $request->validate(['reason' => 'nullable|string|max:500']);

        $subscription = Subscription::withoutGlobalScopes()->where('school_id', $school->id)->latest()->first();
        if ($subscription) {
            SchoolSubscriptionService::cancelSubscription($subscription, $request->reason);
        } else {
            $school->update(['status' => 'cancelled']);
            SchoolSubscriptionService::invalidateCache($school->id);
        }

        AuditLogService::log('school.cancelled', 'school', "Cancelled school: {$school->name}", $school->id);
        return back()->with('success', "School '{$school->name}' subscription has been cancelled. Data is preserved.");
    }

    public function changePackage(Request $request, School $school)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $newPackage   = Package::findOrFail($validated['package_id']);
        $subscription = Subscription::withoutGlobalScopes()->where('school_id', $school->id)->latest()->first();

        if (!$subscription) {
            $subscription = SchoolSubscriptionService::createSubscription($school, $newPackage);
        } else {
            $oldPackageName = $subscription->package?->name ?? 'Unknown';
            SchoolSubscriptionService::changePackage($subscription, $newPackage);
            AuditLogService::log(
                'school.package_changed', 'school',
                "Changed package for {$school->name} from {$oldPackageName} to {$newPackage->name}",
                $school->id,
                ['package' => $oldPackageName],
                ['package' => $newPackage->name]
            );
        }

        return back()->with('success', "Package changed to '{$newPackage->name}' for {$school->name}.");
    }

    public function extendSubscription(Request $request, School $school)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'months' => 'required|integer|min:1|max:36',
        ]);

        $subscription = Subscription::withoutGlobalScopes()->where('school_id', $school->id)->latest()->first();
        if (!$subscription) {
            return back()->withErrors(['error' => 'No subscription found for this school.']);
        }

        SchoolSubscriptionService::extendSubscription($subscription, $validated['months']);

        AuditLogService::log(
            'subscription.extended', 'subscription',
            "Extended subscription for {$school->name} by {$validated['months']} months",
            $school->id
        );

        return back()->with('success', "Subscription extended by {$validated['months']} month(s) for {$school->name}.");
    }

    // ─── POPULATION ALLOCATION ────────────────────────────────────────────────────
    public function allocatePopulation(Request $request, School $school)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'student_limit' => 'nullable|integer|min:0',
            'teacher_limit' => 'nullable|integer|min:0',
            'parent_limit'  => 'nullable|integer|min:0',
            'admin_limit'   => 'nullable|integer|min:0',
            'reason'        => 'nullable|string|max:500',
        ]);

        SchoolPopulationAllocation::create([
            'school_id'     => $school->id,
            'student_limit' => $validated['student_limit'] ?? null,
            'teacher_limit' => $validated['teacher_limit'] ?? null,
            'parent_limit'  => $validated['parent_limit'] ?? null,
            'admin_limit'   => $validated['admin_limit'] ?? null,
            'reason'        => $validated['reason'] ?? null,
            'assigned_by'   => Auth::id(),
        ]);

        PopulationAllocationService::invalidateCache($school->id);

        AuditLogService::log(
            'population.updated', 'school',
            "Updated population allocation for {$school->name}",
            $school->id,
            [],
            $validated
        );

        return back()->with('success', "Population allocation updated for {$school->name}.");
    }

    // ─── FEATURE MANAGEMENT ───────────────────────────────────────────────────────
    public function updateFeature(Request $request, School $school)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'feature_key' => 'required|string',
            'enabled'     => 'required|boolean',
            'expires_at'  => 'nullable|date',
        ]);

        $action = $validated['enabled'] ? 'feature.enabled' : 'feature.disabled';

        SchoolFeatureService::setOverride(
            $school->id,
            $validated['feature_key'],
            $validated['enabled'],
            Auth::id(),
            $validated['expires_at'] ?? null
        );

        AuditLogService::log(
            $action, 'school',
            ($validated['enabled'] ? 'Enabled' : 'Disabled') . " feature '{$validated['feature_key']}' for {$school->name}",
            $school->id
        );

        return back()->with('success', "Feature '{$validated['feature_key']}' updated for {$school->name}.");
    }

    public function resetFeature(Request $request, School $school)
    {
        $this->requireSuperAdmin();
        $validated = $request->validate(['feature_key' => 'required|string']);

        SchoolFeatureService::resetToPackage($school->id, $validated['feature_key']);
        return back()->with('success', "Feature '{$validated['feature_key']}' reset to package default.");
    }

    // ─── PACKAGES ─────────────────────────────────────────────────────────────────
    public function packages()
    {
        $this->requireSuperAdmin();
        $packages    = Package::with('features')->orderBy('sort_order')->get();
        $allFeatures = Package::allFeatureKeys();
        return view('superadmin.packages.index', compact('packages', 'allFeatures'));
    }

    public function createPackage()
    {
        $this->requireSuperAdmin();
        $allFeatures = Package::allFeatureKeys();
        return view('superadmin.packages.create', compact('allFeatures'));
    }

    public function storePackage(Request $request)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'code'              => 'required|string|max:50|unique:packages,code',
            'description'       => 'nullable|string',
            'activation_fee'    => 'required|numeric|min:0',
            'annual_fee'        => 'required|numeric|min:0',
            'max_students'      => 'nullable|integer|min:0',
            'max_teachers'      => 'nullable|integer|min:0',
            'max_parents'       => 'nullable|integer|min:0',
            'max_admins'        => 'nullable|integer|min:0',
            'trial_days'        => 'required|integer|min:0',
            'grace_period_days' => 'required|integer|min:0',
            'is_public'         => 'nullable|boolean',
            'is_active'         => 'nullable|boolean',
            'sort_order'        => 'nullable|integer',
            'features'          => 'nullable|array',
        ]);

        $package = Package::create([
            'name'              => $validated['name'],
            'code'              => strtoupper($validated['code']),
            'description'       => $validated['description'] ?? null,
            'activation_fee'    => $validated['activation_fee'],
            'annual_fee'        => $validated['annual_fee'],
            'max_students'      => $validated['max_students'] ?? null,
            'max_teachers'      => $validated['max_teachers'] ?? null,
            'max_parents'       => $validated['max_parents'] ?? null,
            'max_admins'        => $validated['max_admins'] ?? null,
            'trial_days'        => $validated['trial_days'],
            'grace_period_days' => $validated['grace_period_days'],
            'is_public'         => (bool) ($validated['is_public'] ?? false),
            'is_active'         => (bool) ($validated['is_active'] ?? true),
            'sort_order'        => $validated['sort_order'] ?? 0,
        ]);

        // Save features
        $enabledFeatures = $validated['features'] ?? [];
        foreach (array_keys(Package::allFeatureKeys()) as $fk) {
            PackageFeature::create([
                'package_id'  => $package->id,
                'feature_key' => $fk,
                'enabled'     => in_array($fk, $enabledFeatures),
            ]);
        }

        AuditLogService::log('package.created', 'package', "Created package: {$package->name}", null, [], $package->toArray());

        return redirect()->route('superadmin.packages')->with('success', "Package '{$package->name}' created successfully.");
    }

    public function editPackage(Package $package)
    {
        $this->requireSuperAdmin();
        $allFeatures = Package::allFeatureKeys();
        $package->load('features');
        return view('superadmin.packages.edit', compact('package', 'allFeatures'));
    }

    public function updatePackage(Request $request, Package $package)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'description'       => 'nullable|string',
            'activation_fee'    => 'required|numeric|min:0',
            'annual_fee'        => 'required|numeric|min:0',
            'max_students'      => 'nullable|integer|min:0',
            'max_teachers'      => 'nullable|integer|min:0',
            'max_parents'       => 'nullable|integer|min:0',
            'max_admins'        => 'nullable|integer|min:0',
            'trial_days'        => 'required|integer|min:0',
            'grace_period_days' => 'required|integer|min:0',
            'is_public'         => 'nullable|boolean',
            'is_active'         => 'nullable|boolean',
            'sort_order'        => 'nullable|integer',
            'features'          => 'nullable|array',
        ]);

        $old = $package->toArray();
        $package->update($validated);

        // Update features
        $enabledFeatures = $validated['features'] ?? [];
        foreach (array_keys(Package::allFeatureKeys()) as $fk) {
            PackageFeature::updateOrCreate(
                ['package_id' => $package->id, 'feature_key' => $fk],
                ['enabled' => in_array($fk, $enabledFeatures)]
            );
        }

        AuditLogService::log('package.updated', 'package', "Updated package: {$package->name}", null, $old, $package->fresh()->toArray());

        return redirect()->route('superadmin.packages')->with('success', "Package '{$package->name}' updated.");
    }

    public function togglePackage(Package $package)
    {
        $this->requireSuperAdmin();
        $package->update(['is_active' => !$package->is_active]);

        $action = $package->is_active ? 'package.activated' : 'package.deactivated';
        AuditLogService::log($action, 'package', "Toggled package '{$package->name}' active state");

        return back()->with('success', "Package '{$package->name}' " . ($package->is_active ? 'activated' : 'deactivated') . ".");
    }

    public function duplicatePackage(Package $package)
    {
        $this->requireSuperAdmin();
        $package->load('features');

        $new = $package->replicate();
        $new->name     = $package->name . ' (Copy)';
        $new->code     = strtoupper(Str::slug($package->name . '-copy-' . now()->timestamp, '-'));
        $new->is_active = false;
        $new->save();

        foreach ($package->features as $feat) {
            PackageFeature::create([
                'package_id'  => $new->id,
                'feature_key' => $feat->feature_key,
                'enabled'     => $feat->enabled,
            ]);
        }

        return redirect()->route('superadmin.packages')->with('success', "Package duplicated as '{$new->name}'.");
    }

    // ─── SUBSCRIPTIONS ────────────────────────────────────────────────────────────
    public function subscriptions(Request $request)
    {
        $this->requireSuperAdmin();

        $query = Subscription::withoutGlobalScopes()
            ->with(['school', 'package'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->paginate(20)->withQueryString();
        return view('superadmin.subscriptions', compact('subscriptions'));
    }

    // ─── BILLING ─────────────────────────────────────────────────────────────────
    public function billing(Request $request)
    {
        $this->requireSuperAdmin();

        $query = SubscriptionPayment::with(['school', 'package'])->latest();

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $payments = $query->paginate(20)->withQueryString();
        $schools  = School::orderBy('name')->get(['id', 'name']);

        return view('superadmin.billing', compact('payments', 'schools'));
    }

    public function recordManualPayment(Request $request, School $school)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|in:mpesa,bank,paystack,manual',
            'reference'      => 'required|string|max:100|unique:subscription_payments,reference',
            'notes'          => 'nullable|string|max:300',
        ]);

        $subscription = Subscription::withoutGlobalScopes()->where('school_id', $school->id)->latest()->first();

        $payment = SubscriptionPayment::create([
            'school_id'       => $school->id,
            'subscription_id' => $subscription?->id,
            'package_id'      => $subscription?->package_id,
            'amount'          => $validated['amount'],
            'payment_method'  => $validated['payment_method'],
            'reference'       => $validated['reference'],
            'status'          => 'successful',
            'paid_at'         => now(),
            'recorded_by'     => Auth::id(),
            'metadata'        => ['notes' => $validated['notes'] ?? null],
        ]);

        if ($subscription && $subscription->payment_status !== 'successful') {
            $subscription->update(['payment_status' => 'successful']);
        }

        AuditLogService::log(
            'billing.created', 'billing',
            "Recorded manual payment KES {$validated['amount']} for {$school->name}",
            $school->id
        );

        return back()->with('success', "Payment KES " . number_format($validated['amount']) . " recorded for {$school->name}.");
    }

    // ─── USAGE ────────────────────────────────────────────────────────────────────
    public function usage()
    {
        $this->requireSuperAdmin();

        // Return aggregate usage per school — no individual records exposed
        $schools = School::with(['subscription.package'])->get();
        $usageData = $schools->map(function (School $school) {
            $usage = PopulationAllocationService::getUsageSummary($school);
            return [
                'school'       => $school,
                'subscription' => $school->subscription,
                'usage'        => $usage,
            ];
        });

        return view('superadmin.usage', compact('usageData'));
    }

    // ─── AUDIT LOGS ───────────────────────────────────────────────────────────────
    public function auditLogs(Request $request)
    {
        $this->requireSuperAdmin();

        $query = AuditLog::with(['user', 'school'])->latest();

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs    = $query->paginate(25)->withQueryString();
        $schools = School::orderBy('name')->get(['id', 'name']);
        $modules = ['platform', 'school', 'package', 'subscription', 'billing', 'feature', 'population'];

        return view('superadmin.audit_logs', compact('logs', 'schools', 'modules'));
    }

    // ─── PLATFORM SETTINGS ────────────────────────────────────────────────────────
    public function platformSettings()
    {
        $this->requireSuperAdmin();
        $settings = \App\Models\PlatformSetting::pluck('value', 'key')->toArray();
        return view('superadmin.platform_settings', compact('settings'));
    }

    public function updatePlatformSettings(Request $request)
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'platform_name'        => 'nullable|string|max:100',
            'support_email'        => 'nullable|email|max:100',
            'support_phone'        => 'nullable|string|max:20',
            'default_currency'     => 'nullable|string|max:5',
            'default_trial_days'   => 'nullable|integer|min:0',
            'default_grace_days'   => 'nullable|integer|min:0',
        ]);

        foreach ($validated as $key => $value) {
            \App\Models\PlatformSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'general']
            );
        }

        AuditLogService::log('settings.updated', 'platform', 'Updated platform settings');

        return back()->with('success', 'Platform settings updated.');
    }

    // ─── USERS ────────────────────────────────────────────────────────────────────
    public function users()
    {
        $this->requireSuperAdmin();

        // Only platform-level users: Super Admin + School Admins
        $users = User::withoutGlobalScopes()
            ->with(['role', 'school'])
            ->whereHas('role', function ($q) {
                $q->whereIn('slug', [Role::SUPER_ADMIN, Role::SCHOOL_ADMIN]);
            })
            ->latest()
            ->paginate(20);

        return view('superadmin.users', compact('users'));
    }

    // ─── PUBLIC REGISTRATION ROUTES ───────────────────────────────────────────────
    public function showPublicSchoolRegistration()
    {
        $packages = Package::where('is_active', true)->where('is_public', true)->orderBy('sort_order')->get();
        return view('school.register', compact('packages'));
    }

    public function searchPublicSchoolRegistration(Request $request)
    {
        $data = $request->validate([
            'search_email' => 'nullable|email',
            'search_code'  => 'nullable|string|max:50',
        ]);

        $school = School::query()
            ->where(function ($query) use ($data) {
                if (!empty($data['search_email'])) {
                    $query->orWhere('email', $data['search_email']);
                }
                if (!empty($data['search_code'])) {
                    $query->orWhere('code', strtoupper($data['search_code']));
                }
            })
            ->first();

        if (!$school) {
            return back()->withErrors([
                'search_email' => 'No registration found. Complete the form below to register a new school.',
            ])->withInput();
        }

        $subscription = Subscription::withoutGlobalScopes()->where('school_id', $school->id)->latest()->first();
        if ($subscription && $subscription->status === 'active' && (float) $subscription->amount > 0) {
            return redirect()->route('login')->with('success', 'This school is already active. Please log in.');
        }

        return redirect()->route('school.payment', ['school' => $school->id])
            ->with('success', 'School registration found. Complete payment below.');
    }

    public function storePublicSchool(Request $request)
    {
        return $this->provisionSchool($request, true);
    }

    public function paymentPage(School $school)
    {
        $school->load('subscription.package');
        // Amount comes from the package — never from session or user input
        $subscription = $school->subscription;
        $amount = (float) ($subscription?->package?->activation_fee ?? $subscription?->amount ?? 0);
        return view('school.payment', compact('school', 'amount'));
    }

    public function initiateMpesaSchoolPayment(Request $request, School $school)
    {
        $validated = $request->validate([
            'phone'  => 'required|string',
        ]);

        $subscription = Subscription::withoutGlobalScopes()
            ->with('package')
            ->where('school_id', $school->id)
            ->latest()
            ->first();

        // Load amount from package — never trust user-submitted amount
        $amount = (float) ($subscription?->package?->activation_fee ?? $subscription?->amount ?? 0);

        if ($amount <= 0) {
            return back()->withErrors(['phone' => 'Unable to determine payment amount. Please contact support.']);
        }

        // Create pending SaaS subscription payment (separate from student fee payments)
        $pendingPayment = SubscriptionPayment::create([
            'school_id'       => $school->id,
            'subscription_id' => $subscription?->id,
            'package_id'      => $subscription?->package_id,
            'amount'          => $amount,
            'payment_method'  => 'mpesa',
            'reference'       => 'SAAS-' . $school->id . '-' . now()->format('YmdHis'),
            'status'          => 'pending',
            'phone'           => $validated['phone'],
        ]);

        // Use a dedicated SaaS STK push that doesn't use student_id
        try {
            $mpesaService = app(\App\Services\MpesaDarajaService::class);
            $result = $mpesaService->initiateSubscriptionStkPush(
                schoolId: $school->id,
                phone: $validated['phone'],
                amount: $amount,
                reference: $pendingPayment->reference
            );

            $pendingPayment->update([
                'mpesa_checkout_request_id'  => $result['CheckoutRequestID'] ?? null,
                'mpesa_merchant_request_id'  => $result['MerchantRequestID'] ?? null,
                'status'                     => 'processing',
            ]);
        } catch (\Throwable $e) {
            $pendingPayment->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            return back()->withErrors(['phone' => 'M-Pesa request failed: ' . $e->getMessage()]);
        }

        return redirect()->route('school.payment', ['school' => $school->id])
            ->with('success', 'M-Pesa STK Push sent to ' . $validated['phone'] . '. Please complete payment on your phone.');
    }

    public function initiatePaystackSchoolPayment(Request $request, School $school)
    {
        $validated = $request->validate(['email' => 'required|email']);

        $subscription = Subscription::withoutGlobalScopes()
            ->with('package')
            ->where('school_id', $school->id)
            ->latest()
            ->first();

        // Amount from package — never trust client
        $amount = (float) ($subscription?->package?->activation_fee ?? $subscription?->amount ?? 0);

        if ($amount <= 0) {
            return back()->withErrors(['email' => 'Unable to determine payment amount.']);
        }

        $secretKey = config('services.paystack.secret_key');
        if (blank($secretKey)) {
            return back()->withErrors(['email' => 'Paystack secret key is not configured.']);
        }

        $reference = 'SAAS-PS-' . $school->id . '-' . now()->format('YmdHis');

        // Create pending subscription payment
        SubscriptionPayment::create([
            'school_id'       => $school->id,
            'subscription_id' => $subscription?->id,
            'package_id'      => $subscription?->package_id,
            'amount'          => $amount,
            'payment_method'  => 'paystack',
            'reference'       => $reference,
            'status'          => 'pending',
            'metadata'        => ['email' => $validated['email']],
        ]);

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email'        => $validated['email'],
            'amount'       => (int) round($amount * 100),
            'currency'     => 'KES',
            'callback_url' => route('school.payment.paystack.verify', ['school' => $school->id]),
            'reference'    => $reference,
            'metadata'     => ['school_id' => $school->id, 'school_name' => $school->name],
        ]);

        if ($response->successful() && !empty($response->json('data.authorization_url'))) {
            return redirect()->away($response->json('data.authorization_url'));
        }

        return back()->withErrors(['email' => $response->json('message') ?? 'Paystack checkout could not be initialized.']);
    }

    public function verifyPaystackSchoolPayment(Request $request, School $school)
    {
        $reference = $request->query('reference') ?? $request->input('reference');

        if (!$reference) {
            return redirect()->route('school.payment', $school->id)->withErrors(['error' => 'No payment reference provided.']);
        }

        // Never activate based on redirect — verify server-side
        $secretKey = config('services.paystack.secret_key');
        $response  = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
        ])->get("https://api.paystack.co/transaction/verify/{$reference}");

        $data   = $response->json('data');
        $status = $data['status'] ?? null;

        $payment = SubscriptionPayment::where('reference', $reference)->first();

        if ($status === 'success' && $payment) {
            $payment->update([
                'status'  => 'successful',
                'paid_at' => now(),
                'metadata'=> $data,
            ]);

            $subscription = Subscription::withoutGlobalScopes()
                ->where('school_id', $school->id)->latest()->first();

            if ($subscription) {
                SchoolSubscriptionService::activateSubscription($subscription);
            }

            return redirect()->route('login')->with('success', 'Payment confirmed. Your school account is now active. Please log in.');
        }

        if ($payment) {
            $payment->update(['status' => 'failed', 'failure_reason' => $data['gateway_response'] ?? 'Verification failed']);
        }

        return redirect()->route('school.payment', $school->id)->withErrors(['error' => 'Payment could not be verified. Please try again.']);
    }

    // ─── PROVISION SCHOOL ──────────────────────────────────────────────────────
    protected function provisionSchool(Request $request, bool $isPublicRegistration): mixed
    {
        // Graceful fallback for legacy registration calls providing plan_name instead of package_id
        if (!$request->filled('package_id')) {
            $matchedPkg = null;
            if ($request->filled('plan_name')) {
                $token = explode(' ', trim($request->plan_name))[0];
                $matchedPkg = Package::where('name', 'like', "%{$token}%")->first();
            }
            $matchedPkg = $matchedPkg ?? Package::where('is_active', true)->first();
            if ($matchedPkg) {
                $request->merge(['package_id' => $matchedPkg->id]);
            }
        }

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'code'            => 'required|string|max:50|unique:schools,code',
            'email'           => 'required|email',
            'phone'           => 'required|string',
            'curriculum_type' => 'required|in:CBC,8-4-4,Hybrid',
            'county'          => 'nullable|string',
            'motto'           => 'nullable|string',
            // MUST be a package_id — amount comes from the package, never from client
            'package_id'      => 'required|exists:packages,id',
            'admin_name'      => 'required|string|max:255',
            'admin_email'     => 'required|email|max:255|unique:users,email',
            'admin_phone'     => 'nullable|string|max:25',
            'admin_password'  => 'required|string|min:6',
        ]);


        $package = Package::findOrFail($validated['package_id']);
        $adminRole = Role::firstOrCreate(
            ['slug' => Role::SCHOOL_ADMIN],
            ['name' => 'School Admin', 'description' => 'School IT Administrator & System Manager']
        );

        $status = $isPublicRegistration
            ? ($package->trial_days > 0 ? 'trial' : 'pending')
            : 'active';

        $school = School::create([
            'name'            => $validated['name'],
            'code'            => strtoupper($validated['code']),
            'email'           => $validated['email'],
            'phone'           => $validated['phone'],
            'curriculum_type' => $validated['curriculum_type'],
            'county'          => $validated['county'] ?? 'Nairobi',
            'motto'           => $validated['motto'] ?? 'Empowering Minds',
            'status'          => $status,
            'trial_ends_at'   => $package->trial_days > 0 ? now()->addDays($package->trial_days) : null,
        ]);

        SchoolSetting::create([
            'school_id'       => $school->id,
            'currency'        => 'KES',
            'timezone'        => 'Africa/Nairobi',
            'daraja_shortcode'=> '174379',
            'sms_sender_id'   => 'CLOUDKAZI',
        ]);

        // Create subscription using package pricing from DB — never from user input
        $subscription = SchoolSubscriptionService::createSubscription($school, $package, [
            'status'         => $status,
            'payment_status' => $isPublicRegistration ? 'pending' : 'successful',
        ]);

$schoolAdmin = User::create([
    'school_id' => $school->id,
    'role_id'   => $adminRole->id,
            'name'      => $validated['admin_name'],
            'email'     => $validated['admin_email'],
            'phone'     => $validated['admin_phone'] ?? $validated['phone'],
            'password'  => Hash::make($validated['admin_password']),
            'is_active' => true,
        ]);

        if (!$isPublicRegistration) {
            AuditLogService::log(
                'school.created', 'school',
                "Provisioned school '{$school->name}' with package '{$package->name}'",
                $school->id,
                [],
                ['school_name' => $school->name, 'package' => $package->name, 'admin' => $schoolAdmin->email]
            );
        }

        if ($isPublicRegistration) {
            return redirect()->route('school.payment', ['school' => $school->id])
                ->with('success', "School '{$school->name}' registered. Complete subscription payment to activate access.");
        }

        return redirect()->route('superadmin.schools')
            ->with('success', "School '{$school->name}' provisioned with package '{$package->name}'. Admin: {$schoolAdmin->email}.");
    }
}
