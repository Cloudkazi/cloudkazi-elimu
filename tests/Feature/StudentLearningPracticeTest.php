<?php

namespace Tests\Feature;

use App\Models\LearningAttempt;
use App\Models\LearningChapter;
use App\Models\LearningChapterProgress;
use App\Models\LearningQuestion;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentLearningPracticeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private SchoolClass $class;
    private Student $student;
    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Practice Test School',
            'code' => 'PRACTICE-' . uniqid(),
            'status' => 'active',
        ]);
        $role = Role::firstOrCreate(['slug' => Role::STUDENT], ['name' => 'Student']);
        $this->studentUser = User::create([
            'school_id' => $this->school->id,
            'role_id' => $role->id,
            'name' => 'Practice Student',
            'email' => 'practice-' . uniqid() . '@student.test',
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
            'school_class_id' => $this->class->id,
            'admission_number' => 'PRACTICE-' . uniqid(),
            'first_name' => 'Practice',
            'last_name' => 'Student',
            'gender' => 'Female',
            'dob' => '2013-01-01',
            'admission_date' => '2026-01-01',
            'curriculum' => 'CBC',
            'status' => 'active',
        ]);
    }

    public function test_student_can_answer_published_question_and_progress_is_saved(): void
    {
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'curriculum' => 'CBC',
        ]);
        $chapter = LearningChapter::create([
            'school_id' => $this->school->id,
            'school_class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'title' => 'Number system',
            'sequence' => 1,
            'is_published' => true,
        ]);
        $question = LearningQuestion::create([
            'school_id' => $this->school->id,
            'learning_chapter_id' => $chapter->id,
            'prompt' => 'What is 2 + 2?',
            'question_type' => 'multiple_choice',
            'options' => ['3', '4', '5'],
            'correct_answer' => '4',
            'explanation' => 'Add the two numbers.',
            'difficulty' => 'easy',
            'is_published' => true,
        ]);

        $this->actingAs($this->studentUser)
            ->get(route('portal.practice'))
            ->assertOk()
            ->assertSee('Number system')
            ->assertSee('What is 2 + 2?')
            ->assertDontSee('Add the two numbers.');

        $this->actingAs($this->studentUser)
            ->post(route('portal.practice.answer', $question), ['answer' => '4'])
            ->assertRedirect(route('portal.practice', ['subject_id' => $subject->id]));

        $this->assertDatabaseHas('learning_attempts', [
            'student_id' => $this->student->id,
            'learning_question_id' => $question->id,
            'answer' => '4',
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('learning_chapter_progress', [
            'student_id' => $this->student->id,
            'learning_chapter_id' => $chapter->id,
            'attempts_count' => 1,
            'correct_count' => 1,
            'mastery_percent' => 100,
        ]);
    }

    public function test_student_cannot_answer_question_from_another_class(): void
    {
        $otherClass = SchoolClass::create([
            'school_id' => $this->school->id,
            'name' => 'Grade 8',
            'level' => 'junior_school',
            'curriculum' => 'CBC',
            'numeric_level' => 8,
        ]);
        $subject = Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Science',
            'code' => 'SCI',
            'curriculum' => 'CBC',
        ]);
        $chapter = LearningChapter::create([
            'school_id' => $this->school->id,
            'school_class_id' => $otherClass->id,
            'subject_id' => $subject->id,
            'title' => 'Other grade content',
            'sequence' => 1,
            'is_published' => true,
        ]);
        $question = LearningQuestion::create([
            'school_id' => $this->school->id,
            'learning_chapter_id' => $chapter->id,
            'prompt' => 'Question from another grade',
            'question_type' => 'short_answer',
            'correct_answer' => 'answer',
            'difficulty' => 'medium',
            'is_published' => true,
        ]);

        $this->actingAs($this->studentUser)
            ->post(route('portal.practice.answer', $question), ['answer' => 'answer'])
            ->assertForbidden();
        $this->assertSame(0, LearningAttempt::count());
        $this->assertSame(0, LearningChapterProgress::count());
    }

    public function test_practice_page_explains_when_migrations_are_not_applied(): void
    {
        Schema::shouldReceive('hasTable')
            ->once()
            ->with('learning_chapters')
            ->andReturn(false);

        $this->actingAs($this->studentUser)
            ->get(route('portal.practice'))
            ->assertOk()
            ->assertSee('Practice is temporarily unavailable')
            ->assertDontSee('Your school has not published practice chapters');
    }

    public function test_answer_endpoint_returns_service_unavailable_when_practice_tables_are_missing(): void
    {
        Schema::shouldReceive('hasTable')
            ->once()
            ->with('learning_chapters')
            ->andReturn(false);

        $this->actingAs($this->studentUser)
            ->post(route('portal.practice.answer', 123), ['answer' => 'answer'])
            ->assertServiceUnavailable();
    }
}
