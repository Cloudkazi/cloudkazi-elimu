<?php

namespace Tests\Feature;

use App\Models\Guardian;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Examination;
use App\Models\ExamMark844;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Student;
use App\Models\User;
use App\Http\Controllers\ExaminationController;
use App\Services\StudentEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentAccessAndDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_student_can_login_with_parent_email_and_admission_number(): void
    {
        $school = School::create([
            'code' => 'ACD-001',
            'name' => 'Alpha Crest Academy',
            'email' => 'info@alpha.ac.ke',
            'phone' => '+254700000001',
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

        $studentUser = User::create([
            'school_id' => $school->id,
            'role_id' => $studentRole->id,
            'name' => 'Aisha Student',
            'email' => 'adm-001@student.cloudkazi.com',
            'password' => Hash::make('ADM-001'),
            'is_active' => true,
        ]);
        $student->user()->associate($studentUser);
        $student->save();

        $response = $this->post('/login', [
            'email' => 'parent@example.com',
            'password' => 'ADM-001',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($studentUser);
        $this->assertTrue(auth()->user()->isStudent());

        auth()->logout();

        $response = $this->post('/login', [
            'email' => 'ADM-001',
            'password' => '0712345678',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($studentUser);
    }

    public function test_enrollment_creates_student_and_parent_logins_with_the_requested_credentials(): void
    {
        $school = School::create([
            'code' => 'ENA-001',
            'name' => 'Enrollment Academy',
            'email' => 'info@enrollment.ac.ke',
            'phone' => '+254700000003',
            'address' => 'Nairobi',
            'county' => 'Nairobi',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);
        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Grade 1',
            'level' => 'junior_school',
            'curriculum' => 'CBC',
            'numeric_level' => 1,
        ]);

        StudentEnrollmentService::enroll([
            'school_id' => $school->id,
            'admission_number' => 'ENR-001',
            'first_name' => 'Aisha',
            'last_name' => 'Student',
            'gender' => 'Female',
            'dob' => '2016-01-15',
            'admission_date' => '2026-01-05',
            'school_class_id' => $class->id,
            'curriculum' => 'CBC',
            'guardian_name' => 'Mary Parent',
            'guardian_phone' => '0712345680',
            'guardian_email' => 'mary@example.com',
            'guardian_relationship' => 'Mother',
        ]);

        $student = Student::where('admission_number', 'ENR-001')->firstOrFail();
        $studentUser = $student->user;
        $parentUser = $student->parent;

        $this->assertSame(Role::STUDENT, $studentUser->role->slug);
        $this->assertSame(Role::PARENT, $parentUser->role->slug);
        $this->assertTrue(Hash::check('0712345680', $studentUser->password));
        $this->assertTrue(Hash::check('0712345680', $parentUser->password));

        $studentLogin = $this->post('/login', [
            'email' => 'ENR-001',
            'password' => '0712345680',
        ]);
        $studentLogin->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($studentUser);

        auth()->logout();

        $parentLogin = $this->post('/login', [
            'email' => 'mary@example.com',
            'password' => '0712345680',
        ]);
        $parentLogin->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($parentUser);
    }

    public function test_report_card_lists_all_exams_from_the_selected_academic_year(): void
    {
        $school = School::create([
            'code' => 'RCA-001',
            'name' => 'Report Card Academy',
            'email' => 'info@reportcard.ac.ke',
            'phone' => '+254700000004',
            'address' => 'Nairobi',
            'county' => 'Nairobi',
            'curriculum_type' => '8-4-4',
            'status' => 'active',
        ]);
        $studentRole = Role::firstOrCreate(['slug' => Role::STUDENT], ['name' => 'Student']);
        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Form 1',
            'level' => 'secondary_844',
            'curriculum' => '8-4-4',
            'numeric_level' => 1,
        ]);
        $student = Student::create([
            'school_id' => $school->id,
            'admission_number' => 'RCA-001',
            'first_name' => 'Sam',
            'last_name' => 'Learner',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'admission_date' => '2026-01-01',
            'school_class_id' => $class->id,
            'curriculum' => '8-4-4',
            'status' => 'active',
        ]);
        $studentUser = User::create([
            'school_id' => $school->id,
            'role_id' => $studentRole->id,
            'name' => $student->full_name,
            'email' => 'rca-001@student.cloudkazi.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $student->user()->associate($studentUser);
        $student->save();

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);
        $termOne = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-05',
            'end_date' => '2026-04-01',
        ]);
        $termTwo = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'start_date' => '2026-05-01',
            'end_date' => '2026-08-01',
        ]);
        $otherYear = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2027',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);
        $otherTerm = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $otherYear->id,
            'name' => '2027 Term 1',
            'start_date' => '2027-01-05',
            'end_date' => '2027-04-01',
        ]);
        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'curriculum' => '8-4-4',
        ]);

        $exams = collect([
            ['name' => '2026 Opener Exam', 'term' => $termOne, 'date' => '2026-02-01'],
            ['name' => '2026 Midterm Exam', 'term' => $termTwo, 'date' => '2026-06-01'],
            ['name' => '2027 Opener Exam', 'term' => $otherTerm, 'date' => '2027-02-01'],
        ])->map(function ($data) use ($school, $year, $otherYear) {
            $examYear = str_starts_with($data['name'], '2027') ? $otherYear : $year;
            return Examination::create([
                'school_id' => $school->id,
                'academic_year_id' => $examYear->id,
                'term_id' => $data['term']->id,
                'name' => $data['name'],
                'curriculum_type' => '8-4-4',
                'exam_type' => 'endterm',
                'start_date' => $data['date'],
                'end_date' => $data['date'],
                'is_published' => true,
            ]);
        });

        foreach ($exams as $index => $exam) {
            ExamMark844::create([
                'school_id' => $school->id,
                'examination_id' => $exam->id,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'score' => 70 + $index,
                'out_of' => 100,
                'grade' => 'B',
                'points' => 8,
            ]);
        }

        $this->actingAs($studentUser);
        $report = app(ExaminationController::class)->reportCard($exams[0]->id, $student->id);
        $html = $report->with('errors', new \Illuminate\Support\ViewErrorBag())->render();

        $this->assertStringContainsString('2026 Opener Exam', $html);
        $this->assertStringContainsString('2026 Midterm Exam', $html);
        $this->assertStringNotContainsString('2027 Opener Exam', $html);
        $this->assertSame(2, substr_count($html, 'Mathematics'));
    }

    public function test_invalid_student_admission_number_fails_login(): void
    {
        $school = School::create([
            'code' => 'ACD-002',
            'name' => 'Alpha Crest Academy Two',
            'email' => 'info2@alpha.ac.ke',
            'phone' => '+254700000002',
            'address' => 'Mombasa',
            'county' => 'Mombasa',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        $parentRole = Role::firstOrCreate(['slug' => Role::PARENT], ['name' => 'Parent']);
        $parentUser = User::create([
            'school_id' => $school->id,
            'role_id' => $parentRole->id,
            'name' => 'Mary Parent',
            'email' => 'parent2@example.com',
            'phone' => '0712345679',
            'password' => Hash::make('0712345679'),
            'is_active' => true,
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Grade 2',
            'level' => 'junior_school',
            'curriculum' => 'CBC',
            'numeric_level' => 2,
        ]);

        $guardian = Guardian::create([
            'school_id' => $school->id,
            'user_id' => $parentUser->id,
            'first_name' => 'Mary',
            'last_name' => 'Parent',
            'phone' => '0712345679',
            'email' => 'parent2@example.com',
            'relationship' => 'Mother',
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'user_id' => null,
            'parent_user_id' => $parentUser->id,
            'admission_number' => 'ADM-002',
            'first_name' => 'John',
            'last_name' => 'Student',
            'gender' => 'Male',
            'dob' => '2015-03-10',
            'admission_date' => '2026-01-05',
            'school_class_id' => $class->id,
            'stream_id' => null,
            'curriculum' => 'CBC',
            'status' => 'active',
        ]);
        $student->guardians()->syncWithoutDetaching([$guardian->id => ['is_primary' => true]]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'parent2@example.com',
                'password' => 'ADM-333',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
