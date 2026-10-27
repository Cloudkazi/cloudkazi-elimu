<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\BookIssue;
use App\Models\CbcAssessment;
use App\Models\Examination;
use App\Models\ExamMark844;
use App\Models\FeePayment;
use App\Models\Guardian;
use App\Models\LearningMaterial;
use App\Models\LearningChapter;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentInvoice;
use App\Models\StudentReportSummary;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherSubjectAllocation;
use App\Models\Timetable;
use App\Services\TimetableEngineService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PortalController extends Controller
{
    protected TimetableEngineService $timetableEngine;

    public function __construct(TimetableEngineService $timetableEngine)
    {
        $this->timetableEngine = $timetableEngine;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TEACHER PORTAL
    // ─────────────────────────────────────────────────────────────────────────

    public function teacher()
    {
        $user = Auth::user();

        if (!$user || !$user->isTeacher()) {
            abort(403, 'Access denied.');
        }

        // Find the teacher record linked to this user — never fall back to first()
        $teacher = $user->teacher;

        if (!$teacher) {
            return view('portals.teacher', [
                'teacher'          => null,
                'allocations'      => collect(),
                'activeTimetable'  => null,
                'teacherMatrix'    => [],
                'learningMaterials'=> collect(),
                'announcements'    => collect(),
                'unlinked'         => true,
            ]);
        }

        $allocations = TeacherSubjectAllocation::where('teacher_id', $teacher->id)
            ->with(['subject', 'schoolClass', 'stream'])
            ->get();

        $schoolId = $user->school_id ?? session('active_school_id');
        $activeTimetable = Timetable::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'published')
            ->orderByDesc('id')
            ->first();

        $teacherMatrix = [];
        if ($activeTimetable && $teacher) {
            $teacherMatrix = $this->timetableEngine->getTeacherTimetableMatrix($activeTimetable->id, $teacher->id);
        }

        $learningMaterials = LearningMaterial::where('teacher_id', $teacher->id)->latest()->take(5)->get();
        $announcements = Announcement::where('school_id', $schoolId)->latest()->take(3)->get();

        return view('portals.teacher', compact(
            'teacher',
            'allocations',
            'activeTimetable',
            'teacherMatrix',
            'learningMaterials',
            'announcements'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STUDENT PORTAL
    // ─────────────────────────────────────────────────────────────────────────

    public function student()
    {
        $user = Auth::user();

        if (!$user || !$user->isStudent()) {
            abort(403, 'Access denied.');
        }

        // Strictly resolve student from the authenticated user — no fallback to another student's record
        $student = $user->student
            ?? Student::where('user_id', $user->id)->first()
            ?? Student::where('admission_number', explode('@', $user->email)[0])->first();

        if (!$student) {
            return view('portals.student', [
                'student'        => null,
                'unlinked'       => true,
                'latestExam'     => null,
                'reportSummary'  => null,
                'marks844'       => collect(),
                'cbcAssessments' => collect(),
                'activeTimetable'=> null,
                'streamMatrix'   => [],
                'feeBreakdown'   => null,
                'recentPayments' => collect(),
                'hostelAllocation'=> null,
                'transportAllocation' => null,
                'materials'      => collect(),
                'announcements'  => collect(),
                'libraryIssues'  => collect(),
                'feeAccount' => null,
                'feeInvoices' => collect(),
                'feeYearSummary' => null,
                'practiceChapter' => null,
                'practiceAvailable' => false,
            ]);
        }

        // ── Academic performance — strictly filtered to THIS student ──────────
        $schoolId   = $student->school_id;
        $latestExam = Examination::where('school_id', $schoolId)->latest()->first();
        $reportSummary   = null;
        $marks844        = collect();
        $cbcAssessments  = collect();

        if ($latestExam) {
            $reportSummary = StudentReportSummary::where('examination_id', $latestExam->id)
                ->where('student_id', $student->id)
                ->first();

            $marks844 = ExamMark844::where('examination_id', $latestExam->id)
                ->where('student_id', $student->id)
                ->with('subject')
                ->get();

            $cbcAssessments = CbcAssessment::where('examination_id', $latestExam->id)
                ->where('student_id', $student->id)
                ->with('subject')
                ->get();
        }

        // ── Timetable — filtered to THIS student's class/stream ───────────────
        $activeTimetable = Timetable::where('school_id', $schoolId)
            ->where('status', 'published')
            ->orderByDesc('id')
            ->first();

        $streamMatrix = [];
        if ($activeTimetable && $student->stream_id) {
            $streamMatrix = $this->timetableEngine->getStreamTimetableMatrix(
                $activeTimetable->id,
                $student->stream_id
            );
        }

        // ── Dynamic fee breakdown by category from fee_payments table ─────────
        $feeBreakdown  = $student->calculateFeeBreakdown();
        $recentPayments = FeePayment::with(['invoice.academicYear', 'invoice.term'])
            ->where('student_id', $student->id)
            ->where('school_id', $schoolId)
            ->latest('payment_date')
            ->get();
        $feeInvoices = StudentInvoice::with(['academicYear', 'term'])
            ->where('student_id', $student->id)
            ->where('school_id', $schoolId)
            ->orderByDesc('academic_year_id')
            ->orderByDesc('term_id')
            ->get();
        $feeAccount = $this->feeAccountSummary($student, $feeInvoices, $feeBreakdown, $recentPayments);
        $feeYearSummary = $this->currentYearFeeSummary($schoolId, $student, $feeInvoices, $recentPayments);

        // ── Dormitory / Hostel allocation ─────────────────────────────────────
        $hostelAllocation = $student->hostels()
            ->with(['dormitory', 'room'])
            ->latest()
            ->first();

        // ── Transport allocation ──────────────────────────────────────────────
        $transportAllocation = $student->transports()
            ->with('route')
            ->where('is_active', true)
            ->first();

        // ── Study materials — filtered to this student's class ────────────────
        $materials = LearningMaterial::where('school_id', $schoolId)
            ->where('school_class_id', $student->school_class_id)
            ->latest()
            ->get();

        $announcements = Announcement::where('school_id', $schoolId)->latest()->take(4)->get();
        $practiceAvailable = $this->practiceTablesAvailable();
        $practiceChapter = $practiceAvailable
            ? LearningChapter::with('subject')
                ->where('school_id', $schoolId)
                ->where('school_class_id', $student->school_class_id)
                ->where('is_published', true)
                ->whereHas('questions', fn ($query) => $query->where('is_published', true))
                ->orderBy('subject_id')
                ->orderBy('sequence')
                ->first()
            : null;

        // ── Library books issued strictly to THIS logged-in student (eager loaded to prevent N+1) ──
        $libraryIssues = BookIssue::where('student_id', $student->id)
            ->where('school_id', $schoolId)
            ->with(['book.category'])
            ->orderByRaw("CASE WHEN status = 'issued' THEN 0 ELSE 1 END")
            ->latest('issue_date')
            ->get();

        return view('portals.student', compact(
            'student',
            'latestExam',
            'reportSummary',
            'marks844',
            'cbcAssessments',
            'activeTimetable',
            'streamMatrix',
            'feeBreakdown',
            'recentPayments',
            'hostelAllocation',
            'transportAllocation',
            'materials',
            'announcements',
            'libraryIssues',
            'feeAccount',
            'feeInvoices',
            'feeYearSummary',
            'practiceChapter',
            'practiceAvailable'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PARENT PORTAL
    // ─────────────────────────────────────────────────────────────────────────

    public function parent()
    {
        $user = Auth::user();

        if (!$user || !$user->isParent()) {
            abort(403, 'Access denied.');
        }

        $guardian = $user->guardian
            ?? Guardian::where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->when(!empty($user->phone), fn ($q) => $q->orWhere('phone', $user->phone))
                ->first();

        // ── Gather ALL linked children strictly via User helper (no cross-leakage) ──
        $children = $user->getLinkedStudents();

        // ── Selected child — must be one of THIS parent's children ───────────
        $selectedStudentId = request('student_id');
        if ($selectedStudentId) {
            $selectedStudent = $children->firstWhere('id', (int) $selectedStudentId);
            // Security: if the requested student_id is NOT in their children list, deny
            if (!$selectedStudent) {
                abort(403, 'You are not authorized to view this student\'s information.');
            }
        } else {
            $selectedStudent = $children->first();
        }

        // ── Per-child data (only if a child is selected) ─────────────────────
        $feeBreakdown        = null;
        $feeAccount          = null;
        $feeYearSummary      = null;
        $recentPayments      = collect();
        $invoices            = collect();
        $latestExam          = null;
        $reportSummary       = null;
        $recentAttendance    = collect();
        $hostelAllocation    = null;
        $transportAllocation = null;
        $streamMatrix        = [];
        $activeTimetable     = null;

        if ($selectedStudent) {
            $schoolId = $selectedStudent->school_id;

            // Dynamic fee breakdown with categories
            $feeBreakdown = $selectedStudent->calculateFeeBreakdown();

            // Actual payments from fee_payments table
            $recentPayments = FeePayment::with(['invoice.academicYear', 'invoice.term'])
                ->where('student_id', $selectedStudent->id)
                ->where('school_id', $schoolId)
                ->latest('payment_date')
                ->get();

            // Invoices for this child only
            $invoices = $selectedStudent->invoices()->with(['academicYear', 'term'])->get();
            $feeAccount = $this->feeAccountSummary($selectedStudent, $invoices, $feeBreakdown, $recentPayments);
            $feeYearSummary = $this->currentYearFeeSummary($schoolId, $selectedStudent, $invoices, $recentPayments);

            // Exam results — filtered to THIS child only
            $latestExam = Examination::where('school_id', $schoolId)->latest()->first();
            if ($latestExam) {
                $reportSummary = StudentReportSummary::where('examination_id', $latestExam->id)
                    ->where('student_id', $selectedStudent->id)
                    ->first();
            }

            // Attendance — filtered to THIS child
            $recentAttendance = StudentAttendance::where('student_id', $selectedStudent->id)
                ->latest('attendance_date')
                ->take(7)
                ->get();

            // Dormitory/hostel allocation
            $hostelAllocation = $selectedStudent->hostels()
                ->with(['dormitory', 'room'])
                ->latest()
                ->first();

            // Transport allocation
            $transportAllocation = $selectedStudent->transports()
                ->with('route')
                ->where('is_active', true)
                ->first();

            // Timetable — ONLY this child's enrolled class/stream
            $activeTimetable = Timetable::where('school_id', $schoolId)
                ->where('status', 'published')
                ->orderByDesc('id')
                ->first();

            if ($activeTimetable && $selectedStudent->stream_id) {
                $streamMatrix = $this->timetableEngine->getStreamTimetableMatrix(
                    $activeTimetable->id,
                    $selectedStudent->stream_id
                );
            }
        }

        $announcements = Announcement::when(
            $selectedStudent,
            fn ($q) => $q->where('school_id', $selectedStudent->school_id)
        )->latest()->take(4)->get();

        $paymentSettings = null;
        $bankAccounts = collect();
        if ($selectedStudent) {
            $paymentSettings = \App\Models\SchoolSetting::where('school_id', $selectedStudent->school_id)->first();
            $bankAccounts = \App\Models\SchoolBankAccount::where('school_id', $selectedStudent->school_id)
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->get();
        }

        return view('portals.parent', compact(
            'guardian',
            'children',
            'selectedStudent',
            'feeBreakdown',
            'feeAccount',
            'feeYearSummary',
            'recentPayments',
            'invoices',
            'latestExam',
            'reportSummary',
            'recentAttendance',
            'hostelAllocation',
            'transportAllocation',
            'activeTimetable',
            'streamMatrix',
            'announcements',
            'paymentSettings',
            'bankAccounts'
        ));
    }

    public function studentDiary(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->isStudent(), 403, 'Access denied.');
        $student = $this->resolveStudentForPortal($user);

        return $this->renderDiary($request, $student, 'Students', collect(), null);
    }

    public function parentDiary(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->isParent(), 403, 'Access denied.');
        $children = $user->getLinkedStudents();
        $student = $this->selectLinkedChild($request, $children);

        if (!$student) {
            return view('portals.diary', [
                'student' => null,
                'children' => $children,
                'entries' => collect(),
                'parentMode' => true,
                'calendarMonth' => now()->startOfMonth(),
                'calendarWeeks' => [],
                'readAnnouncementIds' => collect(),
            ]);
        }

        return $this->renderDiary($request, $student, 'Parents', $children, $student);
    }

    public function markAnnouncementRead(Announcement $announcement)
    {
        $user = Auth::user();
        abort_unless($user && ($user->isStudent() || $user->isParent()), 403);

        if ($user->isStudent()) {
            $student = $this->resolveStudentForPortal($user);
            abort_unless((int) $announcement->school_id === (int) $student->school_id, 404);
            abort_unless(in_array($announcement->target_audience, ['All', 'Students'], true), 404);
        } else {
            $children = $user->getLinkedStudents();
            abort_unless($children->contains(fn ($child) => (int) $child->school_id === (int) $announcement->school_id), 404);
            abort_unless(in_array($announcement->target_audience, ['All', 'Parents'], true), 404);
        }

        if (!Schema::hasTable('portal_announcement_reads')) {
            return back()
                ->with('info', 'Announcement read tracking is unavailable until the portal database migration is applied.')
                ->withFragment('diary-entry-' . $announcement->id);
        }

        DB::table('portal_announcement_reads')->updateOrInsert(
            ['user_id' => $user->id, 'announcement_id' => $announcement->id],
            ['read_at' => now()]
        );

        return back()->withFragment('diary-entry-' . $announcement->id);
    }

    public function downloadDiaryAttachment(LearningMaterial $material)
    {
        $user = Auth::user();
        abort_unless($user && ($user->isStudent() || $user->isParent()), 403);
        abort_unless($material->status === 'published' && $material->type === 'assignment', 404);

        if ($user->isStudent()) {
            $student = $this->resolveStudentForPortal($user);
            abort_unless(
                (int) $student->school_id === (int) $material->school_id
                && (int) $student->school_class_id === (int) $material->school_class_id,
                404
            );
        } else {
            $children = $user->getLinkedStudents();
            abort_unless($children->contains(fn ($child) =>
                (int) $child->school_id === (int) $material->school_id
                && (int) $child->school_class_id === (int) $material->school_class_id
            ), 404);
        }

        $path = ltrim((string) $material->file_path, '/\\');
        abort_if($path === '' || str_contains($path, '..') || !\Illuminate\Support\Facades\Storage::disk('public')->exists($path), 404);

        return \Illuminate\Support\Facades\Storage::disk('public')->download($path);
    }

    public function studentPerformance(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->isStudent(), 403, 'Access denied.');

        return $this->renderPerformance($request, $this->resolveStudentForPortal($user), false, collect());
    }

    public function parentPerformance(Request $request)
    {
        $user = Auth::user();
        abort_unless($user && $user->isParent(), 403, 'Access denied.');
        $children = $user->getLinkedStudents();
        $student = $this->selectLinkedChild($request, $children);

        if (!$student) {
            return view('portals.performance', [
                'student' => null,
                'children' => $children,
                'parentMode' => true,
                'years' => collect(),
                'terms' => collect(),
                'exams' => collect(),
                'selectedYearId' => null,
                'selectedTermId' => null,
                'selectedExamId' => null,
                'numericResults' => collect(),
                'cbcResults' => collect(),
                'summaries' => collect(),
                'strongSubjects' => collect(),
                'supportSubjects' => collect(),
                'scoreMode' => false,
            ]);
        }

        return $this->renderPerformance($request, $student, true, $children);
    }

    private function renderDiary(Request $request, Student $student, string $audience, $children, ?Student $selectedStudent)
    {
        $user = Auth::user();
        $schoolId = (int) $student->school_id;
        $validated = $request->validate(['month' => 'nullable|date_format:Y-m']);
        $calendarMonth = $request->query('month')
            ? Carbon::createFromFormat('!Y-m', $validated['month'])->startOfMonth()
            : now()->startOfMonth();
        $calendarMonth = $calendarMonth->copy()->startOfMonth();

        $announcements = Announcement::where('school_id', $schoolId)
            ->whereIn('target_audience', ['All', $audience])
            ->whereDate('published_at', '<=', today())
            ->orderByDesc('published_at')
            ->limit(100)
            ->get();
        $readAnnouncementIds = Schema::hasTable('portal_announcement_reads')
            ? DB::table('portal_announcement_reads')
                ->where('user_id', $user->id)
                ->whereIn('announcement_id', $announcements->pluck('id'))
                ->pluck('announcement_id')
                ->map(fn ($id) => (int) $id)
            : collect();

        $entries = collect();
        foreach ($announcements as $announcement) {
            $entries->push([
                'id' => 'announcement-' . $announcement->id,
                'announcement_id' => $announcement->id,
                'title' => $announcement->title,
                'category' => 'Announcement',
                'date' => Carbon::parse($announcement->published_at),
                'details' => $announcement->content,
                'is_read' => $readAnnouncementIds->contains((int) $announcement->id),
                'attachment' => null,
            ]);
        }

        $assignments = LearningMaterial::with('subject')
            ->where('school_id', $schoolId)
            ->where('school_class_id', $student->school_class_id)
            ->where('status', 'published')
            ->where('type', 'assignment')
            ->orderByRaw('COALESCE(due_date, created_at) DESC')
            ->limit(100)
            ->get();
        foreach ($assignments as $assignment) {
            $entries->push([
                'id' => 'assignment-' . $assignment->id,
                'announcement_id' => null,
                'title' => $assignment->title,
                'category' => 'Assignment' . ($assignment->subject?->name ? ' · ' . $assignment->subject->name : ''),
                'date' => $assignment->due_date
                    ? Carbon::parse($assignment->due_date)
                    : Carbon::parse($assignment->created_at),
                'details' => $assignment->description ?? 'No additional instructions provided.',
                'is_read' => true,
                'attachment' => $assignment->file_path,
            ]);
        }

        $examinations = Examination::with(['academicYear', 'term'])
            ->where('school_id', $schoolId)
            ->where('is_published', true)
            ->orderBy('start_date')
            ->limit(100)
            ->get();
        foreach ($examinations as $examination) {
            $entries->push([
                'id' => 'examination-' . $examination->id,
                'announcement_id' => null,
                'title' => $examination->name,
                'category' => 'Examination · ' . ($examination->term?->name ?? ''),
                'date' => Carbon::parse($examination->start_date),
                'end_date' => Carbon::parse($examination->end_date),
                'details' => 'Academic year: ' . ($examination->academicYear?->name ?? '—')
                    . ' · ' . Carbon::parse($examination->start_date)->format('d M Y')
                    . ' – ' . Carbon::parse($examination->end_date)->format('d M Y'),
                'is_read' => true,
                'attachment' => null,
            ]);
        }

        $examTimetables = Timetable::with(['academicYear', 'term'])
            ->where('school_id', $schoolId)
            ->where('type', 'exam')
            ->where('status', 'published')
            ->orderBy('academic_year_id')
            ->limit(30)
            ->get();
        foreach ($examTimetables as $timetable) {
            $entries->push([
                'id' => 'exam-timetable-' . $timetable->id,
                'announcement_id' => null,
                'title' => $timetable->name,
                'category' => 'Examination timetable · ' . ($timetable->term?->name ?? ''),
                'date' => Carbon::parse($timetable->term?->start_date ?? $timetable->created_at),
                'details' => $timetable->description ?: 'Published examination timetable for ' . ($timetable->academicYear?->name ?? 'the academic year') . '.',
                'is_read' => true,
                'attachment' => null,
            ]);
        }

        $entries = $entries->sortByDesc(fn ($entry) => $entry['date']->timestamp)->values();
        $firstWeekday = (int) $calendarMonth->dayOfWeekIso - 1;
        $calendarWeeks = [];
        $week = array_fill(0, $firstWeekday, null);
        for ($day = 1; $day <= $calendarMonth->daysInMonth; $day++) {
            $date = $calendarMonth->copy()->day($day);
            $week[] = [
                'day' => $day,
                'date' => $date->format('Y-m-d'),
                'entries' => $entries->filter(fn ($entry) => $entry['date']->isSameDay($date))->values(),
            ];
            if (count($week) === 7) {
                $calendarWeeks[] = $week;
                $week = [];
            }
        }
        if ($week) {
            $calendarWeeks[] = array_pad($week, 7, null);
        }

        return view('portals.diary', compact(
            'student',
            'children',
            'selectedStudent',
            'entries',
            'calendarMonth',
            'calendarWeeks',
            'readAnnouncementIds'
        ))->with('parentMode', $audience === 'Parents');
    }

    private function renderPerformance(Request $request, Student $student, bool $parentMode, $children)
    {
        $schoolId = (int) $student->school_id;
        $years = AcademicYear::where('school_id', $schoolId)->orderByDesc('start_date')->get();
        $selectedYearId = $request->integer('year_id');
        if (!$years->contains('id', $selectedYearId)) {
            $selectedYearId = (int) ($years->firstWhere('is_current', true)?->id ?? $years->first()?->id);
        }

        $terms = AcademicTerm::where('school_id', $schoolId)
            ->where('academic_year_id', $selectedYearId ?: 0)
            ->orderBy('start_date')
            ->get();
        $selectedTermId = $request->integer('term_id');
        if (!$terms->contains('id', $selectedTermId)) {
            $selectedTermId = null;
        }

        $exams = Examination::where('school_id', $schoolId)
            ->where('academic_year_id', $selectedYearId ?: 0)
            ->when($selectedTermId, fn ($query) => $query->where('term_id', $selectedTermId))
            ->orderBy('start_date')
            ->get();
        $selectedExamId = $request->integer('exam_id');
        if (!$exams->contains('id', $selectedExamId)) {
            $selectedExamId = null;
        }

        $examQuery = fn ($query) => $query->whereHas('examination', function ($examQuery) use ($schoolId, $selectedYearId, $selectedTermId, $selectedExamId) {
            $examQuery->where('school_id', $schoolId)
                ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
                ->when($selectedTermId, fn ($q) => $q->where('term_id', $selectedTermId))
                ->when($selectedExamId, fn ($q) => $q->whereKey($selectedExamId));
        });

        $numericResults = collect();
        $cbcResults = collect();
        $summaries = collect();
        $strongSubjects = collect();
        $supportSubjects = collect();
        $scoreMode = $student->curriculum !== 'CBC';

        if ($scoreMode) {
            $numericResults = ExamMark844::with(['subject', 'examination.term', 'examination.academicYear'])
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->where($examQuery)
                ->orderBy('examination_id')
                ->orderBy('subject_id')
                ->get();

            $summaries = $numericResults->groupBy('examination_id')->map(function ($marks) {
                $denominator = $marks->sum('out_of');
                return [
                    'exam' => $marks->first()->examination,
                    'mean' => $denominator > 0 ? round($marks->sum('score') / $denominator * 100, 1) : null,
                    'points' => $marks->sum('points'),
                    'subjects' => $marks->count(),
                ];
            })->values();

            $bySubject = $numericResults->groupBy(fn ($mark) => $mark->subject?->id);
            $ranked = $bySubject->map(function ($marks) {
                $outOf = $marks->sum('out_of');
                return [
                    'name' => $marks->first()->subject?->name ?? 'Unknown subject',
                    'average' => $outOf > 0 ? round($marks->sum('score') / $outOf * 100, 1) : null,
                    'assessments' => $marks->count(),
                ];
            })->filter(fn ($result) => $result['average'] !== null)->sortByDesc('average')->values();
            $strongSubjects = $ranked->take(3);
            $supportSubjects = $ranked->reverse()->take(3)->values();
        } else {
            $cbcResults = CbcAssessment::with(['subject', 'examination.term', 'examination.academicYear'])
                ->where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->where($examQuery)
                ->orderBy('examination_id')
                ->orderBy('subject_id')
                ->orderBy('strand')
                ->orderBy('sub_strand')
                ->get();

            $levelRank = ['BE' => 1, 'AE' => 2, 'ME' => 3, 'EE' => 4];
            $summaries = $cbcResults->groupBy('examination_id')->map(function ($assessments) use ($levelRank) {
                $levels = $assessments->pluck('achievement_level')->countBy();
                $meanRank = $assessments->avg(fn ($assessment) => $levelRank[$assessment->achievement_level] ?? null);
                return [
                    'exam' => $assessments->first()->examination,
                    'mean' => null,
                    'points' => null,
                    'subjects' => $assessments->pluck('subject_id')->unique()->count(),
                    'levels' => $levels,
                    'mean_level' => $meanRank === null ? null : (['BE', 'AE', 'ME', 'EE'][(int) round($meanRank) - 1] ?? null),
                ];
            })->values();

            $ranked = $cbcResults->groupBy(fn ($assessment) => $assessment->subject?->id)
                ->map(function ($assessments) use ($levelRank) {
                    $averageRank = $assessments->avg(fn ($assessment) => $levelRank[$assessment->achievement_level] ?? null);
                    return [
                        'name' => $assessments->first()->subject?->name ?? 'Unknown subject',
                        'level' => $averageRank === null ? null : (['BE', 'AE', 'ME', 'EE'][(int) round($averageRank) - 1] ?? null),
                        'assessments' => $assessments->count(),
                    ];
                })->filter(fn ($result) => $result['level'] !== null)
                ->sortBy(fn ($result) => $levelRank[$result['level']] ?? 0)
                ->values();
            $strongSubjects = $ranked->reverse()->take(3)->values();
            $supportSubjects = $ranked->take(3);
        }

        return view('portals.performance', compact(
            'student',
            'children',
            'parentMode',
            'years',
            'terms',
            'exams',
            'selectedYearId',
            'selectedTermId',
            'selectedExamId',
            'numericResults',
            'cbcResults',
            'summaries',
            'strongSubjects',
            'supportSubjects',
            'scoreMode'
        ));
    }

    private function feeAccountSummary(Student $student, $invoices, array $feeBreakdown, $payments): array
    {
        if ($invoices->isNotEmpty()) {
            $charges = (float) $invoices->sum('total_amount');
            $credits = (float) $invoices->sum('waiver_amount');

            return [
                'charges' => $charges,
                'credits' => $credits,
                'payments' => (float) $payments->sum('amount'),
                'balance' => max(0, (float) $invoices->sum('balance')),
            ];
        }

        return [
            'charges' => (float) ($feeBreakdown['total_billed'] ?? 0),
            'credits' => 0.0,
            'payments' => (float) ($feeBreakdown['total_paid'] ?? $payments->sum('amount')),
            'balance' => (float) ($feeBreakdown['balance'] ?? 0),
        ];
    }

    private function currentYearFeeSummary(int $schoolId, Student $student, $invoices, $payments): ?array
    {
        $year = AcademicYear::where('school_id', $schoolId)
            ->where('is_current', true)
            ->first();
        if (!$year) {
            $year = $invoices->sortByDesc('academic_year_id')->first()?->academicYear;
        }

        if (!$year) {
            return null;
        }

        $yearInvoices = $invoices->where('academic_year_id', $year->id)->values();
        if ($yearInvoices->isEmpty()) {
            return null;
        }

        $yearPayments = $payments->filter(fn ($payment) => (int) $payment->invoice?->academic_year_id === (int) $year->id)->values();

        return [
            'year' => $year,
            'charges' => (float) $yearInvoices->sum('total_amount'),
            'credits' => (float) $yearInvoices->sum('waiver_amount'),
            'payments' => (float) $yearPayments->sum('amount'),
            'balance' => max(0, (float) $yearInvoices->sum('balance')),
        ];
    }

    private function practiceTablesAvailable(): bool
    {
        foreach ([
            'learning_chapters',
            'learning_questions',
            'learning_attempts',
            'learning_chapter_progress',
            'portal_announcement_reads',
        ] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function resolveStudentForPortal($user): Student
    {
        $schoolId = $user->school_id ?? session('active_school_id');
        $student = Student::where('user_id', $user->id)
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->first();

        if (!$student && $schoolId && $user->email) {
            $admissionNumber = explode('@', $user->email)[0];
            $student = Student::where('school_id', $schoolId)
                ->where('admission_number', $admissionNumber)
                ->first();
        }

        abort_unless($student, 403, 'Your account is not linked to a student record.');

        return $student;
    }

    private function selectLinkedChild(Request $request, $children): ?Student
    {
        if ($children->isEmpty()) {
            return null;
        }

        if (!$request->filled('student_id')) {
            return $children->first();
        }

        $student = $children->firstWhere('id', (int) $request->query('student_id'));
        abort_unless($student, 403, 'You are not authorized to access this student.');

        return $student;
    }
}
