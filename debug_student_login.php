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
    'code' => 'DBG-001',
    'name' => 'Debug Academy',
    'email' => 'info@debug.ac.ke',
    'phone' => '+254700000099',
    'address' => 'Nairobi',
    'county' => 'Nairobi',
    'curriculum_type' => 'CBC',
    'status' => 'active',
]);

$parentRole = Role::firstOrCreate(['slug' => Role::PARENT], ['name' => 'Parent']);
$studentRole = Role::firstOrCreate(['slug' => Role::STUDENT], ['name' => 'Student']);

$parentUser = User::create([
    'school_id' => $school->id,
    'role_id' => $parentRole->id,
    'name' => 'Mary Parent',
    'email' => 'parent@example.com',
    'phone' => '0712345678',
    'password' => Hash::make('0712345678'),
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
    'phone' => '0712345678',
    'email' => 'parent@example.com',
    'relationship' => 'Mother',
]);

$student = Student::create([
    'school_id' => $school->id,
    'user_id' => null,
    'parent_user_id' => $parentUser->id,
    'admission_number' => 'ADM-001',
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

$controller = new AuthController();
$resolved = $controller->resolveStudentForLogin('parent@example.com', 'ADM-001');
var_dump($resolved ? $resolved->id : 'NULL');
var_dump(User::where('email', 'parent@example.com')->first()->id);
var_dump(Student::where('admission_number', 'ADM-001')->first()->id);
var_dump(Student::query()->whereRaw('LOWER(admission_number) = ?', ['adm-001'])->where(function ($q) use ($parentUser) {
    $q->where('parent_user_id', $parentUser->id)
      ->orWhereHas('guardians', function ($gq) use ($parentUser) {
          $gq->where('user_id', $parentUser->id)
             ->orWhereRaw('LOWER(email) = ?', ['parent@example.com']);
      });
})->first()?->id ?? 'NULL');
