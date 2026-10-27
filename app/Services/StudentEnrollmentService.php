<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use App\Models\Guardian;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentEnrollmentService
{
    /**
     * Enroll a student and automatically create associated user accounts for the
     * student and the parent (guardian). All operations are performed within a
     * database transaction to guarantee atomicity.
     *
     * @param array $data Validated request data from the enrollment form.
     */
    public static function enroll(array $data): void
    {
        $authUser = auth()->user();
        if ($authUser && !$authUser->isSuperAdmin() && $authUser->school_id) {
            $schoolId = $authUser->school_id;
        } else {
            $schoolId = $data['school_id'] ?? ($authUser?->school_id ?? session('active_school_id'));
        }

        if ($schoolId && !\App\Services\PopulationAllocationService::canCreateStudent((int) $schoolId)) {
            $limit = \App\Services\PopulationAllocationService::getStudentLimit((int) $schoolId);
            throw new \RuntimeException("Student Limit Reached: Your current subscription allows up to {$limit} students. Please contact CloudKazi to increase your limit.");
        }

        DB::transaction(function () use ($data, $schoolId) {
            // Create the Student record
            $student = Student::create([
                'admission_number' => $data['admission_number'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'other_names' => $data['other_names'] ?? null,
                'gender' => $data['gender'],
                'dob' => $data['dob'],
                'admission_date' => $data['admission_date'],
                'school_class_id' => $data['school_class_id'],
                'stream_id' => $data['stream_id'] ?? null,
                'curriculum' => $data['curriculum'],
                'pathway' => $data['pathway'] ?? 'general',
                'birth_certificate_no' => $data['birth_certificate_no'] ?? null,
                'nemis_upi' => $data['nemis_upi'] ?? null,
                'assessment_number' => $data['assessment_number'] ?? null,
                'medical_conditions' => $data['medical_conditions'] ?? null,
                'blood_group' => $data['blood_group'] ?? null,
                'fee_profile' => $data['fee_profile'] ?? 'regular',
                'status' => $data['status'] ?? 'active',
                'school_id' => $schoolId,
            ]);

            $studentRole = Role::firstOrCreate(
                ['slug' => Role::STUDENT],
                ['name' => 'Student', 'description' => 'Enrolled Student']
            );
            $parentRole = Role::firstOrCreate(
                ['slug' => Role::PARENT],
                ['name' => 'Parent', 'description' => 'Student Guardian / Parent']
            );

            // Keep internal student account emails unique when admission numbers repeat between schools.
            $cleanAdm = strtolower(str_replace([' ', '/', '\\'], '-', $data['admission_number']));
            $studentEmail = $cleanAdm . '-school-' . ($schoolId ?? $student->id) . '@student.cloudkazi.com';
            $studentUser = User::where('email', $studentEmail)->first();
            if ($studentUser && ((int) $studentUser->school_id !== (int) $schoolId || ! $studentUser->isStudent())) {
                throw new \RuntimeException("A different account is already using the student portal email {$studentEmail}.");
            }

            if (! $studentUser) {
                $studentUser = User::create([
                    'school_id' => $schoolId,
                    'role_id' => $studentRole->id,
                    'name' => $student->full_name,
                    'email' => $studentEmail,
                    'password' => Hash::make($data['guardian_phone']),
                    'is_active' => true,
                ]);
            } else {
                $studentUser->update([
                    'role_id' => $studentRole->id,
                    'name' => $student->full_name,
                    'password' => Hash::make($data['guardian_phone']),
                    'is_active' => true,
                ]);
            }

            $student->user()->associate($studentUser);
            $student->save();

            // Parent (guardian) user handling – reuse existing account if email matches
            $parentUser = User::where('email', $data['guardian_email'])->first();
            if ($parentUser && ! $parentUser->isParent()) {
                throw new \RuntimeException("The guardian email {$data['guardian_email']} is already assigned to a non-parent account.");
            }

            if (! $parentUser) {
                $parentUser = User::create([
                    'school_id' => $schoolId,
                    'role_id' => $parentRole->id,
                    'name' => $data['guardian_name'],
                    'email' => $data['guardian_email'],
                    'phone' => $data['guardian_phone'],
                    'password' => Hash::make($data['guardian_phone']), // Phone number as password
                    'is_active' => true,
                ]);
            } else {
                // Ensure phone and password are set to guardian phone
                $parentUser->update([
                    'phone' => $data['guardian_phone'],
                    'password' => Hash::make($data['guardian_phone']),
                ]);
            }

            // Associate parent user with the student
            $student->parent()->associate($parentUser);
            $student->save();

            // Maintain guardian contact record for reporting purposes
            $nameParts = explode(' ', $data['guardian_name'], 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? $nameParts[0];

            $guardian = Guardian::where('school_id', $schoolId)
                ->where('email', $data['guardian_email'])
                ->first();

            if (! $guardian) {
                $guardian = Guardian::create([
                    'school_id' => $schoolId,
                    'user_id' => $parentUser->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $data['guardian_email'],
                    'phone' => $data['guardian_phone'],
                    'relationship' => $data['guardian_relationship'] ?? 'Parent',
                    'occupation' => $data['guardian_occupation'] ?? null,
                ]);
            } else {
                $guardian->update([
                    'user_id' => $parentUser->id,
                    'email' => $data['guardian_email'],
                    'phone' => $data['guardian_phone'],
                ]);
            }

            $student->guardians()->syncWithoutDetaching([
                $guardian->id => ['is_primary' => true],
            ]);
        });
    }
}
