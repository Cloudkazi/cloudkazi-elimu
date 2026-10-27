<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    protected function authorizeAdmin(): void
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isSchoolAdmin())) {
            abort(403, 'Unauthorized. Only school administrators can access staff and user accounts.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $schoolId = Auth::user()->school_id ?? session('active_school_id');

        $query = User::with(['role', 'school'])
            ->where('school_id', $schoolId);

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->input('role_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);
        $assignableRoles = Role::whereIn('slug', array_merge([Role::SCHOOL_ADMIN], Role::STAFF_SLUGS))
            ->orderBy('name')
            ->get();
        $roles = Role::where('slug', '!=', Role::SUPER_ADMIN)->orderBy('name')->get();
        $portalLabels = [
            Role::SCHOOL_ADMIN => 'School dashboard',
            Role::PRINCIPAL => 'School dashboard',
            Role::DEPUTY_PRINCIPAL => 'School dashboard',
            Role::TEACHER => 'Teacher portal',
            Role::CLASS_TEACHER => 'Teacher portal',
            Role::STUDENT => 'Student portal',
            Role::PARENT => 'Parent portal',
            Role::BURSAR => 'Finance portal',
            Role::LIBRARIAN => 'Library portal',
            Role::EXAMINATIONS_OFFICER => 'Examinations portal',
            Role::SECRETARY => 'School dashboard',
            Role::ICT_ADMIN => 'School dashboard',
            Role::DISCIPLINE_OFFICER => 'Discipline portal',
            Role::HOSTEL_WARDEN => 'Warden portal',
            Role::TRANSPORT_OFFICER => 'Transport portal',
        ];

        $totalUsers = User::where('school_id', $schoolId)->count();
        $activeUsers = User::where('school_id', $schoolId)->where('is_active', true)->count();
        $teachersCount = User::where('school_id', $schoolId)->whereHas('role', fn($q) => $q->whereIn('slug', ['teacher', 'class-teacher']))->count();
        $financeCount = User::where('school_id', $schoolId)->whereHas('role', fn($q) => $q->where('slug', 'bursar'))->count();
        $librariansCount = User::where('school_id', $schoolId)->whereHas('role', fn($q) => $q->where('slug', Role::LIBRARIAN))->count();

        return view('school.users', compact(
            'users',
            'roles',
            'assignableRoles',
            'totalUsers',
            'activeUsers',
            'teachersCount',
            'financeCount',
            'librariansCount',
            'portalLabels'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $schoolId = Auth::user()->school_id ?? session('active_school_id');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:25',
            'role_id' => 'required|exists:roles,id',
            'password' => ['required', 'string', 'min:6'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        // Only supported school roles are assignable here; platform super-admin stays protected.
        if (!in_array($role->slug, array_merge([Role::SCHOOL_ADMIN], Role::STAFF_SLUGS), true)) {
            return back()->withInput()->withErrors([
                'role_id' => 'Invalid staff role selected. Only authorized school staff roles can be assigned.'
            ]);
        }

        if (! $schoolId) {
            return back()->withInput()->withErrors([
                'role_id' => 'Select an active school before creating a staff account.',
            ]);
        }

        // Limit enforcement by role type
        if ($schoolId) {
            if (in_array($role->slug, ['teacher', 'class-teacher']) && !\App\Services\PopulationAllocationService::canCreateTeacher((int) $schoolId)) {
                $limit = \App\Services\PopulationAllocationService::getTeacherLimit((int) $schoolId);
                return back()->withInput()->withErrors([
                    'limit' => "Teacher Limit Reached: Your current subscription allows up to {$limit} teachers. Please contact CloudKazi to increase your limit."
                ]);
            }
            if ($role->slug === Role::PARENT && !\App\Services\PopulationAllocationService::canCreateParent((int) $schoolId)) {
                $limit = \App\Services\PopulationAllocationService::getParentLimit((int) $schoolId);
                return back()->withInput()->withErrors([
                    'limit' => "Parent Account Limit Reached: Your current subscription allows up to {$limit} parent accounts. Please contact CloudKazi to increase your limit."
                ]);
            }
            if (in_array($role->slug, [Role::SCHOOL_ADMIN, Role::PRINCIPAL]) && !\App\Services\PopulationAllocationService::canCreateAdmin((int) $schoolId)) {
                $limit = \App\Services\PopulationAllocationService::getAdminLimit((int) $schoolId);
                return back()->withInput()->withErrors([
                    'limit' => "Admin Limit Reached: Your current subscription allows up to {$limit} administrative accounts. Please contact CloudKazi to increase your limit."
                ]);
            }
        }

        $user = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $schoolId, $role) {
            $user = User::create([
                'school_id' => $schoolId,
                'role_id' => $role->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            if (in_array($role->slug, [Role::TEACHER, Role::CLASS_TEACHER], true)) {
                Teacher::firstOrCreate([
                    'user_id' => $user->id,
                ], [
                    'school_id' => $schoolId,
                    'tsc_number' => 'TSC-' . rand(100000, 999999),
                    'specialization' => 'General Faculty',
                    'status' => 'active',
                ]);
            }

            return $user;
        });

        return back()->with('success', "Staff account for '{$user->name}' ({$role->name}) created. Sign in with {$user->email} and the password set during enrollment.");
    }

    public function toggleStatus(User $user)
    {
        $this->authorizeAdmin();

        $schoolId = Auth::user()->school_id ?? session('active_school_id');
        if ($user->school_id != $schoolId || $user->isSuperAdmin()) {
            return back()->with('error', 'Unauthorized to modify this user account.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot lock your own account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $action = $user->is_active ? 'activated' : 'locked';
        return back()->with('success', "Account for '{$user->name}' has been {$action}.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $schoolId = Auth::user()->school_id ?? session('active_school_id');
        if ($user->school_id != $schoolId || $user->isSuperAdmin()) {
            return back()->with('error', 'Unauthorized to reset password for this user.');
        }

        $validated = $request->validate([
            'new_password' => ['required', 'string', 'min:6'],
        ]);

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return back()->with('success', "Password for {$user->name} has been reset successfully.");
    }

    public function showChangePassword()
    {
        return view('auth.change_password');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();
        $user->password = Hash::make($validated['password']);
        $user->save();

        return back()->with('success', 'Your password has been changed successfully.');
    }
}
