<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use App\Models\Teacher;
use App\Models\School;
use App\Models\User;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\AcademicYear;

echo "=== MODEL COUNTS ===\n";
try { echo "Schools: " . School::count() . "\n"; } catch(Exception $e) { echo "Schools ERROR: " . $e->getMessage() . "\n"; }
try { echo "Users: " . User::count() . "\n"; } catch(Exception $e) { echo "Users ERROR: " . $e->getMessage() . "\n"; }
try { echo "Roles: " . Role::count() . "\n"; } catch(Exception $e) { echo "Roles ERROR: " . $e->getMessage() . "\n"; }
try { echo "Students: " . Student::withoutGlobalScopes()->count() . "\n"; } catch(Exception $e) { echo "Students ERROR: " . $e->getMessage() . "\n"; }
try { echo "Teachers: " . Teacher::withoutGlobalScopes()->count() . "\n"; } catch(Exception $e) { echo "Teachers ERROR: " . $e->getMessage() . "\n"; }
try { echo "Classes: " . SchoolClass::withoutGlobalScopes()->count() . "\n"; } catch(Exception $e) { echo "Classes ERROR: " . $e->getMessage() . "\n"; }

echo "\n=== USERS WITH ROLES ===\n";
try {
    $users = User::with(['role', 'school'])->get();
    foreach($users as $u) {
        echo "  [{$u->id}] {$u->name} | email: {$u->email} | role: " . ($u->role ? $u->role->slug : 'NO ROLE') . " | school_id: " . ($u->school_id ?? 'NULL') . "\n";
    }
} catch(Exception $e) { echo "ERROR: " . $e->getMessage() . "\n"; }

echo "\n=== ROLES ===\n";
try {
    Role::all()->each(function($r) { echo "  [{$r->id}] {$r->name} slug:{$r->slug}\n"; });
} catch(Exception $e) { echo "ERROR: " . $e->getMessage() . "\n"; }

echo "\n=== SCHOOLS ===\n";
try {
    School::all()->each(function($s) { echo "  [{$s->id}] {$s->name} status:{$s->status}\n"; });
} catch(Exception $e) { echo "ERROR: " . $e->getMessage() . "\n"; }
