<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    /**
     * Determine whether the user can view the student's profile.
     */
    public function view(User $user, Student $student): bool
    {
        return $user->canAccessStudent($student);
    }

    /**
     * Determine whether the user can view the student's marks and exam results.
     */
    public function viewMarks(User $user, Student $student): bool
    {
        return $user->canAccessStudent($student);
    }

    /**
     * Determine whether the user can view the student's fee invoices and payments.
     */
    public function viewFees(User $user, Student $student): bool
    {
        return $user->canAccessStudent($student);
    }

    /**
     * Determine whether the user can view the student's timetable.
     */
    public function viewTimetable(User $user, Student $student): bool
    {
        return $user->canAccessStudent($student);
    }

    /**
     * Determine whether the user can manage (create, edit, delete) student records.
     */
    public function manage(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasRole([
            Role::SCHOOL_ADMIN,
            Role::PRINCIPAL,
            Role::DEPUTY_PRINCIPAL,
        ]);
    }
}
