<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\StudentEnrollmentService;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['schoolClass', 'stream', 'guardians']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('admission_number', 'like', "%{$search}%")
                    ->orWhere('nemis_upi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('class_id')) {
            $query->where('school_class_id', $request->input('class_id'));
        }

        if ($request->filled('curriculum')) {
            $query->where('curriculum', $request->input('curriculum'));
        }

        $students = $query->latest()->paginate(15);
        $classes = SchoolClass::with('streams')->get();

        $totalStudents = Student::count();
        $femaleCount = Student::where('gender', 'Female')->count();
        $maleCount = Student::where('gender', 'Male')->count();
        $defaulters = Student::whereHas('invoices', function ($q) {
            $q->where('balance', '>', 0);
        })->count();

        return view('students.index', compact('students', 'classes', 'totalStudents', 'femaleCount', 'maleCount', 'defaulters'));
    }

    public function create()
    {
        $schoolId = auth()->user()->school_id ?? session('active_school_id');
        $classes = SchoolClass::where('school_id', $schoolId)->with('streams')->get();
        $streams = Stream::where('school_id', $schoolId)->get();
        $feeStructures = \App\Models\FeeStructure::where('school_id', $schoolId)->get();
        $student = null;

        // Suggest next admission number for this school
        $year = date('Y');
        $lastStudent = Student::where('school_id', $schoolId)->latest('id')->first();
        $nextNum = $lastStudent ? ($lastStudent->id + 1) : 1;
        $suggestedAdmission = "ADM-{$year}-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        $nextAdmNo = $suggestedAdmission;

        return view('students.create', compact('classes', 'streams', 'feeStructures', 'suggestedAdmission', 'nextAdmNo', 'student'));
    }

    public function store(Request $request)
    {
        // Normalize fields from form aliases
        if ($request->filled('middle_name') && !$request->filled('other_names')) {
            $request->merge(['other_names' => $request->middle_name]);
        }

        if ($request->filled('curriculum_type') && !$request->filled('curriculum')) {
            $request->merge(['curriculum' => $request->curriculum_type]);
        }

        $schoolId = auth()->user()->school_id ?? session('active_school_id');

        // Enforce student capacity limit
        if ($schoolId && !\App\Services\PopulationAllocationService::canCreateStudent((int) $schoolId)) {
            $limit = \App\Services\PopulationAllocationService::getStudentLimit((int) $schoolId);
            $msg = "Student Limit Reached: Your current subscription allows up to {$limit} students. Please contact CloudKazi to increase your limit.";
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'code'    => 'LIMIT_REACHED',
                ], 422);
            }
            return back()->withInput()->withErrors(['limit' => $msg]);
        }

        // Auto-generate admission number if empty
        if (empty($request->admission_number)) {
            $year = date('Y');
            $lastStudent = Student::where('school_id', $schoolId)->latest('id')->first();
            $nextNum = $lastStudent ? ($lastStudent->id + 1) : 1;
            $request->merge([
                'admission_number' => "ADM-{$year}-" . str_pad($nextNum, 4, '0', STR_PAD_LEFT)
            ]);
        }

        $validated = $request->validate([
            'admission_number' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'other_names' => 'nullable|string|max:100',
            'gender' => 'required|in:Male,Female,Other',
            'dob' => 'required|date',
            'admission_date' => 'required|date',
            'school_class_id' => 'required|exists:school_classes,id',
            'stream_id' => 'nullable|exists:streams,id',
            'curriculum' => 'required|in:CBC,8-4-4',
            'pathway' => 'nullable|string',
            'nemis_upi' => 'nullable|string',
            'birth_certificate_no' => 'nullable|string',
            'assessment_number' => 'nullable|string',
            'medical_conditions' => 'nullable|string',
            'blood_group' => 'nullable|string',
            'fee_profile' => 'nullable|in:regular,boarder,transport,boarder_transport',
            'status' => 'nullable|string',
            'fee_structure_id' => 'nullable|integer',
            // Guardian fields
            'guardian_name' => 'required|string|max:150',
            'guardian_phone' => 'required|string|max:20',
            'guardian_email' => 'required|email',
            'guardian_relationship' => 'required|string',
            'guardian_occupation' => 'nullable|string',
        ]);

        // Delegate the enrollment logic to the service for atomic student, user, and parent creation
        StudentEnrollmentService::enroll($validated);

        return redirect()->route('students.index')
            ->with('success', "Student {$validated['first_name']} {$validated['last_name']} admitted successfully.");
    }

    protected function authorizeStudentAccess(Student $student): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }
        if ($user->isSuperAdmin()) {
            return;
        }
        $schoolId = $user->school_id ?? session('active_school_id');
        if ($student->school_id && $schoolId && $student->school_id != $schoolId) {
            abort(404, 'Student not found in your school records.');
        }
    }

    public function show(Student $student)
    {
        $this->authorizeStudentAccess($student);

        $student->load([
            'schoolClass',
            'stream',
            'guardians',
            'invoices.payments',
            'hostelAllocation.dormitory',
            'hostelAllocation.room',
            'transportAllocation.route',
            'attendances' => fn($q) => $q->latest('attendance_date')->take(10),
            'marks844.subject',
            'cbcAssessments.subject',
            'reportSummaries.examination',
            'disciplineIncidents',
        ]);

        $feeBreakdown = $student->calculateFeeBreakdown();
        $totalBilled = $feeBreakdown['total_billed'];
        $totalPaid = $feeBreakdown['total_paid'];
        $balance = $feeBreakdown['balance'];

        return view('students.show', compact('student', 'feeBreakdown', 'totalBilled', 'totalPaid', 'balance'));
    }

    public function edit(Student $student)
    {
        $this->authorizeStudentAccess($student);

        $student->load('guardians');
        $adminSchoolId = auth()->user()->school_id;
        $classes = SchoolClass::where('school_id', $adminSchoolId)->with('streams')->get();
        $streams = Stream::where('school_class_id', $student->school_class_id)->get();

        return view('students.edit', compact('student', 'classes', 'streams'));
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeStudentAccess($student);
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'other_names' => 'nullable|string|max:100',
            'school_class_id' => 'required|exists:school_classes,id',
            'stream_id' => 'nullable|exists:streams,id',
            'curriculum' => 'required|in:CBC,8-4-4',
            'pathway' => 'nullable|string',
            'fee_profile' => 'required|in:regular,boarder,transport,boarder_transport',
            'status' => 'required|in:active,transferred,suspended,alumni',
            'medical_conditions' => 'nullable|string',
            'blood_group' => 'nullable|string',
        ]);

        $student->update($validated);

        return redirect()->route('students.show', $student->id)->with('success', 'Student profile updated successfully.');
    }

    public function search(Request $request)
    {
        $q = $request->input('q', '');
        $students = Student::with(['schoolClass', 'invoices'])
            ->where(function ($query) use ($q) {
                $query->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('admission_number', 'like', "%{$q}%");
            })
            ->take(12)
            ->get();

        return response()->json($students);
    }

    public function promotions()
    {
        $classes = SchoolClass::with('streams')->get();
        return view('students.promotions', compact('classes'));
    }

    public function processPromotions(Request $request)
    {
        $validated = $request->validate([
            'from_class_id' => 'required|exists:school_classes,id',
            'to_class_id' => 'required|exists:school_classes,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        Student::whereIn('id', $validated['student_ids'])
            ->update(['school_class_id' => $validated['to_class_id']]);

        return back()->with('success', count($validated['student_ids']) . ' students promoted successfully.');
    }

    public function documents()
    {
        $students = Student::with('schoolClass')->latest()->paginate(15);
        return view('students.documents', compact('students'));
    }

    /**
     * Completely delete a student along with their portal user account,
     * guardian pivot records, and the parent portal user when the parent
     * is no longer attached to any other student.
     */
    public function destroy(Student $student)
    {
        $this->authorizeStudentAccess($student);

        DB::transaction(function () use ($student) {
            $guardianIds = $student->guardians()->pluck('guardians.id')->toArray();
            $student->guardians()->detach();

            if ($student->user) {
                $student->user->delete();
            }

            $parentUserId = $student->parent_user_id;
            if ($parentUserId) {
                $remainingStudents = Student::where('parent_user_id', $parentUserId)
                    ->whereKeyNot($student->getKey())
                    ->exists();

                if (! $remainingStudents) {
                    $relatedGuardians = Guardian::where('user_id', $parentUserId)->get();
                    foreach ($relatedGuardians as $guardian) {
                        if ($guardian->user) {
                            $guardian->user->delete();
                        }
                        $guardian->students()->detach();
                        $guardian->delete();
                    }

                    $parentUser = User::find($parentUserId);
                    if ($parentUser) {
                        $parentUser->delete();
                    }
                }
            }

            $student->delete();
        });

        return redirect()->route('students.index')
            ->with('success', 'Student and associated records deleted successfully.');
    }
}
