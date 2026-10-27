<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class School extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->withoutGlobalScopes()->latestOfMany();
    }

    public function settings(): HasOne
    {
        return $this->hasOne(SchoolSetting::class);
    }

    public function academicYears(): HasMany
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class)->orderBy('numeric_level');
    }

    public function currentAcademicYear()
    {
        return $this->academicYears()->where('is_current', true)->first();
    }

    // SaaS relationships
    public function features(): HasMany
    {
        return $this->hasMany(SchoolFeature::class);
    }

    public function populationAllocations(): HasMany
    {
        return $this->hasMany(SchoolPopulationAllocation::class);
    }

    public function latestPopulationAllocation(): HasOne
    {
        return $this->hasOne(SchoolPopulationAllocation::class)->latestOfMany();
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /** Aggregate counts — does NOT expose individual records */
    public function getStudentCount(): int
    {
        return $this->students()->count();
    }

    public function getTeacherCount(): int
    {
        return $this->teachers()->count();
    }

    public function getParentCount(): int
    {
        return $this->users()->whereHas('role', fn ($q) => $q->where('slug', Role::PARENT))->count();
    }
}
