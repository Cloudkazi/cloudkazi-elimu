<?php
/**
 * CloudKazi Elimu — Staff Role Synchronizer
 * 
 * Safely inserts missing roles and sets is_staff flags.
 * Does NOT delete existing roles or modify existing users.
 */
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Role;

echo "=== CloudKazi Staff Role Synchronizer ===\n\n";

/**
 * Full role definition table.
 * is_staff=true → appears in Add Staff dropdown
 * is_staff=false → system/student/parent role
 */
$roleDefinitions = [
    // ── Platform / System Roles (is_staff = false) ────────────────────────────
    [
        'name'        => 'Super Admin',
        'slug'        => Role::SUPER_ADMIN,
        'description' => 'CloudKazi HQ Platform Administrator',
        'is_staff'    => false,
    ],
    [
        'name'        => 'School Admin',
        'slug'        => Role::SCHOOL_ADMIN,
        'description' => 'School IT Administrator & System Manager',
        'is_staff'    => false,
    ],

    // ── Non-Staff Roles (is_staff = false) ────────────────────────────────────
    [
        'name'        => 'Parent / Guardian',
        'slug'        => Role::PARENT,
        'description' => 'Student Guardian or Parent',
        'is_staff'    => false,
    ],
    [
        'name'        => 'Student',
        'slug'        => Role::STUDENT,
        'description' => 'Enrolled Student',
        'is_staff'    => false,
    ],

    // ── Exactly the 10 Normal Staff Roles (is_staff = true) ───────────────────
    [
        'name'        => 'Principal / Head Teacher',
        'slug'        => Role::PRINCIPAL,
        'description' => 'School Principal & Head of Institution',
        'is_staff'    => true,
    ],
    [
        'name'        => 'Deputy Principal',
        'slug'        => Role::DEPUTY_PRINCIPAL,
        'description' => 'Deputy Principal & Head of Academics/Discipline',
        'is_staff'    => true,
    ],
    [
        'name'        => 'Teacher',
        'slug'        => Role::TEACHER,
        'description' => 'Subject Teacher',
        'is_staff'    => true,
    ],
    [
        'name'        => 'Bursar / Accountant',
        'slug'        => Role::BURSAR,
        'description' => 'Finance Officer, Bursar & Accountant',
        'is_staff'    => true,
    ],
    [
        'name'        => 'Librarian',
        'slug'        => Role::LIBRARIAN,
        'description' => 'School Librarian',
        'is_staff'    => true,
    ],
    [
        'name'        => 'Examinations Officer',
        'slug'        => Role::EXAMINATIONS_OFFICER,
        'description' => 'Examinations & Assessment Coordinator',
        'is_staff'    => true,
    ],
    [
        'name'        => 'Secretary / Front Office',
        'slug'        => Role::SECRETARY,
        'description' => 'School Secretary & Front Office Administrator',
        'is_staff'    => true,
    ],
    [
        'name'        => 'ICT Administrator',
        'slug'        => Role::ICT_ADMIN,
        'description' => 'ICT Support & Systems Administrator',
        'is_staff'    => true,
    ],
    [
        'name'        => 'Discipline / Welfare Officer',
        'slug'        => Role::DISCIPLINE_OFFICER,
        'description' => 'Student Discipline & Welfare Officer',
        'is_staff'    => true,
    ],
    [
        'name'        => 'Dorm Master',
        'slug'        => Role::HOSTEL_WARDEN,
        'description' => 'Boarding / Dormitory Master or Mistress',
        'is_staff'    => true,
    ],
];


$inserted = [];
$updated  = [];
$skipped  = [];

foreach ($roleDefinitions as $def) {
    $existing = DB::table('roles')->where('slug', $def['slug'])->first();

    if (!$existing) {
        DB::table('roles')->insert(array_merge($def, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $inserted[] = $def['name'] . ' (' . $def['slug'] . ')';
        echo "[INSERT] " . $def['name'] . " | slug=" . $def['slug'] . " | is_staff=" . ($def['is_staff'] ? 'YES' : 'no') . "\n";
    } else {
        // Update is_staff flag + display name if needed, but do NOT change the slug
        $needsUpdate = false;
        $updates = ['updated_at' => now()];

        if ((bool)$existing->is_staff !== (bool)$def['is_staff']) {
            $updates['is_staff'] = $def['is_staff'];
            $needsUpdate = true;
        }
        // Update display name to match required naming (e.g., "Bursar" → "Bursar / Accountant")
        if ($existing->name !== $def['name']) {
            $updates['name'] = $def['name'];
            $needsUpdate = true;
        }
        // Update description
        if ($existing->description !== $def['description']) {
            $updates['description'] = $def['description'];
            $needsUpdate = true;
        }

        if ($needsUpdate) {
            DB::table('roles')->where('slug', $def['slug'])->update($updates);
            $updated[] = $def['name'] . ' (' . $def['slug'] . ')';
            echo "[UPDATE] " . $def['name'] . " | slug=" . $def['slug'] . " | is_staff=" . ($def['is_staff'] ? 'YES' : 'no') . "\n";
        } else {
            $skipped[] = $def['name'];
            echo "[OK    ] " . $def['name'] . " | already correct\n";
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Inserted: " . count($inserted) . "\n";
if ($inserted) echo "  → " . implode("\n  → ", $inserted) . "\n";
echo "Updated : " . count($updated) . "\n";
if ($updated) echo "  → " . implode("\n  → ", $updated) . "\n";
echo "Skipped : " . count($skipped) . "\n";

echo "\n=== FINAL ROLES TABLE ===\n";
$roles = DB::table('roles')->orderBy('is_staff', 'desc')->orderBy('id')->get();
echo str_pad("ID", 5) . str_pad("SLUG", 25) . str_pad("NAME", 35) . "IS_STAFF\n";
echo str_repeat("-", 75) . "\n";
foreach ($roles as $r) {
    echo str_pad($r->id, 5)
       . str_pad($r->slug, 25)
       . str_pad($r->name, 35)
       . ($r->is_staff ? "YES" : "no") . "\n";
}

echo "\n=== STAFF ROLES (will appear in Add Staff) ===\n";
$staffRoles = DB::table('roles')->where('is_staff', true)->orderBy('id')->get();
foreach ($staffRoles as $r) {
    echo "  - {$r->name} ({$r->slug})\n";
}

echo "\nDone. No existing users were modified.\n";
