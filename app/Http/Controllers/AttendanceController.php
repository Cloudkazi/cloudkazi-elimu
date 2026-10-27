<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Teacher;
use App\Models\TeacherAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        // The BelongsToSchool TenantScope on SchoolClass automatically applies school_id
        $classes = SchoolClass::with('streams')->get();
        $selectedClassId = $request->input('class_id', $classes->first()?->id);
        $selectedClass = SchoolClass::with('streams')->find($selectedClassId);
        $selectedStreamId = $request->input('stream_id');

        $students = collect();
        $existingAttendance = collect();

        if ($selectedClassId) {
            $studentsQuery = Student::where('school_class_id', $selectedClassId)
                ->where('status', 'active')
                ->orderBy('first_name');

            if ($selectedStreamId) {
                $studentsQuery->where('stream_id', $selectedStreamId);
            }

            $students = $studentsQuery->get();

            // BelongsToSchool scope on StudentAttendance applies school_id automatically
            $existingAttendance = StudentAttendance::where('attendance_date', $date)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');
        }

        // How many students are enrolled in the selected class/stream?
        $enrolledCount = $students->count();
        // How many have an attendance record today (any status)?
        $recordedCount = $existingAttendance->count();
        $unmarkedCount = max(0, $enrolledCount - $recordedCount);

        $schoolId = auth()->user()?->school_id ?? session('active_school_id');

        // todayCounts: school-wide stats for the selected date
        $todayCounts = [
            'present'  => StudentAttendance::where('attendance_date', $date)->where('status', 'present')->count(),
            'absent'   => StudentAttendance::where('attendance_date', $date)->where('status', 'absent')->count(),
            'late'     => StudentAttendance::where('attendance_date', $date)->where('status', 'late')->count(),
            'excused'  => StudentAttendance::where('attendance_date', $date)->where('status', 'excused')->count(),
            'unmarked' => $unmarkedCount,
        ];

        return view('attendance.index', compact(
            'date',
            'classes',
            'selectedClass',
            'selectedClassId',
            'selectedStreamId',
            'students',
            'existingAttendance',
            'todayCounts'
        ));
    }

    public function store(Request $request)
    {
        $date        = $request->input('date', Carbon::today()->toDateString());
        $attendances = $request->input('attendance', []);

        // Determine school context from the authenticated user (BelongsToSchool source-of-truth)
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');

        $errors = [];

        foreach ($attendances as $studentId => $data) {
            // Load the student and verify school ownership
            $student = Student::withoutGlobalScopes()
                ->where('id', $studentId)
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->first();

            if (!$student) {
                // Student doesn't exist or belongs to a different school — skip silently (security)
                continue;
            }

            // Derive stream_id from the student's own stream assignment, never from browser POST
            $streamId = $student->stream_id;

            if (is_null($streamId)) {
                // Student has no stream assigned — record the error but skip saving
                $errors[] = "Attendance cannot be recorded for {$student->first_name} {$student->last_name}: "
                    . "this student has no stream assigned for the current academic year. "
                    . "Please assign the student to a stream first.";
                continue;
            }

            // Safety check: stream must belong to the same school
            $streamBelongsToSchool = \App\Models\Stream::where('id', $streamId)
                ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->exists();

            if (!$streamBelongsToSchool) {
                $errors[] = "Attendance skipped for {$student->first_name} {$student->last_name}: "
                    . "the student's stream does not belong to this school.";
                continue;
            }

            // updateOrCreate using the UNIQUE KEY (school_id, student_id, attendance_date)
            // school_id is enforced by BelongsToSchool on create; explicit here for the lookup key
            StudentAttendance::updateOrCreate(
                [
                    'school_id'       => $schoolId,
                    'student_id'      => $studentId,
                    'attendance_date' => $date,
                ],
                [
                    'stream_id'   => $streamId,         // always from student record, never browser
                    'status'      => $data['status'] ?? 'present',
                    'remarks'     => $data['remarks'] ?? null,
                    'recorded_by' => auth()->id(),
                ]
            );
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->with('warning', count($errors) . ' student(s) could not be marked — see errors above.');
        }

        return back()->with('success', 'Attendance recorded successfully.');
    }

    public function teachers(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $teachers = Teacher::with('user')->get();
        $attendances = TeacherAttendance::where('attendance_date', $date)->get()->keyBy('teacher_id');

        return view('attendance.teachers', compact('date', 'teachers', 'attendances'));
    }

    public function storeTeacher(Request $request)
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:present,absent,late,on_leave',
            'check_in_time' => 'nullable',
            'check_out_time' => 'nullable',
            'remarks' => 'nullable|string',
        ]);

        TeacherAttendance::updateOrCreate([
            'teacher_id' => $validated['teacher_id'],
            'attendance_date' => $validated['attendance_date'],
        ], $validated);

        return back()->with('success', 'Staff attendance updated.');
    }

    public function report(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date', Carbon::today()->toDateString());
        $classId   = $request->input('class_id');

        // Explicit school_id for defence-in-depth (TenantScope also applies via BelongsToSchool)
        $schoolId = auth()->user()?->school_id ?? session('active_school_id');

        $classes = SchoolClass::with('streams')->get();

        // Primary query — TenantScope applies school_id automatically
        $query = StudentAttendance::with(['student.schoolClass', 'stream'])
            ->whereBetween('attendance_date', [$startDate, $endDate]);

        if ($classId) {
            $query->whereHas('student', fn($q) => $q->where('school_class_id', $classId));
        }

        $records = $query->latest('attendance_date')->paginate(20);

        // Summary stats — explicit school_id for clarity and multi-school safety
        $baseQuery = fn() => StudentAttendance::whereBetween('attendance_date', [$startDate, $endDate])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId));

        $totalMarked  = $baseQuery()->count();
        $totalPresent = $baseQuery()->where('status', 'present')->count();
        $totalAbsent  = $baseQuery()->where('status', 'absent')->count();
        $totalLate    = $baseQuery()->where('status', 'late')->count();
        $attendanceRate = $totalMarked > 0 ? round(($totalPresent / $totalMarked) * 100, 1) : 100.0;

        return view('attendance.report', compact(
            'startDate',
            'endDate',
            'classes',
            'records',
            'totalMarked',
            'totalPresent',
            'totalAbsent',
            'totalLate',
            'attendanceRate'
        ));
    }
}
