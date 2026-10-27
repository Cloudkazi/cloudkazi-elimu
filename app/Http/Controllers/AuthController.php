<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\Scopes\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect(Auth::user()->getDashboardRoute());
        }

        $demoUsers = User::with(['role', 'school'])->take(12)->get();

        return view('auth.login', compact('demoUsers'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($request->input('email'));
        $password = trim($request->input('password'));
        $remember = $request->boolean('remember');

        $parentUser = User::where('email', $loginInput)
            ->whereHas('role', function ($r) {
                $r->where('slug', Role::PARENT);
            })
            ->first();

        if ($parentUser && $this->isParentPasswordValid($parentUser, $password)) {
            Auth::login($parentUser, $remember);
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
            if ($parentUser->school_id) {
                Session::put('active_school_id', $parentUser->school_id);
            }
            $parentUser->update(['last_login_at' => now()]);

            return redirect()->intended(route('parent.dashboard'))
                ->with('success', "Welcome back, {$parentUser->name}!");
        }

        $student = $this->resolveStudentForLogin($loginInput, $password);

        if ($student) {
            $studentUser = $student->user;
            if (! $studentUser) {
                $studentRole = Role::firstOrCreate(
                    ['slug' => Role::STUDENT],
                    ['name' => 'Student', 'description' => 'Enrolled Student']
                );
                $studentUser = User::where('email', $this->studentPortalEmail($student))->first();

                if (! $studentUser) {
                    $guardianPhone = $student->parent?->phone
                        ?: $student->guardians->first()?->phone
                        ?: $student->admission_number;
                    $studentUser = User::create([
                        'school_id' => $student->school_id,
                        'role_id' => $studentRole->id,
                        'name' => $student->full_name,
                        'email' => $this->studentPortalEmail($student),
                        'password' => Hash::make($guardianPhone),
                        'is_active' => true,
                    ]);
                }

                $student->user()->associate($studentUser);
                $student->save();
            }

            if (! $studentUser->isStudent()) {
                $studentRole = Role::firstOrCreate(
                    ['slug' => Role::STUDENT],
                    ['name' => 'Student', 'description' => 'Enrolled Student']
                );
                $studentUser->update(['role_id' => $studentRole->id]);
            }

            Auth::login($studentUser, $remember);
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
            if ($student->school_id) {
                Session::put('active_school_id', $student->school_id);
            }
            $studentUser->update(['last_login_at' => now()]);

            return redirect()->intended(route('student.dashboard'))
                ->with('success', "Welcome to your student portal, {$student->first_name}!");
        }

        if (Auth::attempt(['email' => $loginInput, 'password' => $password], $remember)) {
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
            $user = Auth::user();

            if ($user->school_id) {
                Session::put('active_school_id', $user->school_id);
            }

            $user->update(['last_login_at' => now()]);

            if ($user->isDemoAccount()) {
                return redirect()->intended($user->getDashboardRoute())
                    ->with('warning', 'This is a demo account. Access is frozen. Please register to activate your account and unlock full access.');
            }

            return redirect()->intended($user->getDashboardRoute())
                ->with('success', "Welcome back, {$user->name}!");
        }

        return back()->withErrors([
            'email' => 'Invalid email or admission number.',
        ])->onlyInput('email');
    }

    protected function isParentPasswordValid(User $parentUser, string $password): bool
    {
        $cleanInputPassword = preg_replace('/[^0-9]/', '', $password);
        $cleanUserPhone = preg_replace('/[^0-9]/', '', $parentUser->phone ?? '');
        $guardian = $parentUser->guardian ?? Guardian::where('user_id', $parentUser->id)
            ->orWhere('email', $parentUser->email)
            ->first();
        $cleanGuardianPhone = preg_replace('/[^0-9]/', '', $guardian->phone ?? '');

        return Hash::check($password, $parentUser->password)
            || ($cleanInputPassword !== '' && ($cleanInputPassword === $cleanUserPhone || $cleanInputPassword === $cleanGuardianPhone));
    }

    protected function resolveStudentForLogin(string $loginInput, string $password): ?Student
    {
        $normalizedLoginInput = strtolower(trim($loginInput));
        $normalizedPassword = strtolower(trim($password));
        $cleanInputPassword = preg_replace('/[^0-9]/', '', $password);

        $studentsByAdmissionNumber = Student::withoutGlobalScope(TenantScope::class)
            ->with(['parent', 'guardians'])
            ->whereRaw('LOWER(admission_number) = ?', [$normalizedLoginInput])
            ->get();

        $studentsMatchingPhone = $studentsByAdmissionNumber->filter(function (Student $student) use ($cleanInputPassword) {
            $parentPhone = preg_replace('/[^0-9]/', '', $student->parent?->phone ?? '');
            if ($cleanInputPassword !== '' && $cleanInputPassword === $parentPhone) {
                return true;
            }

            foreach ($student->guardians as $guardian) {
                $guardianPhone = preg_replace('/[^0-9]/', '', $guardian->phone ?? '');
                if ($cleanInputPassword !== '' && $cleanInputPassword === $guardianPhone) {
                    return true;
                }
            }

            return false;
        });

        if ($studentsMatchingPhone->count() === 1) {
            return $studentsMatchingPhone->first();
        }
        if ($studentsMatchingPhone->count() > 1) {
            return null;
        }

        $parentUser = User::whereRaw('LOWER(email) = ?', [$normalizedLoginInput])
            ->whereHas('role', function ($r) {
                $r->where('slug', Role::PARENT);
            })
            ->first();

        $matchingStudents = Student::withoutGlobalScope(TenantScope::class)
            ->with(['parent', 'guardians'])
            ->where(function ($query) use ($normalizedPassword) {
                $query->whereRaw('LOWER(admission_number) = ?', [$normalizedPassword]);
            })
            ->get();

        if ($parentUser) {
            foreach ($matchingStudents as $student) {
                if ((int) $student->parent_user_id === (int) $parentUser->id) {
                    return $student;
                }

                if (strtolower((string) ($student->parent?->email ?? '')) === $normalizedLoginInput) {
                    return $student;
                }

                foreach ($student->guardians as $guardian) {
                    if ((int) ($guardian->user_id ?? 0) === (int) $parentUser->id) {
                        return $student;
                    }

                    if (strtolower((string) ($guardian->email ?? '')) === $normalizedLoginInput) {
                        return $student;
                    }
                }
            }

            return null;
        }

        foreach ($matchingStudents as $student) {
            if (strtolower((string) ($student->parent?->email ?? '')) === $normalizedLoginInput) {
                return $student;
            }

            foreach ($student->guardians as $guardian) {
                if (strtolower((string) ($guardian->email ?? '')) === $normalizedLoginInput) {
                    return $student;
                }
            }
        }

        return null;
    }

    protected function studentPortalEmail(Student $student): string
    {
        $cleanAdm = strtolower(str_replace([' ', '/', '\\'], '-', $student->admission_number));

        return $cleanAdm . '-school-' . ($student->school_id ?? $student->id) . '@student.cloudkazi.com';
    }

    /**
     * Quick demo impersonation/switcher for instant review across roles
     */
    public function quickLogin(int $userId)
    {
        $user = User::findOrFail($userId);
        Auth::login($user);
        request()->session()->regenerate();

        if ($user->school_id) {
            Session::put('active_school_id', $user->school_id);
        }

        return redirect($user->getDashboardRoute())
            ->with('info', "Logged in as {$user->name} ({$user->role->name})");
    }

    public function switchSchool(Request $request)
    {
        $user = Auth::user();

        // Regular school users are bound to their assigned school and cannot switch arbitrarily
        if (!$user || !$user->isSuperAdmin()) {
            return back()->with('error', 'You are not authorized to switch school contexts.');
        }

        $schoolId = $request->input('school_id');
        $school = School::findOrFail($schoolId);

        Session::put('active_school_id', $school->id);

        return back()->with('success', "Switched active school context to {$school->name}");
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out successfully.');
    }

    /**
     * Display forgot password page.
     */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle password reset instruction request.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
        ]);

        return back()->with('status', 'If your account is registered, password reset instructions have been sent. For student or parent credential assistance, please contact your school administrator or bursar.');
    }
}
