<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\AuthController;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$school = School::create([
    'code' => 'DBG-882',
    'name' => 'Debug Academy 882',
    'email' => 'info@debug882.ac.ke',
    'phone' => '+254700000882',
    'address' => 'Nairobi',
    'county' => 'Nairobi',
    'curriculum_type' => 'CBC',
    'status' => 'active',
]);

$parentRole = Role::firstOrCreate(['slug' => Role::PARENT], ['name' => 'Parent']);
$studentRole = Role::firstOrCreate(['slug' => Role::STUDENT], ['name' => 'Student']);

$parentEmail = 'parent882@example.com';
$parentUser = User::create([
    'school_id' => $school->id,
    'role_id' => $parentRole->id,
    'name' => 'Mary Parent 882',
    'email' => $parentEmail,
    'phone' => '0712345882',
    'password' => Hash::make('0712345882'),
    'is_active' => true,
]);

$class = SchoolClass::create([
    'school_id' => $school->id,
    'name' => 'Grade 1',
    'level' => 'junior_school',
    'curriculum' => 'CBC',
    'numeric_level' => 1,
]);

$guardian = Guardian::create([
    'school_id' => $school->id,
    'user_id' => $parentUser->id,
    'first_name' => 'Mary',
    'last_name' => 'Parent',
    'phone' => '0712345882',
    'email' => $parentEmail,
    'relationship' => 'Mother',
]);

$student = Student::create([
    'school_id' => $school->id,
    'user_id' => null,
    'parent_user_id' => $parentUser->id,
    'admission_number' => 'ADM-882',
    'first_name' => 'Aisha',
    'last_name' => 'Student',
    'gender' => 'Female',
    'dob' => '2016-01-15',
    'admission_date' => '2026-01-05',
    'school_class_id' => $class->id,
    'stream_id' => null,
    'curriculum' => 'CBC',
    'status' => 'active',
]);
$student->guardians()->syncWithoutDetaching([$guardian->id => ['is_primary' => true]]);

$resolved = (new AuthController())->resolveStudentForLogin($parentEmail, 'ADM-882');

echo "parent found: " . (User::whereRaw('LOWER(email) = ?', [strtolower($parentEmail)])->first()?->id ?? 'none') . PHP_EOL;
echo "student id: " . ($student->id) . PHP_EOL;
echo "resolved: " . ($resolved ? $resolved->id : 'none') . PHP_EOL;

$query = Student::query()->whereRaw('LOWER(admission_number) = ?', ['adm-882']);
$query->where(function ($q) use ($parentUser, $parentEmail) {
    $q->where('parent_user_id', $parentUser->id)
        ->orWhereHas('guardians', function ($guardiansQuery) use ($parentUser, $parentEmail) {
            $guardiansQuery->where(function ($subQuery) use ($parentUser, $parentEmail) {
                $subQuery->where('user_id', $parentUser->id)
                    ->orWhereRaw('LOWER(email) = ?', [strtolower($parentEmail)])
                    ->orWhereRaw('LOWER(email) = ?', [strtolower((string) $parentUser->email)]);
            });
        });
});

echo "manual: " . ($query->first()?->id ?? 'none') . PHP_EOL;

echo "sql: " . $query->toSql() . PHP_EOL;

echo "bindings: " . json_encode($query->getBindings()) . PHP_EOL;
