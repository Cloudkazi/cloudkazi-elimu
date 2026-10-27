<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // ── Platform / System Roles ──────────────────────────────────────────────
    public const SUPER_ADMIN   = 'super-admin';
    public const SCHOOL_ADMIN  = 'school-admin';

    // ── School Leadership ────────────────────────────────────────────────────
    public const PRINCIPAL        = 'principal';
    public const DEPUTY_PRINCIPAL = 'deputy-principal';

    // ── Teaching Staff ───────────────────────────────────────────────────────
    public const TEACHER       = 'teacher';
    public const CLASS_TEACHER = 'class-teacher'; // Legacy constant; teacher + class assignment model

    // ── Finance ──────────────────────────────────────────────────────────────
    public const BURSAR = 'bursar';

    // ── Support / Specialized Staff ──────────────────────────────────────────
    public const LIBRARIAN             = 'librarian';
    public const EXAMINATIONS_OFFICER  = 'examinations-officer'; // Examinations Officer
    public const EXAMS_OFFICER         = 'examinations-officer'; // Backward compatibility alias
    public const SECRETARY             = 'secretary';            // Secretary / Front Office
    public const ICT_ADMIN             = 'ict-admin';            // ICT Administrator
    public const DISCIPLINE_OFFICER    = 'discipline-officer';  // Discipline / Welfare Officer
    public const HOSTEL_WARDEN         = 'hostel-warden';       // UI display name: Dorm Master
    public const TRANSPORT_OFFICER     = 'transport-officer';

    // ── Non-Staff Roles (students, parents) ─────────────────────────────────
    public const PARENT  = 'parent';
    public const STUDENT = 'student';

    /**
     * School roles available in the staff account form.
     */
    public const STAFF_SLUGS = [
        self::PRINCIPAL,
        self::DEPUTY_PRINCIPAL,
        self::TEACHER,
        self::CLASS_TEACHER,
        self::BURSAR,
        self::LIBRARIAN,
        self::EXAMINATIONS_OFFICER,
        self::SECRETARY,
        self::ICT_ADMIN,
        self::DISCIPLINE_OFFICER,
        self::HOSTEL_WARDEN,
        self::TRANSPORT_OFFICER,
    ];

    /**
     * Roles that are not standard staff; School Admin is assigned separately.
     */
    public const NON_STAFF_SLUGS = [
        self::SUPER_ADMIN,
        self::SCHOOL_ADMIN,
        self::PARENT,
        self::STUDENT,
    ];


    // ── Eloquent Scopes ──────────────────────────────────────────────────────

    /**
     * Scope: Only staff roles (for Add Staff dropdown).
     */
    public function scopeStaffOnly($query)
    {
        return $query->where(function ($q) {
            $q->where('is_staff', true)
              ->orWhereIn('slug', self::STAFF_SLUGS);
        });
    }

    /**
     * Accessor: Determine whether this role is classified as normal school staff.
     */
    public function getIsStaffAttribute($value): bool
    {
        return (bool) $value || in_array($this->slug, self::STAFF_SLUGS, true);
    }

    /**
     * Scope: Exclude platform-level roles.
     */
    public function scopeExcludePlatform($query)
    {
        return $query->whereNotIn('slug', self::NON_STAFF_SLUGS);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
