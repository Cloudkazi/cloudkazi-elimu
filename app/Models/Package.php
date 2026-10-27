<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'activation_fee' => 'decimal:2',
        'annual_fee'     => 'decimal:2',
        'is_public'      => 'boolean',
        'is_active'      => 'boolean',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(PackageFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFeatureEnabled(string $featureKey): bool
    {
        return $this->features()
            ->where('feature_key', $featureKey)
            ->where('enabled', true)
            ->exists();
    }

    public function enabledFeatureKeys(): array
    {
        return $this->features()
            ->where('enabled', true)
            ->pluck('feature_key')
            ->toArray();
    }

    /** All possible feature keys for the platform */
    public static function allFeatureKeys(): array
    {
        return [
            'student_management' => 'Student Management',
            'teacher_management' => 'Teacher Management',
            'parent_portal'      => 'Parent Portal',
            'student_portal'     => 'Student Portal',
            'finance'            => 'Finance & Fees',
            'mpesa'              => 'M-Pesa Integration',
            'examinations'       => 'Examinations & Reports',
            'attendance'         => 'Attendance',
            'timetable'          => 'Timetable Generator',
            'library'            => 'Library Management',
            'transport'          => 'Transport Management',
            'hostel'             => 'Hostel Management',
            'sms'                => 'Bulk SMS',
            'reports'            => 'Reports & Analytics',
            'communication'      => 'Communication & Notices',
        ];
    }
}
