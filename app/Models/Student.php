<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = [
        'dob' => 'date',
        'admission_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDateOfBirthAttribute(): ?Carbon
    {
        return isset($this->attributes['dob']) ? Carbon::parse($this->attributes['dob']) : null;
    }

    public function getAdmissionDateAttribute(): ?Carbon
    {
        return isset($this->attributes['admission_date']) ? Carbon::parse($this->attributes['admission_date']) : null;
    }

    public function getGuardianNameAttribute(): ?string
    {
        $guardian = $this->guardians()->orderByDesc('is_primary')->first();

        if ($guardian) {
            return trim($guardian->first_name . ' ' . $guardian->last_name);
        }

        if ($this->parent) {
            return $this->parent->name;
        }

        return null;
    }

    public function getGuardianPhoneAttribute(): ?string
    {
        $guardian = $this->guardians()->orderByDesc('is_primary')->first();

        return $guardian?->phone ?? ($this->parent?->phone ?? null);
    }

    public function getGuardianEmailAttribute(): ?string
    {
        $guardian = $this->guardians()->orderByDesc('is_primary')->first();

        return $guardian?->email ?? ($this->parent?->email ?? null);
    }

    public function getGuardianRelationshipAttribute(): ?string
    {
        $guardian = $this->guardians()->orderByDesc('is_primary')->first();

        return $guardian?->relationship ?? null;
    }

    public function getPhotoAttribute(): ?string
    {
        return $this->attributes['passport_photo'] ?? null;
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function currentClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_guardian')->withPivot('is_primary')->withTimestamps();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(StudentInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function hostelAllocation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentHostel::class)->latestOfMany();
    }

    public function hostels(): HasMany
    {
        return $this->hasMany(StudentHostel::class);
    }

    public function transportAllocation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StudentTransport::class)->where('is_active', true)->latestOfMany();
    }

    public function transports(): HasMany
    {
        return $this->hasMany(StudentTransport::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    public function marks844(): HasMany
    {
        return $this->hasMany(ExamMark844::class);
    }

    public function cbcAssessments(): HasMany
    {
        return $this->hasMany(CbcAssessment::class);
    }

    public function reportSummaries(): HasMany
    {
        return $this->hasMany(StudentReportSummary::class);
    }

    public function disciplineIncidents(): HasMany
    {
        return $this->hasMany(DisciplineIncident::class);
    }

    public function bookIssues(): HasMany
    {
        return $this->hasMany(BookIssue::class);
    }

    public function issuedBooks(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(Book::class, BookIssue::class, 'student_id', 'id', 'id', 'book_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name} {$this->other_names}");
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    /**
     * Determine if student is a boarder based on assigned profile or active hostel allocation.
     */
    public function isBoarder(): bool
    {
        if (in_array($this->fee_profile, ['boarder', 'boarder_transport'])) {
            return true;
        }

        return $this->hostels()->exists();
    }

    /**
     * Determine if student uses school transport based on assigned profile or active transport route.
     */
    public function usesTransport(): bool
    {
        if (in_array($this->fee_profile, ['transport', 'boarder_transport'])) {
            return true;
        }

        return $this->transports()->where('is_active', true)->exists();
    }

    /**
     * Calculate dynamic fee balance and categories (tuition, boarding, transport, other charges)
     * based on student's assigned fee profile and fee structures, minus actual fee_payments.
     */
    public function calculateFeeBreakdown(?int $termId = null, ?int $academicYearId = null): array
    {
        $schoolId = $this->school_id;

        // Resolve current or latest term/year if not provided
        if (!$termId) {
            $termId = AcademicTerm::where('school_id', $schoolId)->where('is_current', true)->value('id')
                ?? AcademicTerm::where('school_id', $schoolId)->latest('id')->value('id');
        }

        if (!$academicYearId) {
            $academicYearId = AcademicYear::where('school_id', $schoolId)->where('is_current', true)->value('id')
                ?? AcademicYear::where('school_id', $schoolId)->latest('id')->value('id');
        }

        // Query fee structures for this student's class
        $structures = FeeStructure::with('category')
            ->where('school_id', $schoolId)
            ->where('school_class_id', $this->school_class_id)
            ->when($academicYearId, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->get();

        // If no structures found for specific term, query all structures for the class
        if ($structures->isEmpty()) {
            $structures = FeeStructure::with('category')
                ->where('school_id', $schoolId)
                ->where('school_class_id', $this->school_class_id)
                ->get();
        }

        $categories = [
            'tuition' => [
                'label' => 'Tuition & Academic Support',
                'amount' => 0.00,
                'applicable' => true,
                'description' => 'Core classroom instruction and academic programs',
            ],
            'boarding' => [
                'label' => 'Boarding & Accommodation',
                'amount' => 0.00,
                'applicable' => $this->isBoarder(),
                'description' => $this->isBoarder() ? 'Dormitory, meals, and hostel facilities' : 'Not applicable (Day Scholar)',
            ],
            'transport' => [
                'label' => 'Transport Services',
                'amount' => 0.00,
                'applicable' => $this->usesTransport(),
                'description' => $this->usesTransport() ? 'Termly bus fare & route pick-up' : 'Not applicable (No Transport)',
            ],
            'other' => [
                'label' => 'Other Institutional Charges',
                'amount' => 0.00,
                'applicable' => true,
                'description' => 'Activity, assessment, library, and development levies',
            ],
        ];

        // Map fee structures into categories
        foreach ($structures as $st) {
            $catName = strtolower($st->category?->name ?? '');
            $amount = (float) $st->amount;

            if (str_contains($catName, 'tuition') || str_contains($catName, 'academic')) {
                $categories['tuition']['amount'] += $amount;
            } elseif (str_contains($catName, 'boarding') || str_contains($catName, 'hostel') || str_contains($catName, 'meals')) {
                if ($this->isBoarder()) {
                    $categories['boarding']['amount'] += $amount;
                }
            } elseif (str_contains($catName, 'transport') || str_contains($catName, 'bus')) {
                if ($this->usesTransport()) {
                    $categories['transport']['amount'] += $amount;
                }
            } else {
                $categories['other']['amount'] += $amount;
            }
        }

        // If transport route is assigned, include route fare if transport structure wasn't already added
        if ($this->usesTransport() && $categories['transport']['amount'] <= 0) {
            $transportRoute = $this->transportAllocation?->route;
            if ($transportRoute) {
                $categories['transport']['amount'] = (float) $transportRoute->termly_fare;
            }
        }

        // If student invoices exist and structures were empty, fallback to invoices total
        $totalBilled = 0.00;
        foreach ($categories as $catKey => $cat) {
            if ($cat['applicable']) {
                $totalBilled += $cat['amount'];
            }
        }

        if ($totalBilled <= 0 && $this->invoices()->exists()) {
            $invTotal = (float) $this->invoices()->sum('total_amount');
            $categories['tuition']['amount'] = $invTotal;
            $totalBilled = $invTotal;
        }

        // Total recorded payments from the unified fee_payments table
        $totalPaid = (float) FeePayment::where('student_id', $this->id)->sum('amount');
        $dynamicBalance = max(0.0, $totalBilled - $totalPaid);

        return [
            'categories' => $categories,
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'balance' => $dynamicBalance,
            'is_cleared' => $dynamicBalance <= 0,
        ];
    }

    public function getDynamicFeeBalanceAttribute(): float
    {
        return (float) ($this->calculateFeeBreakdown()['balance'] ?? 0.0);
    }

    public function getFeeCategoryBreakdownAttribute(): array
    {
        return $this->calculateFeeBreakdown();
    }

    public function getTotalFeeBalanceAttribute(): float
    {
        // Dynamically compute from fee structures and fee_payments table
        return $this->getDynamicFeeBalanceAttribute();
    }

    public function getFeeBalanceAttribute(): float
    {
        return $this->getDynamicFeeBalanceAttribute();
    }
}
