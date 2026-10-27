<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ensure staff roles have is_staff = true and all staff roles are registered.
     */
    public function up(): void
    {
        $staffRoles = [
            [
                'slug'        => 'principal',
                'name'        => 'Principal / Head Teacher',
                'description' => 'School Principal & Head of Institution',
            ],
            [
                'slug'        => 'deputy-principal',
                'name'        => 'Deputy Principal',
                'description' => 'Deputy Principal & Head of Academics/Discipline',
            ],
            [
                'slug'        => 'teacher',
                'name'        => 'Teacher',
                'description' => 'Subject Teacher',
            ],
            [
                'slug'        => 'bursar',
                'name'        => 'Bursar / Accountant',
                'description' => 'Finance Officer, Bursar & Accountant',
            ],
            [
                'slug'        => 'librarian',
                'name'        => 'Librarian',
                'description' => 'School Librarian',
            ],
            [
                'slug'        => 'examinations-officer',
                'name'        => 'Examinations Officer',
                'description' => 'Examinations & Assessment Coordinator',
            ],
            [
                'slug'        => 'secretary',
                'name'        => 'Secretary / Front Office',
                'description' => 'School Secretary & Front Office Administrator',
            ],
            [
                'slug'        => 'ict-admin',
                'name'        => 'ICT Administrator',
                'description' => 'ICT Support & Systems Administrator',
            ],
            [
                'slug'        => 'discipline-officer',
                'name'        => 'Discipline / Welfare Officer',
                'description' => 'Student Discipline & Welfare Officer',
            ],
            [
                'slug'        => 'hostel-warden',
                'name'        => 'Hostel Warden',
                'description' => 'Boarding / Hostel Master/Mistress',
            ],
        ];

        foreach ($staffRoles as $roleData) {
            $existing = DB::table('roles')->where('slug', $roleData['slug'])->first();
            if ($existing) {
                DB::table('roles')->where('slug', $roleData['slug'])->update([
                    'is_staff'   => true,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('roles')->insert([
                    'name'        => $roleData['name'],
                    'slug'        => $roleData['slug'],
                    'description' => $roleData['description'],
                    'is_staff'    => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')
            ->whereIn('slug', [
                'examinations-officer',
                'secretary',
                'ict-admin',
                'discipline-officer',
            ])->delete();

        DB::table('roles')->update(['is_staff' => false]);
    }
};
