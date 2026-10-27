<?php

namespace Tests\Feature;

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ExaminationController;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\TimetableAllocation;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\AcademicAssessmentService;
use App\Services\TimetableEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AcademicAttendanceRegressionTest extends TestCase
{
    public function test_examination_index_creates_default_year_and_term_options_when_missing(): void
    {
        $school = School::create([
            'name' => 'Regression School',
            'code' => 'REG-TEST-1',
            'email' => 'admin@regression.test',
            'phone' => '0712345678',
            'county' => 'Nairobi',
            'curriculum_type' => 'Hybrid',
            'status' => 'active',
        ]);

        Session::put('active_school_id', $school->id);

        $controller = new ExaminationController(app(AcademicAssessmentService::class));
        $response = $controller->index();

        $data = $response->getData();

        $this->assertGreaterThan(0, $data['years']->count());
        $this->assertGreaterThan(0, $data['terms']->count());
        $this->assertTrue($data['years']->contains(fn ($year) => $year->school_id === $school->id));
    }

    public function test_attendance_index_loads_available_classes_for_the_school(): void
    {
        $school = School::create([
            'name' => 'Attendance School',
            'code' => 'ATT-TEST-1',
            'email' => 'attendance@regression.test',
            'phone' => '0712345679',
            'county' => 'Kiambu',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        Session::put('active_school_id', $school->id);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Form 1',
            'level' => 'secondary_844',
            'curriculum' => '8-4-4',
            'numeric_level' => 1,
        ]);

        Stream::create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'name' => 'North',
            'capacity' => 40,
        ]);

        $response = (new AttendanceController())->index(new Request(['date' => '2026-09-19']));
        $data = $response->getData();

        $this->assertNotEmpty($data['classes']);
        $this->assertTrue($data['classes']->contains('id', $class->id));
    }

    public function test_timetable_generate_get_shows_form_without_creating_timetable(): void
    {
        $school = School::create([
            'name' => 'Timetable School',
            'code' => 'TT-TEST-1',
            'email' => 'timetable@regression.test',
            'phone' => '0712345680',
            'county' => 'Nakuru',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Timetable Admin',
            'email' => 'admin@timetable.test',
            'password' => bcrypt('password'),
            'phone' => '0712345681',
            'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-05',
            'end_date' => '2026-03-30',
            'is_current' => true,
        ]);

        $this->actingAs($user);
        Session::put('active_school_id', $school->id);

        $response = $this->get(route('timetable.generate'));

        $response->assertOk();
        $response->assertViewIs('timetable.generate');
        $this->assertDatabaseMissing('timetables', ['school_id' => $school->id]);
    }

    public function test_timetable_generate_route_redirects_to_index_and_creates_timetable(): void
    {
        $school = School::create([
            'name' => 'Timetable School',
            'code' => 'TT-TEST-2',
            'email' => 'timetable2@regression.test',
            'phone' => '0712345682',
            'county' => 'Nakuru',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Timetable Admin',
            'email' => 'admin2@timetable.test',
            'password' => bcrypt('password'),
            'phone' => '0712345683',
            'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-05',
            'end_date' => '2026-03-30',
            'is_current' => true,
        ]);

        $this->actingAs($user);
        Session::put('active_school_id', $school->id);

        $response = $this->post(route('timetable.generate'));

        $response->assertRedirect(route('timetable.index'));
        $this->assertDatabaseHas('timetables', ['school_id' => $school->id]);
        $this->assertGreaterThanOrEqual(1, Timetable::where('school_id', $school->id)->count());
    }

    public function test_timetable_generator_validates_real_teacher_subject_assignments_before_scheduling(): void
    {
        $school = School::create([
            'name' => 'Readiness School',
            'code' => 'TT-TEST-3',
            'email' => 'readiness@regression.test',
            'phone' => '0712345684',
            'county' => 'Mombasa',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        $teacher = Teacher::create([
            'school_id' => $school->id,
            'user_id' => User::create([
                'school_id' => $school->id,
                'name' => 'Science Teacher',
                'email' => 'science@readiness.test',
                'password' => bcrypt('password'),
                'phone' => '0712345685',
                'is_active' => true,
            ])->id,
            'tsc_number' => 'TSC-101',
            'max_weekly_lessons' => 25,
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Form 2',
            'level' => 'secondary_844',
            'curriculum' => '8-4-4',
            'numeric_level' => 2,
        ]);

        $stream = Stream::create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'name' => 'East',
            'capacity' => 35,
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Biology',
            'code' => 'BIO',
            'curriculum' => '8-4-4',
            'pathway' => 'general',
            'lessons_per_week' => 4,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-05',
            'end_date' => '2026-03-30',
            'is_current' => true,
        ]);

        $timetable = Timetable::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => '2026 Term 1',
            'type' => 'class',
            'status' => 'draft',
        ]);

        $readiness = app(TimetableEngineService::class)->getReadinessReport($timetable);
        $this->assertTrue(isset($readiness['requirements']));
        $this->assertSame(0, $readiness['missing_teacher_assignments']);
    }

    public function test_timetable_matrix_uses_real_subject_and_teacher_names(): void
    {
        $school = School::create([
            'name' => 'Matrix School',
            'code' => 'TT-TEST-4',
            'email' => 'matrix@regression.test',
            'phone' => '0712345684',
            'county' => 'Mombasa',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Grace Njeri',
            'email' => 'grace@matrix.test',
            'password' => bcrypt('password'),
            'phone' => '0712345685',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'tsc_number' => 'TSC-001',
            'max_weekly_lessons' => 25,
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Form 2',
            'level' => 'secondary_844',
            'curriculum' => '8-4-4',
            'numeric_level' => 2,
        ]);

        $stream = Stream::create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'name' => 'East',
            'capacity' => 35,
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Biology',
            'code' => 'BIO',
            'curriculum' => '8-4-4',
            'pathway' => 'general',
            'lessons_per_week' => 4,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-05',
            'end_date' => '2026-03-30',
            'is_current' => true,
        ]);

        $timetable = Timetable::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => '2026 Term 1',
            'type' => 'class',
            'status' => 'published',
        ]);

        $slot = TimetableSlot::create([
            'school_id' => $school->id,
            'timetable_id' => $timetable->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'is_break' => false,
        ]);

        TimetableAllocation::create([
            'school_id' => $school->id,
            'timetable_id' => $timetable->id,
            'timetable_slot_id' => $slot->id,
            'stream_id' => $stream->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => null,
        ]);

        $matrix = app(TimetableEngineService::class)->getStreamTimetableMatrix($timetable->id, $stream->id);

        $this->assertSame('Biology', $matrix['Monday'][1]['subject_name']);
        $this->assertSame('Grace Njeri', $matrix['Monday'][1]['teacher_name']);
    }

    public function test_school_overview_matrix_uses_real_class_subject_and_teacher_names(): void
    {
        $school = School::create([
            'name' => 'Overview School',
            'code' => 'TT-TEST-5',
            'email' => 'overview@regression.test',
            'phone' => '0712345686',
            'county' => 'Kisumu',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Alice Otieno',
            'email' => 'alice@overview.test',
            'password' => bcrypt('password'),
            'phone' => '0712345687',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'tsc_number' => 'TSC-101',
            'max_weekly_lessons' => 30,
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Form 3',
            'level' => 'secondary_844',
            'curriculum' => '8-4-4',
            'numeric_level' => 3,
        ]);

        $stream = Stream::create([
            'school_id' => $school->id,
            'school_class_id' => $class->id,
            'name' => 'West',
            'capacity' => 36,
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Mathematics',
            'code' => 'MATH',
            'curriculum' => '8-4-4',
            'pathway' => 'general',
            'lessons_per_week' => 5,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-05',
            'end_date' => '2026-03-30',
            'is_current' => true,
        ]);

        $timetable = Timetable::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => '2026 Term 1',
            'type' => 'class',
            'status' => 'published',
        ]);

        $slot = TimetableSlot::create([
            'school_id' => $school->id,
            'timetable_id' => $timetable->id,
            'day_of_week' => 'Tuesday',
            'period_number' => 2,
            'start_time' => '08:45:00',
            'end_time' => '09:30:00',
            'is_break' => false,
        ]);

        TimetableAllocation::create([
            'school_id' => $school->id,
            'timetable_id' => $timetable->id,
            'timetable_slot_id' => $slot->id,
            'stream_id' => $stream->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => null,
        ]);

        $matrix = app(TimetableEngineService::class)->getSchoolTimetableMatrix($timetable->id);

        $this->assertSame('Form 3', $matrix['Tuesday'][2][0]['class_name']);
        $this->assertSame('Mathematics', $matrix['Tuesday'][2][0]['subject_name']);
        $this->assertSame('Alice Otieno', $matrix['Tuesday'][2][0]['teacher_name']);
    }

    public function test_timetable_generation_blocks_when_no_real_teacher_subject_allocations_exist(): void
    {
        $school = School::create([
            'name' => 'Empty Data School',
            'code' => 'TT-TEST-6',
            'email' => 'empty@regression.test',
            'phone' => '0712345688',
            'county' => 'Nyeri',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'School Admin',
            'email' => 'admin@empty.test',
            'password' => bcrypt('password'),
            'phone' => '0712345689',
            'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-05',
            'end_date' => '2026-03-30',
            'is_current' => true,
        ]);

        $this->actingAs($user);
        Session::put('active_school_id', $school->id);

        $response = $this->post(route('timetable.generate'));

        $response->assertRedirect(route('timetable.index'));
        $response->assertSessionHas('warning');
        $this->assertStringContainsString('No teacher-subject allocations were found for the current school.', session('warning'));
    }

    public function test_timetable_index_does_not_report_empty_data_when_teacher_subject_allocations_exist(): void
    {
        $school = School::create([
            'name' => 'Allocation School',
            'code' => 'TT-TEST-7',
            'email' => 'allocation@regression.test',
            'phone' => '0712345690',
            'county' => 'Nairobi',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Allocation Admin',
            'email' => 'admin@allocation.test',
            'password' => bcrypt('password'),
            'phone' => '0712345691',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'school_id' => $school->id,
            'user_id' => User::create([
                'school_id' => $school->id,
                'name' => 'Allocation Teacher',
                'email' => 'teacher@allocation.test',
                'password' => bcrypt('password'),
                'phone' => '0712345692',
                'is_active' => true,
            ])->id,
            'tsc_number' => 'TSC-777',
            'max_weekly_lessons' => 25,
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Form 1',
            'level' => 'secondary_844',
            'curriculum' => '8-4-4',
            'numeric_level' => 1,
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Geography',
            'code' => 'GEO',
            'curriculum' => '8-4-4',
            'pathway' => 'general',
            'lessons_per_week' => 4,
        ]);

        \App\Models\TeacherSubjectAllocation::create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
            'stream_id' => null,
            'lessons_per_week' => 4,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-05',
            'end_date' => '2026-03-30',
            'is_current' => true,
        ]);

        Timetable::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => '2026 Term 1',
            'type' => 'class',
            'status' => 'draft',
        ]);

        $this->actingAs($user);
        Session::put('active_school_id', $school->id);

        $response = $this->get(route('timetable.index'));

        $response->assertOk();
        $response->assertDontSee('This school has no real timetable allocations yet');
    }

    public function test_timetable_index_uses_latest_published_timetable_for_the_school(): void
    {
        $school = School::create([
            'name' => 'Latest Published School',
            'code' => 'TT-TEST-8',
            'email' => 'latestpub@regression.test',
            'phone' => '0712345693',
            'county' => 'Mombasa',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Latest Published Admin',
            'email' => 'admin@latestpub.test',
            'password' => bcrypt('password'),
            'phone' => '0712345694',
            'is_active' => true,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-05',
            'end_date' => '2026-03-30',
            'is_current' => true,
        ]);

        $older = Timetable::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => 'Older Published Schedule',
            'type' => 'class',
            'status' => 'published',
        ]);

        $latest = Timetable::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => 'Latest Published Schedule',
            'type' => 'class',
            'status' => 'published',
        ]);

        $latestSlot = TimetableSlot::create([
            'school_id' => $school->id,
            'timetable_id' => $latest->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'is_break' => false,
        ]);

        TimetableAllocation::create([
            'school_id' => $school->id,
            'timetable_id' => $latest->id,
            'timetable_slot_id' => $latestSlot->id,
            'stream_id' => null,
            'subject_id' => Subject::create([
                'school_id' => $school->id,
                'name' => 'Biology',
                'code' => 'BIO',
                'curriculum' => '8-4-4',
                'pathway' => 'general',
                'lessons_per_week' => 4,
            ])->id,
            'teacher_id' => Teacher::create([
                'school_id' => $school->id,
                'user_id' => User::create([
                    'school_id' => $school->id,
                    'name' => 'Latest Teacher',
                    'email' => 'teacher@latestpub.test',
                    'password' => bcrypt('password'),
                    'phone' => '0712345695',
                    'is_active' => true,
                ])->id,
                'tsc_number' => 'TSC-889',
                'max_weekly_lessons' => 25,
            ])->id,
            'classroom_id' => null,
        ]);

        $this->actingAs($user);
        Session::put('active_school_id', $school->id);

        $response = $this->get(route('timetable.index'));

        $response->assertOk();
        $response->assertViewHas('activeTimetable', fn ($timetable) => $timetable && $timetable->id === $latest->id);
        $this->assertNotEquals($older->id, $response->viewData('activeTimetable')->id);
    }

    public function test_timetable_matrix_keeps_class_level_allocation_when_stream_is_null(): void
    {
        $school = School::create([
            'name' => 'Class Level Matrix School',
            'code' => 'TT-TEST-9',
            'email' => 'matrixclass@regression.test',
            'phone' => '0712345696',
            'county' => 'Mombasa',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Matrix Teacher',
            'email' => 'matrixteacher@regression.test',
            'password' => bcrypt('password'),
            'phone' => '0712345697',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'tsc_number' => 'TSC-991',
            'max_weekly_lessons' => 25,
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Form 1',
            'level' => 'secondary_844',
            'curriculum' => '8-4-4',
            'numeric_level' => 1,
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'Biology',
            'code' => 'BIO',
            'curriculum' => '8-4-4',
            'pathway' => 'general',
            'lessons_per_week' => 4,
        ]);

        \App\Models\TeacherSubjectAllocation::create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
            'stream_id' => null,
            'lessons_per_week' => 4,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-05',
            'end_date' => '2026-03-30',
            'is_current' => true,
        ]);

        $timetable = Timetable::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => '2026 Term 1',
            'type' => 'class',
            'status' => 'published',
        ]);

        $slot = TimetableSlot::create([
            'school_id' => $school->id,
            'timetable_id' => $timetable->id,
            'day_of_week' => 'Monday',
            'period_number' => 1,
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'is_break' => false,
        ]);

        $allocation = TimetableAllocation::create([
            'school_id' => $school->id,
            'timetable_id' => $timetable->id,
            'timetable_slot_id' => $slot->id,
            'stream_id' => null,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => null,
        ]);

        $matrix = app(TimetableEngineService::class)->getSchoolTimetableMatrix($timetable->id);

        $this->assertNotEmpty($matrix['Monday'][1]);
        $this->assertSame('Biology', $matrix['Monday'][1][0]['subject_name']);
        $this->assertSame('Matrix Teacher', $matrix['Monday'][1][0]['teacher_name']);
        $this->assertSame('Form 1', $matrix['Monday'][1][0]['class_name']);
    }

    public function test_timetable_generation_schedules_class_level_workload_when_stream_is_null(): void
    {
        $school = School::create([
            'name' => 'Class Level School',
            'code' => 'TT-TEST-10',
            'email' => 'classlevel@regression.test',
            'phone' => '0712345693',
            'county' => 'Mombasa',
            'curriculum_type' => 'CBC',
            'status' => 'active',
        ]);

        $teacherUser = User::create([
            'school_id' => $school->id,
            'name' => 'Class Teacher',
            'email' => 'classteacher@regression.test',
            'password' => bcrypt('password'),
            'phone' => '0712345694',
            'is_active' => true,
        ]);

        $teacher = Teacher::create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
            'tsc_number' => 'TSC-888',
            'max_weekly_lessons' => 25,
        ]);

        $class = SchoolClass::create([
            'school_id' => $school->id,
            'name' => 'Form 4',
            'level' => 'secondary_844',
            'curriculum' => '8-4-4',
            'numeric_level' => 4,
        ]);

        $subject = Subject::create([
            'school_id' => $school->id,
            'name' => 'History',
            'code' => 'HIST',
            'curriculum' => '8-4-4',
            'pathway' => 'general',
            'lessons_per_week' => 4,
        ]);

        \App\Models\TeacherSubjectAllocation::create([
            'school_id' => $school->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'school_class_id' => $class->id,
            'stream_id' => null,
            'lessons_per_week' => 2,
        ]);

        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_current' => true,
        ]);

        $term = AcademicTerm::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'start_date' => '2026-01-05',
            'end_date' => '2026-03-30',
            'is_current' => true,
        ]);

        $timetable = Timetable::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'name' => '2026 Term 1',
            'type' => 'class',
            'status' => 'draft',
        ]);

        $service = app(TimetableEngineService::class);
        $result = $service->autoGenerateAllocations($timetable);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['allocated_slots']);
        $this->assertDatabaseHas('timetable_allocations', [
            'school_id' => $school->id,
            'timetable_id' => $timetable->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'stream_id' => null,
        ]);
    }
}
