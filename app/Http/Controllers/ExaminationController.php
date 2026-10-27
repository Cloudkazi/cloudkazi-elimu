<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\CbcAssessment;
use App\Models\Examination;
use App\Models\ExamMark844;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentReportSummary;
use App\Models\Subject;
use App\Models\SubjectOffering;
use App\Services\AcademicAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class ExaminationController extends Controller
{
    protected AcademicAssessmentService $assessmentService;

    public function __construct(AcademicAssessmentService $assessmentService)
    {
        $this->assessmentService = $assessmentService;
    }

    public function index()
    {
        $user = Auth::user();
        if ($user?->isStudent()) {
            return redirect()->route('student.dashboard');
        }
        if ($user?->isParent()) {
            return redirect()->route('parent.dashboard');
        }

        $schoolId = auth()->user()->school_id ?? session('active_school_id');

        $years = $schoolId ? AcademicYear::where('school_id', $schoolId)->orderBy('name', 'desc')->get() : AcademicYear::orderBy('name', 'desc')->get();
        if ($schoolId && $years->isEmpty()) {
            AcademicYear::create([
                'school_id' => $schoolId,
                'name' => date('Y'),
                'start_date' => date('Y-01-01'),
                'end_date' => date('Y-12-31'),
                'is_current' => true,
            ]);
            $years = AcademicYear::where('school_id', $schoolId)->orderBy('name', 'desc')->get();
        }

        $terms = $schoolId ? AcademicTerm::where('school_id', $schoolId)->orderBy('name')->get() : AcademicTerm::orderBy('name')->get();
        if ($schoolId && $terms->isEmpty()) {
            $yearId = $years->first()?->id;
            foreach (['Term 1', 'Term 2', 'Term 3'] as $index => $termName) {
                AcademicTerm::firstOrCreate([
                    'school_id' => $schoolId,
                    'academic_year_id' => $yearId,
                    'name' => $termName,
                ], [
                    'start_date' => date('Y-m-d', strtotime("+{$index} months")),
                    'end_date' => date('Y-m-d', strtotime("+{$index} months +3 months")),
                    'is_current' => $index === 0,
                ]);
            }
            $terms = AcademicTerm::where('school_id', $schoolId)->orderBy('name')->get();
        }

        $examinations = Examination::with(['academicYear', 'term'])
            ->withCount(['marks844', 'cbcAssessments', 'reportSummaries'])
            ->latest()
            ->get();

        $classes = SchoolClass::with('streams')->get();

        return view('examinations.index', compact('examinations', 'classes', 'years', 'terms'));
    }

    private function authorizeExamStaff(): void
    {
        $user = Auth::user();
        if (!$user || (!$user->isSchoolAdmin() && !$user->isTeacher() && !$user->isSuperAdmin())) {
            abort(403, 'Unauthorized. Only teachers and administrators can configure exams or enter marks.');
        }
    }

    public function store(Request $request)
    {
        $this->authorizeExamStaff();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id' => 'required|exists:academic_terms,id',
            'curriculum_type' => 'required|in:CBC,8-4-4,Both',
            'exam_type' => 'required|in:opener,midterm,endterm,mock,cbc_assessment',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        Examination::create($validated);
        return back()->with('success', "Examination session '{$validated['name']}' scheduled.");
    }

    public function marks(Request $request, int $examId)
    {
        $this->authorizeExamStaff();

        $exam = Examination::with(['academicYear', 'term'])->findOrFail($examId);
        $schoolId = auth()->user()?->school_id ?? session('active_school_id') ?? $exam->school_id;

        // 1. Dynamic Examinations List for dropdown (respecting school, NO is_published filter)
        $exams = Examination::where('school_id', $schoolId)
            ->with(['academicYear', 'term'])
            ->latest()
            ->get();
        if ($exams->isEmpty()) {
            $exams = collect([$exam]);
        }

        // 2. Real Classes & Streams for school
        $classes = SchoolClass::where('school_id', $schoolId)
            ->with('streams')
            ->orderBy('name')
            ->get();

        $classOptions = [];
        foreach ($classes as $c) {
            if ($c->streams->isNotEmpty()) {
                foreach ($c->streams as $str) {
                    $classOptions[] = [
                        'value' => "{$c->id}:{$str->id}",
                        'class_id' => $c->id,
                        'stream_id' => $str->id,
                        'label' => "{$c->name} — {$str->name}",
                    ];
                }
                $classOptions[] = [
                    'value' => "{$c->id}",
                    'class_id' => $c->id,
                    'stream_id' => null,
                    'label' => "{$c->name} (All Streams)",
                ];
            } else {
                $classOptions[] = [
                    'value' => "{$c->id}",
                    'class_id' => $c->id,
                    'stream_id' => null,
                    'label' => $c->name,
                ];
            }
        }

        // 3. Resolve selected class and stream
        $rawClassId = (string) $request->input('class_id', '');
        $selectedClassId = null;
        $selectedStreamId = null;
        $selectedClassValue = '';

        if ($rawClassId !== '') {
            if (str_contains($rawClassId, ':')) {
                [$cId, $sId] = explode(':', $rawClassId, 2);
                $selectedClassId = (int) $cId;
                $selectedStreamId = (int) $sId;
                $selectedClassValue = $rawClassId;
            } else {
                $selectedClassId = (int) $rawClassId;
                $selectedStreamId = $request->input('stream_id') ? (int) $request->input('stream_id') : null;
                $selectedClassValue = $selectedStreamId ? "{$selectedClassId}:{$selectedStreamId}" : (string) $selectedClassId;
            }
        } else {
            $firstOpt = $classOptions[0] ?? null;
            if ($firstOpt) {
                $selectedClassId = $firstOpt['class_id'];
                $selectedStreamId = $firstOpt['stream_id'];
                $selectedClassValue = $firstOpt['value'];
            }
        }

        $selectedClass = $selectedClassId ? $classes->firstWhere('id', $selectedClassId) : null;
        $selectedStream = ($selectedClass && $selectedStreamId) ? $selectedClass->streams->firstWhere('id', $selectedStreamId) : null;

        // 4. Subjects for this class/stream — sourced from SUBJECT OFFERINGS (admin-configured),
        //    NOT from teacher allocations or the global subjects table.
        //    This ensures only subjects assigned by admin to this class/stream appear.
        $offeringsQuery = SubjectOffering::where('school_id', $schoolId)
            ->where('school_class_id', $selectedClassId)
            ->where('is_active', true)
            ->with('subject');

        // Narrow to specific stream if selected; otherwise include class-wide offerings (stream_id IS NULL)
        if ($selectedStreamId) {
            $offeringsQuery->where(function ($q) use ($selectedStreamId) {
                $q->where('stream_id', $selectedStreamId)->orWhereNull('stream_id');
            });
        }

        // Filter by exam's academic year/term if both are set on the exam
        if ($exam->academic_year_id) {
            $offeringsQuery->where(function ($q) use ($exam) {
                $q->where('academic_year_id', $exam->academic_year_id)->orWhereNull('academic_year_id');
            });
        }
        if ($exam->term_id) {
            $offeringsQuery->where(function ($q) use ($exam) {
                $q->where('term_id', $exam->term_id)->orWhereNull('term_id');
            });
        }

        // Deduplicate: if same subject appears for both class-wide and stream-specific, prefer stream-specific.
        $rawOfferings = $offeringsQuery->orderBy('stream_id', 'desc')->get();
        $seenSubjectIds = [];
        $classOfferings = collect();
        foreach ($rawOfferings as $offering) {
            if (!in_array($offering->subject_id, $seenSubjectIds)) {
                $seenSubjectIds[] = $offering->subject_id;
                $classOfferings->push($offering);
            }
        }

        // Build a Subject collection from offerings for backward-compatible view rendering
        $subjects = $classOfferings->map(fn($o) => $o->subject)->filter()->sortBy('name')->values();

        // Build a quick offering lookup: subject_id => offering_id (for mark saving)
        $offeringBySubject = $classOfferings->keyBy('subject_id');

        $selectedSubjectId = $request->filled('subject_id') ? (int) $request->input('subject_id') : null;
        $selectedSubject = $selectedSubjectId ? $subjects->firstWhere('id', $selectedSubjectId) : null;

        if ($selectedSubjectId && $selectedSubject) {
            $examSubjects = $subjects->where('id', $selectedSubjectId)->values();
        } else {
            $examSubjects = $subjects;
            $selectedSubjectId = null;
        }

        // 5. Real Students enrolled in selected class/stream
        $students = collect();
        if ($selectedClassId) {
            $studentsQuery = Student::where('school_id', $schoolId)
                ->where('school_class_id', $selectedClassId)
                ->whereIn('status', ['active', 'alumni', 'enrolled']);

            if ($selectedStreamId) {
                $studentsQuery->where('stream_id', $selectedStreamId);
            }

            $students = $studentsQuery->orderBy('first_name')->orderBy('last_name')->get();
        }

        // 6. Fetch existing marks
        $existingMarks = [];
        $studentTotals = [];
        $studentGrades = [];
        $studentRemarks = [];

        if ($students->isNotEmpty()) {
            $allMarksForExam = ExamMark844::where('examination_id', $examId)
                ->whereIn('student_id', $students->pluck('id'))
                ->get();

            foreach ($allMarksForExam as $m) {
                $existingMarks[$m->student_id][$m->subject_id] = $m;
            }

            foreach ($students as $st) {
                $stMarks = $allMarksForExam->where('student_id', $st->id);
                if ($stMarks->isNotEmpty()) {
                    $tot = $stMarks->sum('score');
                    $maxTot = $stMarks->sum('out_of') ?: ($stMarks->count() * 100);
                    $studentTotals[$st->id] = "{$tot}/{$maxTot}";
                    $pct = $maxTot > 0 ? ($tot / $maxTot * 100) : 0;
                    $studentGrades[$st->id] = $this->assessmentService->get844GradeAndPoints($pct)['grade'];
                    $studentRemarks[$st->id] = $stMarks->first()->remarks ?? '';
                } else {
                    $studentTotals[$st->id] = '—';
                    $studentGrades[$st->id] = '—';
                    $studentRemarks[$st->id] = '';
                }
            }
        }

        $selectedExam = $exam;
        $outOf = 100;

        return view('examinations.marks', compact(
            'exam',
            'selectedExam',
            'exams',
            'classes',
            'classOptions',
            'selectedClass',
            'selectedClassId',
            'selectedClassValue',
            'selectedStream',
            'selectedStreamId',
            'subjects',
            'selectedSubject',
            'selectedSubjectId',
            'examSubjects',
            'classOfferings',
            'offeringBySubject',
            'students',
            'existingMarks',
            'studentTotals',
            'studentGrades',
            'studentRemarks',
            'outOf'
        ));
    }

    public function storeMarks(Request $request, int $examId)
    {
        $this->authorizeExamStaff();

        $exam = Examination::findOrFail($examId);
        $schoolId = auth()->user()?->school_id ?? session('active_school_id') ?? $exam->school_id;

        $rawClassId = (string) $request->input('class_id');
        $classId    = str_contains($rawClassId, ':') ? (int) explode(':', $rawClassId)[0] : (int) $rawClassId;
        $streamId   = str_contains($rawClassId, ':') ? (int) explode(':', $rawClassId)[1] : null;

        // Build a subject_id => offering_id map for fast lookup during save
        $offeringsMap = SubjectOffering::where('school_id', $schoolId)
            ->where('school_class_id', $classId)
            ->where('is_active', true)
            ->when($streamId, fn ($q) => $q->where(fn($q2) => $q2->where('stream_id', $streamId)->orWhereNull('stream_id')))
            ->get()
            ->keyBy('subject_id');

        $marksData   = $request->input('marks', []);
        $remarksData = $request->input('remarks', []);

        foreach ($marksData as $studentId => $subjectScores) {
            $student = Student::find($studentId);
            if (!$student) continue;

            if (is_array($subjectScores)) {
                foreach ($subjectScores as $subjectId => $scoreVal) {
                    if ($scoreVal === null || $scoreVal === '') continue;
                    $offeringId = $offeringsMap->get($subjectId)?->id;
                    $this->saveStudentMark($exam, $student, (int) $subjectId, (float) $scoreVal, $remarksData[$studentId] ?? null, $offeringId);
                }
            } elseif (is_numeric($subjectScores)) {
                $subjectId = (int) $request->input('subject_id');
                if ($subjectId) {
                    $offeringId = $offeringsMap->get($subjectId)?->id;
                    $this->saveStudentMark($exam, $student, $subjectId, (float) $subjectScores, $remarksData[$studentId] ?? null, $offeringId);
                }
            }
        }

        if ($classId) {
            $this->assessmentService->computeExamSummaries($exam->id, $classId);
        }

        if ($request->input('action') === 'save_publish') {
            $exam->update(['is_published' => 1]);
        }

        return back()->with('success', 'Examination marks saved successfully and rankings updated.');
    }

    protected function saveStudentMark(Examination $exam, Student $student, int $subjectId, float $score, ?string $userRemark = null, ?int $subjectOfferingId = null): void
    {
        $gp = $this->assessmentService->get844GradeAndPoints($score);
        $remark = $userRemark ?: $gp['remarks'];

        ExamMark844::updateOrCreate([
            'school_id'      => $exam->school_id,
            'examination_id' => $exam->id,
            'student_id'     => $student->id,
            'subject_id'     => $subjectId,
        ], [
            'subject_offering_id' => $subjectOfferingId,
            'score'          => $score,
            'out_of'         => 100.00,
            'grade'          => $gp['grade'],
            'points'         => $gp['points'],
            'remarks'        => $remark,
            'recorded_by'    => Auth::id(),
        ]);

        if ($student->curriculum === 'CBC') {
            $level = match (true) {
                $score >= 80 => 'EE',
                $score >= 65 => 'ME',
                $score >= 50 => 'AE',
                default      => 'BE',
            };
            $points = match ($level) {
                'EE'    => 4,
                'ME'    => 3,
                'AE'    => 2,
                default => 1,
            };

            CbcAssessment::updateOrCreate([
                'school_id'           => $exam->school_id,
                'examination_id'      => $exam->id,
                'student_id'          => $student->id,
                'subject_id'          => $subjectId,
            ], [
                'subject_offering_id' => $subjectOfferingId,
                'strand'              => 'Core Competency',
                'sub_strand'          => 'Formative Evaluation',
                'achievement_level'   => $level,
                'score_points'        => $points,
                'rubric_feedback'     => $remark,
                'assessed_by'         => Auth::id(),
            ]);
        }
    }

    public function reportCard(int $examId, int $studentId)
    {
        $exam    = Examination::with(['academicYear', 'term'])->findOrFail($examId);
        $student = Student::with(['schoolClass', 'stream', 'school'])->findOrFail($studentId);

        // Authorization: students can only view their own report card; parents only linked children
        Gate::authorize('view-student-marks', $student);

        // School branding — loaded from the student's school record
        $school = $student->school ?? School::find($student->school_id);

        // Report summary (rankings, mean grade, teacher/principal remarks)
        $summary = StudentReportSummary::where('examination_id', $examId)
            ->where('student_id', $studentId)
            ->first();

        // 8-4-4 marks
        $marks844 = ExamMark844::with('subject')
            ->where('examination_id', $examId)
            ->where('student_id', $studentId)
            ->orderBy('subject_id')
            ->get();

        // CBC assessments
        $cbcAssessments = CbcAssessment::with('subject')
            ->where('examination_id', $examId)
            ->where('student_id', $studentId)
            ->orderBy('subject_id')
            ->get();

        // Decide curriculum type for this report
        $isCBC = $student->curriculum === 'CBC';

        $yearExams = Examination::with(['term'])
            ->where('school_id', $student->school_id)
            ->where('academic_year_id', $exam->academic_year_id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $yearExamIds = $yearExams->pluck('id');
        $yearMarks844 = collect();
        $yearCbcAssessments = collect();
        if ($isCBC) {
            $yearCbcAssessments = CbcAssessment::with(['subject', 'examination'])
                ->where('student_id', $studentId)
                ->whereIn('examination_id', $yearExamIds)
                ->orderBy('examination_id')
                ->orderBy('subject_id')
                ->orderBy('strand')
                ->orderBy('sub_strand')
                ->get();
        } else {
            $yearMarks844 = ExamMark844::with(['subject', 'examination'])
                ->where('student_id', $studentId)
                ->whereIn('examination_id', $yearExamIds)
                ->orderBy('examination_id')
                ->orderBy('subject_id')
                ->get();
        }

        $yearResultsByExam = ($isCBC ? $yearCbcAssessments : $yearMarks844)
            ->groupBy('examination_id');

        $yearTotalScore = $yearMarks844->sum('score');
        $yearTotalOutOf = $yearMarks844->sum('out_of');
        $yearAverage = $yearTotalOutOf > 0
            ? round($yearTotalScore / $yearTotalOutOf * 100, 1)
            : null;
        $yearOverallGrade = $yearAverage !== null
            ? ($this->assessmentService->get844GradeAndPoints($yearAverage)['grade'] ?? '—')
            : null;

        $yearSubjectPositions = [];
        if (!$isCBC && $yearMarks844->isNotEmpty()) {
            foreach ($yearMarks844 as $mark) {
                $key = $mark->examination_id . '_' . $mark->subject_id;
                if (!isset($yearSubjectPositions[$key])) {
                    $yearSubjectPositions[$key] = ExamMark844::where('school_id', $student->school_id)
                        ->where('examination_id', $mark->examination_id)
                        ->where('subject_id', $mark->subject_id)
                        ->where('score', '>', $mark->score)
                        ->count() + 1;
                }
            }
        }

        // Unified results list (for the blade table)
        if ($isCBC) {
            $results = $cbcAssessments;
        } else {
            $results = $marks844;
        }

        // Compute totals / averages for 8-4-4
        $totalScore   = null;
        $totalOutOf   = null;
        $avgPercent   = null;
        $overallGrade = $summary?->mean_grade ?? null;
        if (!$isCBC && $marks844->isNotEmpty()) {
            $totalScore = $marks844->sum('score');
            $totalOutOf = $marks844->sum('out_of');
            $avgPercent = $totalOutOf > 0 ? round($totalScore / $totalOutOf * 100, 1) : 0;
            if (!$overallGrade) {
                $overallGrade = $this->assessmentService->get844GradeAndPoints($avgPercent)['grade'] ?? '—';
            }
        }

        // Class & stream position
        $overallPosition = $summary?->class_rank ?? null;
        $streamPosition  = $summary?->stream_rank ?? null;
        // Total students in the class (for context)
        $classTotal  = Student::where('school_class_id', $student->school_class_id)->whereIn('status', ['active', 'alumni', 'enrolled'])->count();
        $streamTotal = $student->stream_id
            ? Student::where('stream_id', $student->stream_id)->whereIn('status', ['active', 'alumni', 'enrolled'])->count()
            : null;

        // Attendance calculation (current academic term)
        $attendanceRate = null;
        if (Schema::hasTable('student_attendances') && $exam->term_id) {
            $termDates = \App\Models\AcademicTerm::find($exam->term_id);
            $attendanceQuery = DB::table('student_attendances')
                ->where('student_id', $studentId);
            if ($termDates?->start_date && $termDates?->end_date) {
                $attendanceQuery->whereBetween('attendance_date', [$termDates->start_date, $termDates->end_date]);
            }
            $totalDays   = $attendanceQuery->count();
            $presentDays = $attendanceQuery->where('status', 'present')->count();
            $attendanceRate = $totalDays > 0 ? round($presentDays / $totalDays * 100, 1) : null;
        }

        // Remarks from report summary
        $teacherComment = $summary?->class_teacher_remarks;
        $headComment    = $summary?->principal_remarks;

        // Selected exam object (for the view header)
        $selectedExam = $exam;

        // Per-subject position within the class for 8-4-4
        $subjectPositions = [];
        if (!$isCBC && $marks844->isNotEmpty()) {
            foreach ($marks844 as $mark) {
                $subjectId = $mark->subject_id;
                // Count students with higher score in same exam + subject
                $higher = ExamMark844::where('examination_id', $examId)
                    ->where('subject_id', $subjectId)
                    ->where('score', '>', $mark->score)
                    ->count();
                $subjectPositions[$subjectId] = $higher + 1;
            }
        }

        return view('examinations.report-card', compact(
            'exam',
            'student',
            'school',
            'summary',
            'marks844',
            'cbcAssessments',
            'isCBC',
            'results',
            'totalScore',
            'totalOutOf',
            'avgPercent',
            'overallGrade',
            'overallPosition',
            'streamPosition',
            'classTotal',
            'streamTotal',
            'attendanceRate',
            'teacherComment',
            'headComment',
            'selectedExam',
            'subjectPositions',
            'yearExams',
            'yearResultsByExam',
            'yearMarks844',
            'yearCbcAssessments',
            'yearSubjectPositions',
            'yearTotalScore',
            'yearTotalOutOf',
            'yearAverage',
            'yearOverallGrade'
        ));
    }

    public function generalMarks(Request $request)
    {
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');

        $examId = $request->input('exam_id');
        if ($examId) {
            $exam = Examination::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->find($examId);
        } else {
            $exam = Examination::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->latest()->first();
        }

        if (!$exam) {
            return redirect()->route('examinations.index')->with('error', 'No examination session found. Please schedule one first.');
        }

        return $this->marks($request, $exam->id);
    }

    public function results(Request $request)
    {
        $user = Auth::user();
        $schoolId = $user?->school_id ?? session('active_school_id');

        if ($user?->isStudent()) {
            $myStudent = $user->student;
            if ($myStudent) {
                return redirect()->route('examinations.student-results', $myStudent->id);
            }
            return redirect()->route('student.dashboard');
        }

        $examinations = Examination::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->with(['academicYear', 'term'])
            ->latest()
            ->get();
        $selectedExamId = $request->input('exam_id', $examinations->first()?->id);
        $selectedExam = Examination::find($selectedExamId);

        if ($user?->isParent()) {
            $linkedStudents = $user->getLinkedStudents();
            $linkedStudentIds = $linkedStudents->pluck('id')->toArray();

            if (empty($linkedStudentIds)) {
                return redirect()->route('parent.dashboard')->with('info', 'No linked student accounts found.');
            }

            $classes = SchoolClass::whereIn('id', $linkedStudents->pluck('school_class_id')->unique())->with('streams')->get();
            $selectedClassId = $request->input('class_id', $classes->first()?->id);

            $summaries = StudentReportSummary::where('examination_id', $selectedExamId)
                ->whereIn('student_id', $linkedStudentIds)
                ->with(['student.schoolClass', 'student.stream'])
                ->orderBy('class_rank')
                ->paginate(20);

            return view('examinations.results', compact('examinations', 'selectedExam', 'classes', 'selectedClassId', 'summaries'));
        }

        $classes = SchoolClass::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->with('streams')->get();
        $selectedClassId = $request->input('class_id', $classes->first()?->id);

        $summaries = StudentReportSummary::where('examination_id', $selectedExamId)
            ->whereHas('student', fn($q) => $q->where('school_class_id', $selectedClassId))
            ->with(['student.schoolClass', 'student.stream'])
            ->orderBy('class_rank')
            ->paginate(20);

        return view('examinations.results', compact('examinations', 'selectedExam', 'classes', 'selectedClassId', 'summaries'));
    }

    public function reportCardsList(Request $request)
    {
        $user = Auth::user();
        $schoolId = $user?->school_id ?? session('active_school_id');

        $examinations = Examination::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->with(['academicYear', 'term'])
            ->latest()
            ->get();
        $selectedExamId = $request->input('exam_id', $examinations->first()?->id);

        if ($user?->isStudent()) {
            $myStudent = $user->student;
            if ($myStudent && $selectedExamId) {
                return redirect()->route('examinations.report-card', ['examId' => $selectedExamId, 'studentId' => $myStudent->id]);
            }
            return redirect()->route('student.dashboard');
        }

        if ($user?->isParent()) {
            $linkedStudents = $user->getLinkedStudents();
            $linkedStudentIds = $linkedStudents->pluck('id')->toArray();

            $classes = SchoolClass::whereIn('id', $linkedStudents->pluck('school_class_id')->unique())->with('streams')->get();
            $selectedClassId = $request->input('class_id', $classes->first()?->id);

            $students = Student::whereIn('id', $linkedStudentIds)
                ->with(['schoolClass', 'reportSummaries' => fn($q) => $q->where('examination_id', $selectedExamId)])
                ->paginate(20);

            return view('examinations.report-cards', compact('examinations', 'selectedExamId', 'classes', 'selectedClassId', 'students'));
        }

        $classes = SchoolClass::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->with('streams')->get();
        $selectedClassId = $request->input('class_id', $classes->first()?->id);

        $students = Student::where('school_class_id', $selectedClassId)
            ->whereIn('status', ['active', 'alumni', 'enrolled'])
            ->with(['schoolClass', 'reportSummaries' => fn($q) => $q->where('examination_id', $selectedExamId)])
            ->paginate(20);

        return view('examinations.report-cards', compact('examinations', 'selectedExamId', 'classes', 'selectedClassId', 'students'));
    }

    public function storeMarksGeneral(Request $request)
    {
        $examId = $request->input('exam_id');
        if (!$examId) {
            return back()->with('error', 'Examination session not found.');
        }
        return $this->storeMarks($request, (int) $examId);
    }

    public function studentResults(int $studentId)
    {
        $student = Student::with(['schoolClass', 'stream'])->findOrFail($studentId);

        // Authorization guard — must pass before any marks data is loaded
        Gate::authorize('view-student-marks', $student);

        $latestSummary = StudentReportSummary::where('student_id', $studentId)->latest()->first();
        $latestExam = $latestSummary ? Examination::find($latestSummary->examination_id) : Examination::latest()->first();

        if ($latestExam) {
            return $this->reportCard($latestExam->id, $studentId);
        }

        return redirect()->route('students.show', $studentId)->with('info', 'No examination marks recorded yet for this student.');
    }
}
