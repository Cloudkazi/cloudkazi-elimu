<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\SubjectRule;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\Timetable;
use App\Models\TimetableGenerationSetting;
use App\Services\TimetableEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class TimetableController extends Controller
{
    protected TimetableEngineService $timetableEngine;

    public function __construct(TimetableEngineService $timetableEngine)
    {
        $this->timetableEngine = $timetableEngine;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $currentSchoolId = $user?->school_id ?? session('active_school_id');

        $timetables = Timetable::with(['academicYear', 'term'])
            ->when($currentSchoolId, fn ($query) => $query->where('school_id', $currentSchoolId))
            ->orderByDesc('id')
            ->get();

        $activeTimetable = ($currentSchoolId ? Timetable::getActivePublished($currentSchoolId) : null)
            ?? $timetables->where('status', 'published')->first()
            ?? $timetables->first();

        // ── STUDENT ROLE: Strictly locked to student's enrolled stream ───────────
        if ($user?->isStudent()) {
            $student = $user->student
                ?? Student::where('user_id', $user->id)->first()
                ?? Student::where('admission_number', explode('@', $user->email)[0])->first();

            if (!$student) {
                return redirect()->route('student.dashboard')->with('info', 'No student record associated with this account.');
            }

            $selectedClass = $student->schoolClass;
            $selectedStream = $student->stream;
            $selectedClassId = $student->school_class_id;
            $selectedStreamId = $student->stream_id;
            $classes = $selectedClass ? collect([$selectedClass]) : collect();
            $streams = $selectedStream ? collect([$selectedStream]) : collect();
            $teachers = collect();
            $rooms = collect();
            $teacherAvailabilities = collect();
            $subjectRules = collect();
            $generationSettings = new \stdClass();
            $health = [];
            $viewType = 'stream';
            $selectedTeacher = null;
            $schoolMatrix = [];
            $teacherMatrix = [];
            $allocationSummary = [];
            $streamMatrix = [];

            if ($activeTimetable && $selectedStreamId) {
                $streamMatrix = $this->timetableEngine->getStreamTimetableMatrix($activeTimetable->id, (int) $selectedStreamId);
            }

            [$days, $periods] = $this->getPeriodsAndDays($activeTimetable);

            return view('timetable.index', compact(
                'timetables',
                'activeTimetable',
                'days',
                'periods',
                'classes',
                'streams',
                'teachers',
                'rooms',
                'teacherAvailabilities',
                'subjectRules',
                'generationSettings',
                'health',
                'selectedClass',
                'selectedStream',
                'selectedTeacher',
                'viewType',
                'streamMatrix',
                'teacherMatrix',
                'schoolMatrix',
                'allocationSummary'
            ));
        }

        // ── PARENT ROLE: Strictly locked to linked children's streams ───────────
        if ($user?->isParent()) {
            $linkedStudents = $user->getLinkedStudents();
            if ($linkedStudents->isEmpty()) {
                return redirect()->route('parent.dashboard')->with('info', 'No linked students found.');
            }

            $childStreamIds = $linkedStudents->pluck('stream_id')->filter()->unique()->values();
            $childClassIds = $linkedStudents->pluck('school_class_id')->filter()->unique()->values();

            $reqStreamId = $request->input('stream_id');
            if ($reqStreamId && in_array((int) $reqStreamId, $childStreamIds->toArray())) {
                $selectedStreamId = (int) $reqStreamId;
            } else {
                $selectedStreamId = $childStreamIds->first();
            }

            $selectedStream = $selectedStreamId ? Stream::with('schoolClass')->find($selectedStreamId) : null;
            $selectedClass = $selectedStream?->schoolClass ?? ($childClassIds->first() ? SchoolClass::find($childClassIds->first()) : null);
            $selectedClassId = $selectedClass?->id;

            $classes = SchoolClass::whereIn('id', $childClassIds)->with('streams')->get();
            $streams = Stream::whereIn('id', $childStreamIds)->get();
            $teachers = collect();
            $rooms = collect();
            $teacherAvailabilities = collect();
            $subjectRules = collect();
            $generationSettings = new \stdClass();
            $health = [];
            $viewType = 'stream';
            $selectedTeacher = null;
            $schoolMatrix = [];
            $teacherMatrix = [];
            $allocationSummary = [];
            $streamMatrix = [];

            if ($activeTimetable && $selectedStreamId) {
                $streamMatrix = $this->timetableEngine->getStreamTimetableMatrix($activeTimetable->id, (int) $selectedStreamId);
            }

            [$days, $periods] = $this->getPeriodsAndDays($activeTimetable);

            return view('timetable.index', compact(
                'timetables',
                'activeTimetable',
                'days',
                'periods',
                'classes',
                'streams',
                'teachers',
                'rooms',
                'teacherAvailabilities',
                'subjectRules',
                'generationSettings',
                'health',
                'selectedClass',
                'selectedStream',
                'selectedTeacher',
                'viewType',
                'streamMatrix',
                'teacherMatrix',
                'schoolMatrix',
                'allocationSummary'
            ));
        }

        // ── STAFF / ADMIN ROLE: Full timetable views ──────────────────────────
        $classes = SchoolClass::when($currentSchoolId, fn ($q) => $q->where('school_id', $currentSchoolId))->with('streams')->get();
        $streams = Stream::when($currentSchoolId, fn ($q) => $q->where('school_id', $currentSchoolId))->with('schoolClass')->get();
        $teachers = Teacher::when($currentSchoolId, fn ($q) => $q->where('school_id', $currentSchoolId))->with('user')->get();

        $rooms = Schema::hasTable('rooms') ? Room::when($currentSchoolId, fn ($q) => $q->where('school_id', $currentSchoolId))->with('roomType')->get() : collect();
        $teacherAvailabilities = Schema::hasTable('teacher_availabilities') ? TeacherAvailability::when($currentSchoolId, fn ($q) => $q->where('school_id', $currentSchoolId))->with('teacher.user')->get() : collect();
        $subjectRules = Schema::hasTable('subject_rules') ? SubjectRule::when($currentSchoolId, fn ($q) => $q->where('school_id', $currentSchoolId))->with('subject')->get() : collect();
        $generationSettings = ($activeTimetable && Schema::hasTable('timetable_generation_settings')) ? $this->timetableEngine->getGenerationSettings($activeTimetable) : new \stdClass();
        $health = ($activeTimetable && Schema::hasTable('timetable_allocations')) ? $this->timetableEngine->getTimetableHealth($activeTimetable) : [
            'teacher_conflicts' => 0,
            'stream_conflicts' => 0,
            'room_conflicts' => 0,
            'unscheduled_lessons' => 0,
            'teacher_overload' => 0,
            'room_capacity_warnings' => 0,
            'score' => 100,
            'status_color' => 'green',
        ];

        $selectedClassId = $request->input('class_id');
        $selectedStreamId = $request->input('stream_id');
        $selectedTeacherId = $request->input('teacher_id');
        $viewType = $request->input('view', 'school');

        if ($viewType === 'stream' && !$selectedStreamId && $streams->isNotEmpty()) {
            $selectedStreamId = $streams->first()->id;
        }

        if ($viewType === 'teacher' && !$selectedTeacherId && $teachers->isNotEmpty()) {
            $selectedTeacherId = $teachers->first()->id;
        }

        $streamMatrix = [];
        $teacherMatrix = [];
        $schoolMatrix = [];
        $allocationSummary = $this->timetableEngine->getSchoolTeachingAllocationSummary($currentSchoolId ?? 0);

        if ($activeTimetable) {
            $schoolMatrix = $this->timetableEngine->getSchoolTimetableMatrix(
                $activeTimetable->id,
                $selectedClassId ? (int) $selectedClassId : null,
                $selectedTeacherId ? (int) $selectedTeacherId : null,
                $request->input('day')
            );

            if ($selectedStreamId) {
                $streamMatrix = $this->timetableEngine->getStreamTimetableMatrix($activeTimetable->id, (int) $selectedStreamId);
            }
            if ($selectedTeacherId) {
                $teacherMatrix = $this->timetableEngine->getTeacherTimetableMatrix($activeTimetable->id, (int) $selectedTeacherId);
            }

            $allocationSummary = $this->timetableEngine->getSchoolTeachingAllocationSummary($activeTimetable->school_id);
        }

        $selectedClass = $selectedClassId ? SchoolClass::find($selectedClassId) : null;
        $selectedStream = $selectedStreamId ? Stream::find($selectedStreamId) : null;
        $selectedTeacher = $selectedTeacherId ? Teacher::find($selectedTeacherId) : null;

        [$days, $periods] = $this->getPeriodsAndDays($activeTimetable);

        return view('timetable.index', compact(
            'timetables',
            'activeTimetable',
            'days',
            'periods',
            'classes',
            'streams',
            'teachers',
            'rooms',
            'teacherAvailabilities',
            'subjectRules',
            'generationSettings',
            'health',
            'selectedClass',
            'selectedStream',
            'selectedTeacher',
            'viewType',
            'streamMatrix',
            'teacherMatrix',
            'schoolMatrix',
            'allocationSummary'
        ));
    }

    public function saveSettings(Request $request)
    {
        $timetableId = $request->input('timetable_id');
        $timetable = Timetable::findOrFail($timetableId);

        $existing = TimetableGenerationSetting::where('school_id', $timetable->school_id)
            ->where('timetable_id', $timetable->id)
            ->first();

        $data = [];

        // Daily limit settings with sensible defaults
        $maxTeacher = $request->has('max_lessons_per_teacher_per_day')
            ? (int) $request->input('max_lessons_per_teacher_per_day')
            : ($existing?->max_lessons_per_teacher_per_day ?? 6);
        $data['max_lessons_per_teacher_per_day'] = $maxTeacher > 0 ? $maxTeacher : 6;

        $maxConsec = $request->has('max_consecutive_lessons_per_teacher')
            ? (int) $request->input('max_consecutive_lessons_per_teacher')
            : ($existing?->max_consecutive_lessons_per_teacher ?? 2);
        $data['max_consecutive_lessons_per_teacher'] = $maxConsec > 0 ? $maxConsec : 2;

        $maxStream = $request->has('max_lessons_per_stream_per_day')
            ? (int) $request->input('max_lessons_per_stream_per_day')
            : ($existing?->max_lessons_per_stream_per_day ?? 6);
        $data['max_lessons_per_stream_per_day'] = $maxStream > 0 ? $maxStream : 6;

        // Flags
        $data['allow_double_lessons'] = $request->has('allow_double_lessons')
            ? $request->boolean('allow_double_lessons')
            : ($existing?->allow_double_lessons ?? true);

        $data['practical_subjects_consecutive'] = $request->has('practical_subjects_consecutive')
            ? $request->boolean('practical_subjects_consecutive')
            : ($existing?->practical_subjects_consecutive ?? false);

        $data['core_subjects_distributed'] = $request->has('core_subjects_distributed')
            ? $request->boolean('core_subjects_distributed')
            : ($existing?->core_subjects_distributed ?? true);

        $data['enforce_teacher_availability'] = $request->has('enforce_teacher_availability')
            ? $request->boolean('enforce_teacher_availability')
            : ($existing?->enforce_teacher_availability ?? true);

        $data['enforce_room_availability'] = $request->has('enforce_room_availability')
            ? $request->boolean('enforce_room_availability')
            : ($existing?->enforce_room_availability ?? true);

        $data['conflict_free_generation'] = $request->has('conflict_free_generation')
            ? $request->boolean('conflict_free_generation')
            : ($existing?->conflict_free_generation ?? true);

        $data['strict_validation'] = $request->has('strict_validation')
            ? $request->boolean('strict_validation')
            : ($existing?->strict_validation ?? true);

        $data['allow_incomplete_timetable'] = $request->has('allow_incomplete_timetable')
            ? $request->boolean('allow_incomplete_timetable')
            : ($existing?->allow_incomplete_timetable ?? false);

        $data['generate_draft_only'] = $request->has('generate_draft_only')
            ? $request->boolean('generate_draft_only')
            : ($existing?->generate_draft_only ?? true);

        $data['preferred_scheduling_strategy'] = $request->input(
            'preferred_scheduling_strategy',
            $existing?->preferred_scheduling_strategy ?? 'Balanced'
        );

        $settings = TimetableGenerationSetting::updateOrCreate(
            ['school_id' => $timetable->school_id, 'timetable_id' => $timetable->id],
            $data
        );

        return back()->with('success', 'Timetable generation settings saved successfully.');
    }

    protected function ensureCurrentAcademicCycle(int $schoolId): array
    {
        $currentYear = AcademicYear::where('school_id', $schoolId)->where('is_current', true)->first();
        if (!$currentYear) {
            $currentYear = AcademicYear::create([
                'school_id' => $schoolId,
                'name' => date('Y'),
                'start_date' => date('Y-01-01'),
                'end_date' => date('Y-12-31'),
                'is_current' => true,
            ]);
            AcademicYear::where('school_id', $schoolId)->whereKeyNot($currentYear->id)->update(['is_current' => false]);
        }

        $currentTerm = AcademicTerm::where('school_id', $schoolId)->where('academic_year_id', $currentYear->id)->where('is_current', true)->first();
        if (!$currentTerm) {
            $termName = 'Term 1';
            $currentTerm = AcademicTerm::firstOrCreate([
                'school_id' => $schoolId,
                'academic_year_id' => $currentYear->id,
                'name' => $termName,
            ], [
                'start_date' => $currentYear->start_date,
                'end_date' => $currentYear->end_date,
                'is_current' => true,
            ]);
            AcademicTerm::where('school_id', $schoolId)
                ->where('academic_year_id', $currentYear->id)
                ->whereKeyNot($currentTerm->id)
                ->update(['is_current' => false]);
        }

        return [$currentYear, $currentTerm];
    }

    public function generate(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? session('active_school_id');

        if ($request->isMethod('GET')) {
            $currentYear = $schoolId ? AcademicYear::where('school_id', $schoolId)->where('is_current', true)->first() : null;
            $currentTerm = $schoolId ? AcademicTerm::where('school_id', $schoolId)->where('is_current', true)->first() : null;
            if ($schoolId && (!$currentYear || !$currentTerm)) {
                [$currentYear, $currentTerm] = $this->ensureCurrentAcademicCycle($schoolId);
            }

            $activeTimetable = $schoolId ? Timetable::where('school_id', $schoolId)->latest()->first() : null;
            $streams = $schoolId ? Stream::where('school_id', $schoolId)->with('schoolClass')->get() : collect();
            $teachers = $schoolId ? Teacher::where('school_id', $schoolId)->with('user')->get() : collect();
            $subjects = $schoolId ? \App\Models\Subject::where('school_id', $schoolId)->get() : collect();
            $classrooms = $schoolId ? Classroom::where('school_id', $schoolId)->get() : collect();
            $rooms = Schema::hasTable('rooms') && $schoolId ? Room::where('school_id', $schoolId)->with('roomType')->get() : collect();
            $generationSettings = ($activeTimetable && Schema::hasTable('timetable_generation_settings'))
                ? $this->timetableEngine->getGenerationSettings($activeTimetable)
                : new \stdClass();

            return view('timetable.generate', compact(
                'currentYear',
                'currentTerm',
                'activeTimetable',
                'streams',
                'teachers',
                'subjects',
                'classrooms',
                'rooms',
                'generationSettings'
            ));
        }

        if (!$schoolId) {
            return redirect()->route('login');
        }

        [$currentYear, $currentTerm] = $this->ensureCurrentAcademicCycle($schoolId);

        $timetable = Timetable::firstOrCreate([
            'school_id' => $schoolId,
            'academic_year_id' => $currentYear->id,
            'term_id' => $currentTerm->id,
            'name' => $currentYear->name . ' ' . $currentTerm->name . ' Auto-Generated Schedule',
        ], [
            'type' => 'class',
            'status' => 'draft',
            'description' => 'Generated automatically by CloudKazi Timetable Engine',
        ]);

        $request->merge(['timetable_id' => $timetable->id]);
        if ($request->has('preferred_scheduling_strategy') || $request->has('max_lessons_per_teacher_per_day') || $request->has('school_days')) {
            $this->saveSettings($request);
        }

        $readiness = $this->timetableEngine->getReadinessReport($timetable);

        if (($readiness['teacher_subject_allocations'] ?? 0) <= 0) {
            return redirect()->route('timetable.index')
                ->withErrors(['timetable' => 'No teacher-subject allocations were found for the current school.'])
                ->with('warning', 'No teacher-subject allocations were found for the current school.');
        }

        if (($readiness['strict_validation'] ?? true) && !empty($readiness['issues'])) {
            return redirect()->route('timetable.index')
                ->withErrors(['timetable' => 'Unassigned teaching requirements were found. Fix the teacher subject assignments before generation.'])
                ->with('warning', 'Timetable generation blocked by unassigned teaching requirements.');
        }

        if (($readiness['required_lessons'] ?? 0) <= 0) {
            return redirect()->route('timetable.index')
                ->withErrors(['timetable' => 'No valid teacher-subject allocations were found for this school.'])
                ->with('warning', 'No teacher-subject allocations were found for the current school.');
        }

        $result = $this->timetableEngine->autoGenerateAllocations($timetable);

        if (!$result['success']) {
            return redirect()->route('timetable.index')->withErrors(['timetable' => $result['message']]);
        }

        return redirect()->route('timetable.index')
            ->with('success', "Draft timetable generated successfully! {$result['allocated_slots']} teaching periods scheduled with {$result['detected_conflicts']} detected conflicts.");
    }

    public function repair(Request $request)
    {
        $timetableId = $request->input('timetable_id');
        $timetable = Timetable::findOrFail($timetableId);
        $result = $this->timetableEngine->repairTimetable($timetable);

        return back()->with('success', 'Timetable repair completed. ' . $result['allocated_slots'] . ' lessons retained and conflicts reviewed.');
    }

    public function print(Request $request)
    {
        $timetable = Timetable::findOrFail($request->input('timetable_id'));
        $streamId  = $request->input('stream_id');
        $stream    = Stream::with('schoolClass')->findOrFail($streamId);

        $user = Auth::user();

        // Students may only print their own class/stream timetable
        if ($user && $user->isStudent()) {
            $myStudent = $user->student;
            if (!$myStudent || (int)$myStudent->stream_id !== (int)$streamId) {
                abort(403, 'You may only view your own class timetable.');
            }
        }

        // Parents may only print their linked children's stream timetable
        if ($user && $user->isParent()) {
            $childStreamIds = $user->getLinkedStudents()->pluck('stream_id')->filter()->map(fn ($id) => (int) $id);
            if (!$childStreamIds->contains((int) $streamId)) {
                abort(403, 'You may only view your child\'s class timetable.');
            }
        }

        $streamMatrix = $this->timetableEngine->getStreamTimetableMatrix($timetable->id, $stream->id);

        return view('timetable.print', compact('timetable', 'stream', 'streamMatrix'));
    }

    public function publish(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        $timetable->update(['status' => 'published']);
        return back()->with('success', "Timetable \"{$timetable->name}\" is now published and visible to students and parents.");
    }

    public function archive(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        $timetable->update(['status' => 'archived']);
        return back()->with('success', "Timetable \"{$timetable->name}\" has been archived.");
    }

    public function destroy(Timetable $timetable)
    {
        $this->authorizeSchoolAccess($timetable);
        $name = $timetable->name;
        $timetable->delete();
        return redirect()->route('timetable.index')->with('success', "Timetable \"{$name}\" and all its slots and allocations have been deleted.");
    }

    public function readiness(Request $request)
    {
        $schoolId = auth()->user()->school_id ?? session('active_school_id');
        if (!$schoolId) {
            return response()->json(['error' => 'No school context.'], 403);
        }

        $timetable = Timetable::where('school_id', $schoolId)->latest()->first();
        if (!$timetable) {
            // Build a dummy timetable model for the readiness check without saving
            $timetable = new Timetable(['school_id' => $schoolId, 'id' => 0]);
        }

        $report = $this->timetableEngine->getReadinessReport($timetable);
        return response()->json($report);
    }

    protected function authorizeSchoolAccess(Timetable $timetable): void
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');
        if ($schoolId && $timetable->school_id !== $schoolId) {
            abort(403, 'Access denied.');
        }
    }

    protected function getPeriodsAndDays(?Timetable $activeTimetable): array
    {
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $timetableSlots = $activeTimetable ? $activeTimetable->slots->where('day_of_week', 'Monday')->sortBy('period_number') : collect();
        if ($timetableSlots->isEmpty() && $activeTimetable) {
            $timetableSlots = $activeTimetable->slots->groupBy('period_number')->map->first()->sortBy('period_number');
        }

        $periods = [];
        if ($timetableSlots->isNotEmpty()) {
            foreach ($timetableSlots as $slot) {
                $timeLabel = \Carbon\Carbon::parse($slot->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($slot->end_time)->format('H:i');
                $periods[$slot->period_number] = [
                    'time' => $timeLabel,
                    'name' => $slot->is_break ? ($slot->break_label ?: 'Break') : 'Lesson ' . $slot->period_number,
                    'break' => (bool) $slot->is_break,
                ];
            }
        } else {
            $periods = [
                1 => ['time' => '08:00 - 08:45', 'name' => 'Lesson 1', 'break' => false],
                2 => ['time' => '08:45 - 09:30', 'name' => 'Lesson 2', 'break' => false],
                3 => ['time' => '09:30 - 10:15', 'name' => 'Lesson 3', 'break' => false],
                4 => ['time' => '10:15 - 10:45', 'name' => 'Tea Break', 'break' => true],
                5 => ['time' => '10:45 - 11:30', 'name' => 'Lesson 4', 'break' => false],
                6 => ['time' => '11:30 - 12:15', 'name' => 'Lesson 5', 'break' => false],
                7 => ['time' => '12:15 - 13:00', 'name' => 'Lesson 6', 'break' => false],
                8 => ['time' => '13:00 - 14:00', 'name' => 'Lunch Break', 'break' => true],
                9 => ['time' => '14:00 - 14:45', 'name' => 'Lesson 7', 'break' => false],
                10 => ['time' => '14:45 - 15:30', 'name' => 'Lesson 8', 'break' => false],
                11 => ['time' => '15:30 - 16:30', 'name' => 'Games & Clubs', 'break' => true],
            ];
        }

        return [$days, $periods];
    }
}

