<?php

namespace App\Services;

use App\Models\LeaveType;

class HrDefaultSetupService
{
    /**
     * Ensure default Kenyan leave types are initialized for a school.
     */
    public static function ensureDefaultLeaveTypes(int $schoolId): void
    {
        $defaults = [
            [
                'name' => 'Annual Leave',
                'code' => 'ANNUAL',
                'days_per_year' => 21,
                'is_paid' => true,
                'gender_restriction' => 'all',
                'carries_forward' => true,
                'max_carry_forward_days' => 7,
                'description' => 'Standard statutory annual vacation leave under the Kenya Employment Act.',
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SICK',
                'days_per_year' => 30,
                'is_paid' => true,
                'requires_attachment' => true,
                'gender_restriction' => 'all',
                'carries_forward' => false,
                'max_carry_forward_days' => 0,
                'description' => 'Medical leave accompanied by an authorized medical certificate or hospital discharge note.',
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'MATERNITY',
                'days_per_year' => 90,
                'is_paid' => true,
                'requires_attachment' => true,
                'gender_restriction' => 'female',
                'carries_forward' => false,
                'max_carry_forward_days' => 0,
                'description' => 'Statutory three months fully paid maternity leave for female employees.',
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'PATERNITY',
                'days_per_year' => 14,
                'is_paid' => true,
                'requires_attachment' => true,
                'gender_restriction' => 'male',
                'carries_forward' => false,
                'max_carry_forward_days' => 0,
                'description' => 'Statutory two weeks fully paid paternity leave for male employees.',
            ],
            [
                'name' => 'Compassionate Leave',
                'code' => 'COMPASSIONATE',
                'days_per_year' => 10,
                'is_paid' => true,
                'requires_attachment' => false,
                'gender_restriction' => 'all',
                'carries_forward' => false,
                'max_carry_forward_days' => 0,
                'description' => 'Leave granted upon bereavement or extreme family emergencies.',
            ],
            [
                'name' => 'Unpaid Leave',
                'code' => 'UNPAID',
                'days_per_year' => 0,
                'is_paid' => false,
                'requires_attachment' => false,
                'gender_restriction' => 'all',
                'carries_forward' => false,
                'max_carry_forward_days' => 0,
                'description' => 'Authorized absence without salary entitlement.',
            ],
        ];

        foreach ($defaults as $data) {
            LeaveType::firstOrCreate(
                ['school_id' => $schoolId, 'name' => $data['name']],
                $data + ['status' => 'active']
            );
        }
    }
}
