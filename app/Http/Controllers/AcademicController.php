<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\SubjectOffering;
use App\Models\Teacher;
use App\Models\TeacherSubjectAllocation;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    public function index()
    {
        $academicYears = AcademicYear::with('terms')->latest()->get();
        $classes = SchoolClass::with(['streams.classTeacher', 'students'])->orderBy('numeric_level')->get();
        $subjects = Subject::orderBy('curriculum')->orderBy('name')->get();
        $allocations = TeacherSubjectAllocation::with(['teacher.user', 'subject', 'schoolClass', 'stream'])->get();
        $teachers = Teacher::with('user')->get();

        return view('academics.index', compact(
            'academicYears',
            'classes',
            'subjects',
            'allocations',
            'teachers'
        ));
    }

    public function storeClass(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'level' => 'required|in:junior_school,senior_school,secondary_844',
            'curriculum' => 'required|in:CBC,8-4-4',
            'numeric_level' => 'required|integer',
        ]);

        SchoolClass::create($validated);
        return back()->with('success', "Class {$validated['name']} added successfully.");
    }

    public function updateClass(Request $request, int $id)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');
        $class = SchoolClass::where('id', $id)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:50',
            'level' => 'required|in:junior_school,senior_school,secondary_844',
            'curriculum' => 'required|in:CBC,8-4-4',
            'numeric_level' => 'required|integer',
        ]);

        $class->update($validated);
        return back()->with('success', "Class updated successfully.");
    }

    public function destroyClass(int $id)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');
        $class = SchoolClass::where('id', $id)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->firstOrFail();

        $hasStudents = $class->students()->exists();
        $hasStreams = $class->streams()->exists();
        $hasOfferings = \App\Models\SubjectOffering::where('school_class_id', $class->id)->exists();
        $hasAllocations = \App\Models\TeacherSubjectAllocation::where('school_class_id', $class->id)->exists();

        if ($hasStudents || $hasStreams || $hasOfferings || $hasAllocations) {
            $class->update(['is_active' => false]);
            return back()->with('success', "This class cannot be permanently deleted because it has existing academic records. It has been deactivated instead.");
        }

        $class->delete();
        return back()->with('success', 'Class removed successfully.');
    }

    public function storeStream(Request $request)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');

        $validated = $request->validate([
            'school_class_id'  => 'required|exists:school_classes,id',
            'name'             => 'required|string|max:50',
            'pathway'          => 'nullable|in:general,STEM,Social Sciences,Arts & Sports',
            'capacity'         => 'required|integer|min:1',
            'class_teacher_id' => 'nullable|exists:users,id',
        ]);

        if ($schoolId) {
            $classMatches = SchoolClass::where('id', $validated['school_class_id'])
                ->where('school_id', $schoolId)
                ->exists();
            if (!$classMatches) {
                return back()->withErrors(['school_class_id' => 'Selected class does not belong to your school.'])->withInput();
            }
            $validated['school_id'] = $schoolId;
        }

        $validated['pathway'] = $validated['pathway'] ?? 'general';

        Stream::create($validated);
        return back()->with('success', "Stream {$validated['name']} ({$validated['pathway']}) created successfully.");
    }


    public function storeSubject(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'curriculum' => 'required|in:CBC,8-4-4,Both',
            'pathway' => 'required|in:general,STEM,Social Sciences,Arts & Sports',
            'lessons_per_week' => 'required|integer|min:1|max:10',
        ]);

        Subject::create($validated);
        return back()->with('success', "Subject {$validated['name']} ({$validated['code']}) configured.");
    }

    public function updateSubject(Request $request, int $id)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');
        $subject = Subject::where('id', $id)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20',
            'curriculum' => 'required|in:CBC,8-4-4,Both',
            'pathway' => 'required|in:general,STEM,Social Sciences,Arts & Sports',
            'lessons_per_week' => 'required|integer|min:1|max:10',
        ]);

        $subject->update($validated);
        return back()->with('success', "Subject updated successfully.");
    }

    public function destroySubject(int $id)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');
        $subject = Subject::where('id', $id)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->firstOrFail();

        $hasOfferings = \App\Models\SubjectOffering::where('subject_id', $subject->id)->exists();
        $hasAllocations = \App\Models\TeacherSubjectAllocation::where('subject_id', $subject->id)->exists();
        $hasMarks = \App\Models\ExamMark844::where('subject_id', $subject->id)->exists();

        if ($hasOfferings || $hasAllocations || $hasMarks) {
            $subject->update(['is_active' => false]);
            return back()->with('success', "This subject cannot be permanently deleted because it is already being used by existing academic records. It has been deactivated instead.");
        }

        $subject->delete();
        return back()->with('success', 'Subject removed successfully.');
    }

    public function storeAllocation(Request $request)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');

        $validated = $request->validate([
            'teacher_id'       => 'required|exists:teachers,id',
            'subject_id'       => 'required|exists:subjects,id',
            'school_class_id'  => 'required|exists:school_classes,id',
            'stream_id'        => 'nullable|exists:streams,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id'          => 'nullable|exists:academic_terms,id',
            'lessons_per_week' => 'required|integer|min:1|max:10',
        ]);

        if ($schoolId) {
            $classMatches = SchoolClass::where('id', $validated['school_class_id'])
                ->where('school_id', $schoolId)
                ->exists();
            if (!$classMatches) {
                return back()->withErrors(['school_class_id' => 'Selected class does not belong to your school.'])->withInput();
            }

            $teacherMatches = Teacher::where('id', $validated['teacher_id'])
                ->where('school_id', $schoolId)
                ->exists();
            if (!$teacherMatches) {
                return back()->withErrors(['teacher_id' => 'Selected teacher does not belong to your school.'])->withInput();
            }

            if (!empty($validated['academic_year_id'])) {
                $yearBelongs = \App\Models\AcademicYear::where('id', $validated['academic_year_id'])
                    ->where('school_id', $schoolId)->exists();
                if (!$yearBelongs) {
                    return back()->withErrors(['academic_year_id' => 'Selected academic year does not belong to your school.'])->withInput();
                }
            }

            if (!empty($validated['term_id'])) {
                $termBelongs = \App\Models\AcademicTerm::where('id', $validated['term_id'])
                    ->where('school_id', $schoolId)->exists();
                if (!$termBelongs) {
                    return back()->withErrors(['term_id' => 'Selected term does not belong to your school.'])->withInput();
                }
            }
        }

        // If a specific stream was selected, ensure it belongs to the selected class
        if (!empty($validated['stream_id'])) {
            $streamBelongs = Stream::where('id', $validated['stream_id'])
                ->where('school_class_id', $validated['school_class_id'])
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->exists();

            if (!$streamBelongs) {
                return back()->withErrors(['stream_id' => 'The selected stream does not belong to the selected class.'])->withInput();
            }
        }

        // ── 1. Enforce that subject is already offered to this class/stream ──
        $offeringQuery = SubjectOffering::where('school_id', $schoolId ?? 0)
            ->where('school_class_id', $validated['school_class_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('is_active', true);

        if (!empty($validated['stream_id'])) {
            $offeringQuery->where(function ($q) use ($validated) {
                $q->where('stream_id', $validated['stream_id'])->orWhereNull('stream_id');
            });
        }

        $offering = $offeringQuery->first();

        $targetClass = SchoolClass::find($validated['school_class_id']);
        $targetStream = !empty($validated['stream_id']) ? Stream::find($validated['stream_id']) : null;
        $targetLabel = ($targetClass?->name ?? 'Class') . ($targetStream ? ' ' . $targetStream->name : '');
        $subjectObj = Subject::find($validated['subject_id']);

        if (!$offering) {
            return back()
                ->withErrors(['subject_id' => "{$subjectObj->name} has not been assigned to {$targetLabel}. Assign the subject to this class/stream before allocating a teacher."])
                ->withInput();
        }

        // ── 2. Duplicate teacher allocation detection ────────────────────────
        // Same school + academic year + term + class + stream + subject + teacher
        $existsQuery = TeacherSubjectAllocation::where('school_id', $schoolId ?? 0)
            ->where('teacher_id',      $validated['teacher_id'])
            ->where('subject_id',      $validated['subject_id'])
            ->where('school_class_id', $validated['school_class_id'])
            ->where('is_active',       true);

        if (empty($validated['stream_id'])) {
            $existsQuery->whereNull('stream_id');
        } else {
            $existsQuery->where('stream_id', $validated['stream_id']);
        }

        if (empty($validated['academic_year_id'])) {
            $existsQuery->whereNull('academic_year_id');
        } else {
            $existsQuery->where('academic_year_id', $validated['academic_year_id']);
        }

        if (empty($validated['term_id'])) {
            $existsQuery->whereNull('term_id');
        } else {
            $existsQuery->where('term_id', $validated['term_id']);
        }

        $existingAllocation = $existsQuery->with(['teacher.user'])->first();

        if ($existingAllocation) {
            $teacherName = $existingAllocation->teacher?->user?->name ?? 'Teacher';
            return back()
                ->withErrors(['duplicate' => "{$teacherName} is already allocated to {$subjectObj->name} for {$targetLabel}."])
                ->withInput();
        }
        // ── End duplicate check ──────────────────────────────────────────────

        $allocationData = $validated;
        if ($schoolId && empty($allocationData['school_id'])) {
            $allocationData['school_id'] = $schoolId;
        }
        $allocationData['subject_offering_id'] = $offering->id;
        $allocationData['is_active'] = true;

        TeacherSubjectAllocation::create($allocationData);
        return back()->with('success', 'Teacher subject allocation saved successfully.');
    }


    public function destroyAllocation(TeacherSubjectAllocation $allocation)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');
        if ($schoolId && $allocation->school_id && $allocation->school_id !== $schoolId) {
            abort(403, 'You cannot delete allocations for another school.');
        }
        $allocation->delete();
        return back()->with('success', 'Allocation deleted successfully.');
    }

    public function classes()
    {
        $classes = SchoolClass::with(['streams.classTeacher', 'students'])->orderBy('numeric_level')->get();
        $teachers = Teacher::with('user')->get();
        return view('academics.classes', compact('classes', 'teachers'));
    }

    public function subjects()
    {
        $subjects = Subject::orderBy('curriculum')->orderBy('name')->get();
        return view('academics.subjects', compact('subjects'));
    }

    public function curriculum()
    {
        $cbcSubjects = Subject::where('curriculum', 'CBC')->orWhere('curriculum', 'Both')->get();
        $eightFourFourSubjects = Subject::where('curriculum', '8-4-4')->orWhere('curriculum', 'Both')->get();
        return view('academics.curriculum', compact('cbcSubjects', 'eightFourFourSubjects'));
    }

    public function teachers()
    {
        $teachers = Teacher::with(['user', 'subjectAllocations.subject', 'subjectAllocations.schoolClass'])->get();
        $classes = SchoolClass::with('streams')->get();
        $subjects = Subject::all();
        return view('academics.teachers', compact('teachers', 'classes', 'subjects'));
    }

    public function teacherSubjects()
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');

        // Only show ACTIVE allocations in the main roster
        $allocations = TeacherSubjectAllocation::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['teacher.user', 'subject', 'schoolClass', 'stream', 'academicYear', 'term'])
            ->orderBy('school_class_id')
            ->get();

        // Count inactive (historical) records for informational display
        $inactiveCount = TeacherSubjectAllocation::where('is_active', false)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->count();

        $teachers = Teacher::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('user')
            ->get();
        $classes = SchoolClass::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('streams')
            ->orderBy('numeric_level')
            ->get();
        // Subjects scoped strictly to this school — no orWhereNull leak
        $subjects = Subject::when(
                $schoolId,
                fn ($q) => $q->where('school_id', $schoolId),
                fn ($q) => $q  // super-admin without selected school: no filter
            )
            ->orderBy('name')
            ->get();

        // Academic years / terms for the new allocation form
        $academicYears = \App\Models\AcademicYear::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('terms')
            ->orderByDesc('start_date')
            ->get();

        // Active offerings for class-to-subject dynamic binding
        $offerings = SubjectOffering::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('subject')
            ->get();

        return view('academics.teacher-subjects', compact(
            'allocations', 'inactiveCount', 'teachers', 'classes', 'subjects', 'academicYears', 'offerings'
        ));
    }

    public function subjectOfferings(Request $request)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');

        $classes = SchoolClass::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('streams')
            ->orderBy('numeric_level')
            ->get();

        $subjects = Subject::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('name')
            ->get();

        $academicYears = AcademicYear::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('terms')
            ->orderByDesc('start_date')
            ->get();

        $selectedClassId = $request->input('class_id');
        $selectedStreamId = $request->input('stream_id');
        $selectedYearId = $request->input('academic_year_id');
        $selectedTermId = $request->input('term_id');

        $offeringsQuery = SubjectOffering::where('is_active', true)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with(['schoolClass', 'stream', 'subject', 'academicYear', 'term', 'allocations.teacher.user'])
            ->when($selectedClassId, fn ($q) => $q->where('school_class_id', $selectedClassId))
            ->when($selectedStreamId, fn ($q) => $q->where('stream_id', $selectedStreamId))
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->when($selectedTermId, fn ($q) => $q->where('term_id', $selectedTermId))
            ->orderBy('school_class_id')
            ->orderBy('stream_id');

        $offerings = $offeringsQuery->get();

        // Calculate stats
        $totalOfferings = $offerings->count();
        $allocatedCount = $offerings->filter(fn ($o) => $o->allocations->where('is_active', true)->isNotEmpty())->count();
        $unallocatedCount = $totalOfferings - $allocatedCount;
        $classesCovered = $offerings->pluck('school_class_id')->unique()->count();

        return view('academics.subject-offerings', compact(
            'offerings',
            'totalOfferings',
            'allocatedCount',
            'unallocatedCount',
            'classesCovered',
            'classes',
            'subjects',
            'academicYears',
            'selectedClassId',
            'selectedStreamId',
            'selectedYearId',
            'selectedTermId'
        ));
    }

    public function storeSubjectOffering(Request $request)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');

        $validated = $request->validate([
            'school_class_id'  => 'required|exists:school_classes,id',
            'stream_id'        => 'nullable|exists:streams,id',
            'subject_id'       => 'required|exists:subjects,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'term_id'          => 'nullable|exists:academic_terms,id',
            'pathway'          => 'nullable|in:general,STEM,Social Sciences,Arts & Sports',
        ]);

        if ($schoolId) {
            $classMatches = SchoolClass::where('id', $validated['school_class_id'])
                ->where('school_id', $schoolId)
                ->exists();
            if (!$classMatches) {
                return back()->withErrors(['school_class_id' => 'Selected class does not belong to your school.'])->withInput();
            }

            $subjectMatches = Subject::where('id', $validated['subject_id'])
                ->where('school_id', $schoolId)
                ->exists();
            if (!$subjectMatches) {
                return back()->withErrors(['subject_id' => 'Selected subject does not belong to your school.'])->withInput();
            }

            if (!empty($validated['stream_id'])) {
                $streamMatches = Stream::where('id', $validated['stream_id'])
                    ->where('school_class_id', $validated['school_class_id'])
                    ->where('school_id', $schoolId)
                    ->first();
                if (!$streamMatches) {
                    return back()->withErrors(['stream_id' => 'The selected stream does not belong to the selected class.'])->withInput();
                }
                // If stream has a pathway and request pathway is general/null, adopt stream's pathway
                if (empty($validated['pathway']) || $validated['pathway'] === 'general') {
                    $validated['pathway'] = $streamMatches->pathway ?? 'general';
                }
            }
        }

        $validated['pathway'] = $validated['pathway'] ?? 'general';
        $validated['school_id'] = $schoolId ?? 0;
        $validated['is_active'] = true;

        // Duplicate offering check
        $existsQuery = SubjectOffering::where('school_id', $schoolId ?? 0)
            ->where('school_class_id',  $validated['school_class_id'])
            ->where('subject_id',       $validated['subject_id'])
            ->where('is_active',        true);

        if (empty($validated['stream_id'])) {
            $existsQuery->whereNull('stream_id');
        } else {
            $existsQuery->where('stream_id', $validated['stream_id']);
        }

        if (empty($validated['academic_year_id'])) {
            $existsQuery->whereNull('academic_year_id');
        } else {
            $existsQuery->where('academic_year_id', $validated['academic_year_id']);
        }

        if (empty($validated['term_id'])) {
            $existsQuery->whereNull('term_id');
        } else {
            $existsQuery->where('term_id', $validated['term_id']);
        }

        if ($existsQuery->exists()) {
            $sub = Subject::find($validated['subject_id']);
            $cls = SchoolClass::find($validated['school_class_id']);
            $str = !empty($validated['stream_id']) ? Stream::find($validated['stream_id']) : null;
            $target = $cls?->name . ($str ? ' ' . $str->name : ' (All Streams)');
            return back()->withErrors(['duplicate' => "{$sub->name} is already offered to {$target}."])->withInput();
        }

        SubjectOffering::create($validated);

        return back()->with('success', 'Subject offering configured successfully. You can now allocate teachers to this offering.');
    }

    public function destroySubjectOffering(int $id)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');
        $offering = SubjectOffering::where('id', $id)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->firstOrFail();

        // Check if marks exist for this subject in this class
        $marksExist = \App\Models\ExamMark844::where('school_id', $offering->school_id)
            ->where('subject_id', $offering->subject_id)
            ->whereHas('student', fn ($q) => $q->where('school_class_id', $offering->school_class_id))
            ->exists();

        if ($marksExist) {
            // Soft-deactivate to preserve historical marks
            $offering->update(['is_active' => false]);
            TeacherSubjectAllocation::where('subject_offering_id', $offering->id)->update(['is_active' => false]);
            return back()->with('success', 'Subject offering marked inactive. Existing exam marks are preserved.');
        }

        TeacherSubjectAllocation::where('subject_offering_id', $offering->id)->delete();
        $offering->delete();

        return back()->with('success', 'Subject offering removed successfully.');
    }

    public function updateStream(Request $request, int $id)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');
        $stream = Stream::where('id', $id)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->firstOrFail();

        $validated = $request->validate([
            'name'             => 'required|string|max:50',
            'pathway'          => 'required|in:general,STEM,Social Sciences,Arts & Sports',
            'capacity'         => 'required|integer|min:1',
            'class_teacher_id' => 'nullable|exists:users,id',
        ]);

        $stream->update($validated);

        return back()->with('success', "Stream {$stream->name} updated with pathway: {$stream->pathway}.");
    }


    public function settings()
    {
        $school = auth()->user()?->school ?? (session('active_school_id') ? \App\Models\School::find(session('active_school_id')) : null);
        if (!$school) {
            abort(403, 'No school assigned to your account.');
        }

        $settings = \App\Models\SchoolSetting::where('school_id', $school->id)->first();
        $academicYears = AcademicYear::withoutGlobalScopes()
            ->with(['terms' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('school_id', $school->id)
            ->latest('start_date')
            ->get();

        return view('academics.settings', compact('school', 'settings', 'academicYears'));
    }

    public function storeAcademicYear(Request $request)
    {
        $school = auth()->user()?->school ?? (session('active_school_id') ? \App\Models\School::find(session('active_school_id')) : null);
        if (!$school) {
            abort(403, 'No school assigned to your account.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:20',
                'regex:/^([0-9]{4})(\/[0-9]{4})?$/',
            ],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'name.regex' => 'Academic year format must be YYYY or YYYY/YYYY (e.g. 2026 or 2026/2027).',
            'end_date.after_or_equal' => 'The end date must not be before the start date.',
        ]);

        $schoolId = (int) $school->id;

        $year = AcademicYear::withoutGlobalScopes()->updateOrCreate(
            [
                'school_id' => $schoolId,
                'name' => trim($validated['name']),
            ],
            [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_current' => true,
            ]
        );

        AcademicYear::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereKeyNot($year->id)
            ->update(['is_current' => false]);

        if (!$year->terms()->withoutGlobalScopes()->exists()) {
            foreach (['Term 1', 'Term 2', 'Term 3'] as $index => $termName) {
                AcademicTerm::withoutGlobalScopes()->firstOrCreate([
                    'school_id' => $schoolId,
                    'academic_year_id' => $year->id,
                    'name' => $termName,
                ], [
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'is_current' => $index === 0,
                ]);
            }
        }

        return back()->with('success', 'Academic year saved and marked as current.');
    }

    public function activateAcademicYear(AcademicYear $year)
    {
        $school = auth()->user()?->school ?? (session('active_school_id') ? \App\Models\School::find(session('active_school_id')) : null);
        if (!$school || (int) $year->school_id !== (int) $school->id) {
            abort(403, 'Access denied. You do not own this academic year.');
        }

        AcademicYear::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->update(['is_current' => false]);

        $year->update(['is_current' => true]);

        if (!$year->terms()->withoutGlobalScopes()->where('is_current', true)->exists()) {
            $year->terms()->withoutGlobalScopes()->orderBy('name')->first()?->update(['is_current' => true]);
        }

        return back()->with('success', $year->name . ' is now the active academic year.');
    }

    public function storeAcademicTerm(Request $request)
    {
        $school = auth()->user()?->school ?? (session('active_school_id') ? \App\Models\School::find(session('active_school_id')) : null);
        if (!$school) {
            abort(403, 'No school assigned to your account.');
        }

        $validated = $request->validate([
            'academic_year_id' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('academic_years', 'id')->where('school_id', $school->id),
            ],
            'name' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'academic_year_id.exists' => 'The selected academic year is invalid or does not belong to your school.',
            'end_date.after_or_equal' => 'The end date must not be before the start date.',
        ]);

        $academicYear = AcademicYear::withoutGlobalScopes()
            ->where('id', $validated['academic_year_id'])
            ->where('school_id', $school->id)
            ->first();

        if (!$academicYear) {
            abort(403, 'Unauthorized: The selected academic year belongs to another school.');
        }

        $term = AcademicTerm::withoutGlobalScopes()->updateOrCreate(
            [
                'school_id' => (int) $school->id,
                'academic_year_id' => (int) $academicYear->id,
                'name' => trim($validated['name']),
            ],
            [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_current' => true,
            ]
        );

        AcademicTerm::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->whereKeyNot($term->id)
            ->update(['is_current' => false]);

        return back()->with('success', 'Academic term saved and marked as current.');
    }

    public function activateAcademicTerm(AcademicTerm $term)
    {
        $school = auth()->user()?->school ?? (session('active_school_id') ? \App\Models\School::find(session('active_school_id')) : null);
        if (!$school || (int) $term->school_id !== (int) $school->id) {
            abort(403, 'Access denied. You do not own this academic term.');
        }

        AcademicTerm::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $term->academic_year_id)
            ->update(['is_current' => false]);

        $term->update(['is_current' => true]);

        return back()->with('success', $term->name . ' is now the active term.');
    }

    public function updateSettings(Request $request)
    {
        $school = auth()->user()?->school ?? (session('active_school_id') ? \App\Models\School::find(session('active_school_id')) : null);
        if (!$school) {
            abort(403, 'No school assigned to your account.');
        }

        $school->update($request->only(['name', 'motto', 'address', 'county', 'phone', 'email', 'curriculum_type']));

        \App\Models\SchoolSetting::updateOrCreate(
            ['school_id' => $school->id],
            $request->only(['currency', 'daraja_shortcode', 'sms_sender_id', 'timezone'])
        );

        return back()->with('success', 'School configuration and academic settings updated.');
    }
}
