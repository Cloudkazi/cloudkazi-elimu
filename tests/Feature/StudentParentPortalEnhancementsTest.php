<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\CbcAssessment;
use App\Models\Examination;
use App\Models\FeePayment;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentParentPortalEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private SchoolClass $class;
    private Student $student;
    private User $studentUser;
    private User $parentUser;
    private AcademicYear $year;
    private AcademicTerm $term;
    private Subject $subject;
    private Examination $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Portal Test School',
            'code' => 'PORTAL-' . uniqid(),
            'status' => 'active',
        ]);
        $studentRole = Role::firstOrCreate(['slug' => Role::STUDENT], ['name' => 'Student']);
        $parentRole = Role::firstOrCreate(['slug' => Role::PARENT], ['name' => 'Parent']);
        $this->parentUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $parentRole->id,
            'name' => 'Test Parent',
            'email' => 'parent-' . uniqid() . '@portal.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $studentRole->id,
            'name' => 'Test Student',
            'email' => 'student-' . uniqid() . '@portal.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->class = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Grade 7',
            'level' => 'junior_school',
            'curriculum' => 'CBC',
            'numeric_level' => 7,
        ]);
        $this->student = Student::create([
            'school_id' => $this->school->id,
            'user_id' => $this->studentUser->id,
            'parent_user_id' => $this->parentUser->id,
            'school_class_id' => $this->class->id,
            'admission_number' => 'PORTAL-' . uniqid(),
            'first_name' => 'Test',
            'last_name' => 'Student',
            'gender' => 'Female',
            'dob' => '2013-01-01',
            'admission_date' => '2026-01-01',
            'curriculum' => 'CBC',
            'status' => 'active',
        ]);
        $this->year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);
        $this->term = AcademicTerm::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-04-30',
            'is_current' => true,
        ]);
        $this->subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'curriculum' => 'CBC',
        ]);
        $this->exam = Examination::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'term_id' => $this->term->id,
            'name' => 'End Term Assessment',
            'curriculum_type' => 'CBC',
            'exam_type' => 'endterm',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-05',
            'is_published' => true,
        ]);
    }

    public function test_student_and_linked_parent_can_view_filtered_diary_and_cbc_performance(): void
    {
        Announcement::create([
            'school_id' => $this->school->id,
            'title' => 'Student notice',
            'content' => 'Bring the workbook.',
            'target_audience' => 'Students',
            'published_at' => today()->toDateString(),
        ]);
        Announcement::create([
            'school_id' => $this->school->id,
            'title' => 'Parent notice',
            'content' => 'Parent meeting.',
            'target_audience' => 'Parents',
            'published_at' => today()->toDateString(),
        ]);
        LearningMaterial::create([
            'school_id' => $this->school->id,
            'school_class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'title' => 'Fractions assignment',
            'description' => 'Complete questions 1 to 5.',
            'type' => 'assignment',
            'status' => 'published',
            'due_date' => today()->addDays(4)->toDateString(),
        ]);
        CbcAssessment::create([
            'school_id' => $this->school->id,
            'examination_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'strand' => 'Number',
            'sub_strand' => 'Fractions',
            'achievement_level' => 'EE',
            'score_points' => 4,
            'rubric_feedback' => 'Excellent progress.',
        ]);

        $this->actingAs($this->studentUser)
            ->get(route('portal.student.diary'))
            ->assertOk()
            ->assertSee('Student notice')
            ->assertSee('Fractions assignment')
            ->assertDontSee('Parent notice');

        $this->actingAs($this->studentUser)
            ->get(route('portal.student.performance'))
            ->assertOk()
            ->assertSee('Fractions')
            ->assertSee('EE')
            ->assertSee('Excellent progress.')
            ->assertDontSee('Mean marks');

        $this->actingAs($this->parentUser)
            ->get(route('portal.parent.diary', ['student_id' => $this->student->id]))
            ->assertOk()
            ->assertSee('Parent notice')
            ->assertSee('Fractions assignment')
            ->assertDontSee('Student notice');

        $this->actingAs($this->parentUser)
            ->get(route('portal.parent.performance', ['student_id' => $this->student->id]))
            ->assertOk()
            ->assertSee('Excellent progress.');
    }

    public function test_parent_cannot_select_a_child_from_another_school(): void
    {
        $otherSchool = School::create([
            'name' => 'Another School',
            'code' => 'OTHER-' . uniqid(),
            'status' => 'active',
        ]);
        $otherClass = SchoolClass::create([
            'school_id' => $otherSchool->id,
            'name' => 'Grade 7',
            'level' => 'junior_school',
            'curriculum' => 'CBC',
            'numeric_level' => 7,
        ]);
        $otherStudent = Student::create([
            'school_id' => $otherSchool->id,
            'parent_user_id' => $this->parentUser->id,
            'school_class_id' => $otherClass->id,
            'admission_number' => 'OTHER-' . uniqid(),
            'first_name' => 'Other',
            'last_name' => 'Student',
            'gender' => 'Female',
            'dob' => '2013-01-01',
            'admission_date' => '2026-01-01',
            'curriculum' => 'CBC',
            'status' => 'active',
        ]);

        $this->actingAs($this->parentUser)
            ->get(route('portal.parent.performance', ['student_id' => $otherStudent->id]))
            ->assertForbidden();
    }

    public function test_student_dashboard_remains_available_before_practice_migrations_are_applied(): void
    {
        Schema::shouldReceive('hasTable')
            ->andReturnUsing(fn (string $table) => !in_array($table, [
                'learning_chapters',
                'learning_questions',
                'learning_attempts',
                'learning_chapter_progress',
                'portal_announcement_reads',
            ], true));

        $this->actingAs($this->studentUser)
            ->get(route('portal.student'))
            ->assertOk()
            ->assertSee('Student practice is temporarily unavailable.')
            ->assertDontSee('My Practice');
    }

    public function test_student_diary_loads_before_announcement_read_migration_is_applied(): void
    {
        Announcement::create([
            'school_id' => $this->school->id,
            'title' => 'Student notice before migration',
            'content' => 'Diary content remains available.',
            'target_audience' => 'Students',
            'published_at' => today()->toDateString(),
        ]);

        Schema::shouldReceive('hasTable')
            ->once()
            ->with('portal_announcement_reads')
            ->andReturn(false);

        $this->actingAs($this->studentUser)
            ->get(route('portal.student.diary'))
            ->assertOk()
            ->assertSee('Student notice before migration')
            ->assertSee('Diary content remains available.');
    }

    public function test_marking_announcement_read_is_graceful_before_read_migration(): void
    {
        $announcement = Announcement::create([
            'school_id' => $this->school->id,
            'title' => 'Student notice',
            'content' => 'Read tracking not yet installed.',
            'target_audience' => 'Students',
            'published_at' => today()->toDateString(),
        ]);

        Schema::shouldReceive('hasTable')
            ->once()
            ->with('portal_announcement_reads')
            ->andReturn(false);

        $this->actingAs($this->studentUser)
            ->post(route('portal.diary.announcement.read', $announcement))
            ->assertRedirect()
            ->assertSessionHas('info');
    }

    public function test_student_can_download_pdf_for_a_settled_fee_payment(): void
    {
        $invoice = StudentInvoice::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->year->id,
            'term_id' => $this->term->id,
            'invoice_number' => 'INV-' . uniqid(),
            'total_amount' => 1000,
            'paid_amount' => 200,
            'waiver_amount' => 0,
            'balance' => 800,
            'status' => 'partially_paid',
        ]);
        $payment = FeePayment::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'student_invoice_id' => $invoice->id,
            'receipt_number' => 'RCPT-' . strtoupper(uniqid()),
            'amount' => 200,
            'payment_method' => 'cash',
            'reference_code' => 'CASH-200',
            'payment_date' => today()->toDateString(),
            'payment_source' => 'bursar',
        ]);

        $response = $this->actingAs($this->studentUser)
            ->get(route('finance.receipt', ['id' => $payment->id, 'download' => 1]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
