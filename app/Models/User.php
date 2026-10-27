<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'role_id',
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'is_active',
        'is_demo',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Defensive accessor for code expecting $user->roles collection
     */
    public function getRolesAttribute()
    {
        return $this->role ? collect([$this->role]) : collect();
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Student::class, 'parent_user_id');
    }

    /**
     * Retrieve all students strictly linked to this parent user across
     * direct parent_user_id, guardian pivot, and email/phone matching.
     */
    public function getLinkedStudents()
    {
        if (!$this->isParent()) {
            return collect();
        }

        $schoolId = $this->school_id ?? session('active_school_id');
        $fromParentUser = Student::where('parent_user_id', $this->id)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['schoolClass', 'stream'])
            ->get();
        $fromGuardian = $this->guardian
            ? $this->guardian->students()
                ->when($schoolId, fn ($q) => $q->where('students.school_id', $schoolId))
                ->with(['schoolClass', 'stream'])
                ->get()
            : collect();
        $fromEmailOrPhone = $schoolId
            ? Student::where('school_id', $schoolId)
                ->whereHas('guardians', function ($q) use ($schoolId) {
                    $q->where('guardians.school_id', $schoolId)
                        ->where(function ($linkQuery) {
                            $linkQuery->where('user_id', $this->id)
                                ->orWhere('email', $this->email);
                            if (!empty($this->phone)) {
                                $linkQuery->orWhere('phone', $this->phone);
                            }
                        });
                })->with(['schoolClass', 'stream'])->get()
            : collect();

        return $fromParentUser
            ->merge($fromGuardian)
            ->merge($fromEmailOrPhone)
            ->unique('id')
            ->values();
    }

    /**
     * Check if user is authorized to access records for a given student.
     */
    public function canAccessStudent(Student|int $student): bool
    {
        $studentId = $student instanceof Student ? $student->id : (int) $student;
        $studentObj = $student instanceof Student ? $student : Student::find($studentId);

        if (!$studentObj) {
            return false;
        }

        // Super Admin does NOT have access to individual student records
        // Super Admin is a platform owner — not a school ERP user
        if ($this->isSuperAdmin()) {
            return false;
        }

        // School staff (admin, principal, deputy, teacher, bursar) in same school
        if ($this->hasRole([Role::SCHOOL_ADMIN, Role::PRINCIPAL, Role::DEPUTY_PRINCIPAL, Role::TEACHER, Role::CLASS_TEACHER, Role::BURSAR])) {
            $userSchoolId = $this->school_id ?? session('active_school_id');
            return $userSchoolId && ((int)$userSchoolId === (int)$studentObj->school_id);
        }

        // Student themselves
        if ($this->isStudent()) {
            return ($this->student && $this->student->id === $studentId) || ((int)$studentObj->user_id === (int)$this->id);
        }

        // Parent / Guardian
        if ($this->isParent()) {
            $userSchoolId = $this->school_id ?? session('active_school_id');
            if ($userSchoolId && (int) $studentObj->school_id !== (int) $userSchoolId) {
                return false;
            }

            if ((int)$studentObj->parent_user_id === (int)$this->id) {
                return true;
            }

            // Check guardian linkage
            if ($this->guardian && $this->guardian->students()->where('students.id', $studentId)->exists()) {
                return true;
            }

            return $studentObj->guardians()
                ->where(function ($q) {
                    $q->where('user_id', $this->id)
                        ->orWhere('email', $this->email);
                    if (!empty($this->phone)) {
                        $q->orWhere('phone', $this->phone);
                    }
                })->exists();
        }

        return false;
    }

    // Role check helpers
    public function hasRole(string|array $roles): bool
    {
        if (!$this->role) {
            return false;
        }

        if (is_array($roles)) {
            return in_array($this->role->slug, $roles);
        }

        return $this->role->slug === $roles;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole([Role::SUPER_ADMIN, Role::SCHOOL_ADMIN, Role::PRINCIPAL, Role::DEPUTY_PRINCIPAL]);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    public function isSchoolAdmin(): bool
    {
        return $this->hasRole([Role::SCHOOL_ADMIN, Role::PRINCIPAL, Role::DEPUTY_PRINCIPAL]);
    }

    public function isPrincipal(): bool
    {
        return $this->hasRole(Role::PRINCIPAL);
    }

    public function isDeputyPrincipal(): bool
    {
        return $this->hasRole(Role::DEPUTY_PRINCIPAL);
    }

    public function isLibrarian(): bool
    {
        return $this->hasRole(Role::LIBRARIAN);
    }

    public function isTeacher(): bool
    {
        return $this->hasRole([Role::TEACHER, Role::CLASS_TEACHER]);
    }

    public function isParent(): bool
    {
        return $this->hasRole(Role::PARENT);
    }

    public function isStudent(): bool
    {
        return $this->hasRole(Role::STUDENT);
    }

    public function isBursar(): bool
    {
        return $this->hasRole(Role::BURSAR);
    }

    public function isExaminationsOfficer(): bool
    {
        return $this->hasRole([Role::EXAMINATIONS_OFFICER, 'exams-officer']);
    }

    public function isSecretary(): bool
    {
        return $this->hasRole(Role::SECRETARY);
    }

    public function isIctAdmin(): bool
    {
        return $this->hasRole(Role::ICT_ADMIN);
    }

    public function isDisciplineOfficer(): bool
    {
        return $this->hasRole(Role::DISCIPLINE_OFFICER);
    }

    public function isHostelWarden(): bool
    {
        return $this->hasRole(Role::HOSTEL_WARDEN);
    }

    public function isDemoAccount(): bool
    {
        return (bool) $this->is_demo || str_ends_with(strtolower((string) $this->email), '@demo.com');
    }

    public function getDashboardRoute(): string
    {
        if ($this->isSuperAdmin()) {
            return route('superadmin.dashboard');
        } elseif ($this->isSchoolAdmin()) {
            return route('school.dashboard');
        } elseif ($this->isLibrarian()) {
            return route('library.index');
        } elseif ($this->isTeacher()) {
            return route('teacher.dashboard');
        } elseif ($this->isStudent()) {
            return route('student.dashboard');
        } elseif ($this->isParent()) {
            return route('parent.dashboard');
        } elseif ($this->isBursar()) {
            return route('finance.dashboard');
        } elseif ($this->isExaminationsOfficer()) {
            return \Illuminate\Support\Facades\Route::has('examinations.index') ? route('examinations.index') : route('school.dashboard');
        } elseif ($this->isHostelWarden()) {
            return \Illuminate\Support\Facades\Route::has('warden.dashboard') ? route('warden.dashboard') : route('hostel.index');
        } elseif ($this->hasRole(Role::TRANSPORT_OFFICER)) {
            return route('transport.index');
        } elseif ($this->isDisciplineOfficer()) {
            return \Illuminate\Support\Facades\Route::has('discipline.index') ? route('discipline.index') : route('school.dashboard');
        } elseif ($this->isSecretary() || $this->isIctAdmin() || ($this->role && $this->role->is_staff)) {
            // General staff fallback to school dashboard
            return route('school.dashboard');
        }

        return route('login');
    }
}
