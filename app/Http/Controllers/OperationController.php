<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Asset;
use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookIssue;
use App\Models\DisciplineIncident;
use App\Models\Dormitory;
use App\Models\Employee;
use App\Models\HostelRoom;
use App\Models\LearningMaterial;
use App\Models\Payment;
use App\Models\PayrollRecord;
use App\Models\SchoolClass;
use App\Models\SmsLog;
use App\Models\Student;
use App\Models\StudentHostel;
use App\Models\StudentInvoice;
use App\Models\StudentTransport;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TransportRoute;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperationController extends Controller
{
    // --- 1. LIBRARY SUITE ---
    protected function authorizeLibraryAccess(): void
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isSchoolAdmin() && !$user->isLibrarian())) {
            abort(403, 'Unauthorized. Access is restricted to library and school administration personnel.');
        }
    }

    public function libraryDashboard()
    {
        $this->authorizeLibraryAccess();

        $schoolId = Auth::user()->school_id ?? session('active_school_id');

        $totalTitles = Book::where('school_id', $schoolId)->count();
        $totalCopies = (int) Book::where('school_id', $schoolId)->sum('total_copies');
        $availableCopies = (int) Book::where('school_id', $schoolId)->sum('available_copies');
        $issuedCount = BookIssue::where('school_id', $schoolId)->where('status', 'issued')->count();
        $overdueCount = BookIssue::where('school_id', $schoolId)
            ->where('status', 'issued')
            ->where('due_date', '<', Carbon::today()->toDateString())
            ->count();
        $categoriesCount = BookCategory::where('school_id', $schoolId)->count();

        $recentIssues = BookIssue::where('school_id', $schoolId)
            ->with(['book', 'student'])
            ->latest()
            ->take(8)
            ->get();

        $categories = BookCategory::where('school_id', $schoolId)
            ->withCount('books')
            ->get();

        $books = Book::where('school_id', $schoolId)->orderBy('title')->get();
        $students = Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('first_name')->get();

        return view('operations.library-dashboard', compact(
            'totalTitles',
            'totalCopies',
            'availableCopies',
            'issuedCount',
            'overdueCount',
            'categoriesCount',
            'recentIssues',
            'categories',
            'books',
            'students'
        ));
    }

    public function libraryBooks(Request $request)
    {
        $this->authorizeLibraryAccess();

        $schoolId = Auth::user()->school_id ?? session('active_school_id');

        $query = Book::where('school_id', $schoolId)->with('category');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $books = $query->paginate(15);
        $categories = BookCategory::where('school_id', $schoolId)->withCount('books')->get();
        $students = Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('first_name')->get();

        return view('operations.library', compact('books', 'categories', 'students'));
    }

    public function libraryCategories()
    {
        $this->authorizeLibraryAccess();

        $schoolId = Auth::user()->school_id ?? session('active_school_id');
        $categories = BookCategory::where('school_id', $schoolId)
            ->withCount('books')
            ->get();

        return view('operations.library-categories', compact('categories'));
    }

    public function libraryIssueForm()
    {
        $this->authorizeLibraryAccess();

        $schoolId = Auth::user()->school_id ?? session('active_school_id');
        $books = Book::where('school_id', $schoolId)->where('available_copies', '>', 0)->get();
        $students = Student::where('school_id', $schoolId)->where('status', 'active')->orderBy('first_name')->get();
        $recentIssues = BookIssue::where('school_id', $schoolId)
            ->with(['book', 'student'])
            ->latest()
            ->paginate(15);

        return view('operations.library-issue', compact('books', 'students', 'recentIssues'));
    }

    public function libraryReturns(Request $request)
    {
        $this->authorizeLibraryAccess();

        $schoolId = Auth::user()->school_id ?? session('active_school_id');

        $query = BookIssue::where('school_id', $schoolId)
            ->where('status', 'issued')
            ->with(['book', 'student']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('book', fn ($b) => $b->where('title', 'like', "%{$search}%"))
                  ->orWhereHas('student', fn ($s) => $s->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('admission_number', 'like', "%{$search}%"));
            });
        }

        if ($request->boolean('overdue_only')) {
            $query->where('due_date', '<', Carbon::today()->toDateString());
        }

        $activeIssues = $query->orderBy('due_date', 'asc')->paginate(15);

        return view('operations.library-returns', compact('activeIssues'));
    }

    public function libraryHistory(Request $request)
    {
        $this->authorizeLibraryAccess();

        $schoolId = Auth::user()->school_id ?? session('active_school_id');

        $query = BookIssue::where('school_id', $schoolId)
            ->with(['book', 'student']);

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'overdue') {
                $query->where('status', 'issued')->where('due_date', '<', Carbon::today()->toDateString());
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('book', fn ($b) => $b->where('title', 'like', "%{$search}%"))
                  ->orWhereHas('student', fn ($s) => $s->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('admission_number', 'like', "%{$search}%"));
            });
        }

        $history = $query->latest('issue_date')->paginate(20);

        return view('operations.library-history', compact('history'));
    }

    public function libraryReports()
    {
        $this->authorizeLibraryAccess();

        $schoolId = Auth::user()->school_id ?? session('active_school_id');

        $totalBooks = Book::where('school_id', $schoolId)->count();
        $totalCopies = (int) Book::where('school_id', $schoolId)->sum('total_copies');
        $availableCopies = (int) Book::where('school_id', $schoolId)->sum('available_copies');
        $totalIssuedAllTime = BookIssue::where('school_id', $schoolId)->count();
        $currentIssued = BookIssue::where('school_id', $schoolId)->where('status', 'issued')->count();
        $currentOverdue = BookIssue::where('school_id', $schoolId)
            ->where('status', 'issued')
            ->where('due_date', '<', Carbon::today()->toDateString())
            ->count();
        $totalReturned = BookIssue::where('school_id', $schoolId)->where('status', 'returned')->count();

        // Top borrowed books
        $topBooks = Book::where('school_id', $schoolId)
            ->withCount('issues')
            ->orderByDesc('issues_count')
            ->take(5)
            ->get();

        // Overdue borrowers
        $overdueIssues = BookIssue::where('school_id', $schoolId)
            ->where('status', 'issued')
            ->where('due_date', '<', Carbon::today()->toDateString())
            ->with(['book', 'student'])
            ->get();

        return view('operations.library-reports', compact(
            'totalBooks',
            'totalCopies',
            'availableCopies',
            'totalIssuedAllTime',
            'currentIssued',
            'currentOverdue',
            'totalReturned',
            'topBooks',
            'overdueIssues'
        ));
    }

    public function library()
    {
        return $this->libraryDashboard();
    }

    public function storeBookCategory(Request $request)
    {
        $this->authorizeLibraryAccess();

        $schoolId = auth()->user()->school_id ?? session('active_school_id');

        if (!$schoolId) {
            return back()->with('error', 'No active school context found.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('book_categories', 'name')
                    ->where(fn ($query) => $query->where('school_id', $schoolId)),
            ],
            'description' => 'nullable|string|max:500',
        ]);

        $validated['school_id'] = $schoolId;

        BookCategory::create($validated);

        return back()->with('success', "Library category '{$validated['name']}' added successfully.");
    }

    public function storeBook(Request $request)
    {
        $this->authorizeLibraryAccess();

        $schoolId = auth()->user()->school_id ?? session('active_school_id');

        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'author' => 'required|string|max:150',
            'isbn' => 'nullable|string|max:50',
            'category_id' => 'required|exists:book_categories,id',
            'total_copies' => 'required|integer|min:1',
            'shelf_location' => 'nullable|string',
        ]);

        $validated['school_id'] = $schoolId;
        $validated['available_copies'] = $validated['total_copies'];
        Book::create($validated);
        return back()->with('success', "Book '{$validated['title']}' cataloged.");
    }

    public function issueBook(Request $request)
    {
        $this->authorizeLibraryAccess();

        $schoolId = auth()->user()->school_id ?? session('active_school_id');

        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'student_id' => 'required|exists:students,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
        ]);

        $book = Book::where('school_id', $schoolId)->findOrFail($validated['book_id']);
        if ($book->available_copies < 1) {
            return back()->with('error', 'All copies of this book are currently issued.');
        }

        $student = Student::where('school_id', $schoolId)->findOrFail($validated['student_id']);

        BookIssue::create([
            'school_id' => $schoolId,
            'book_id' => $book->id,
            'student_id' => $student->id,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'status' => 'issued',
        ]);

        $book->decrement('available_copies');

        return back()->with('success', "Book '{$book->title}' issued to {$student->full_name}.");
    }

    public function returnBook(int $id)
    {
        $this->authorizeLibraryAccess();

        $schoolId = auth()->user()->school_id ?? session('active_school_id');
        $issue = BookIssue::where('school_id', $schoolId)->findOrFail($id);

        if ($issue->status === 'returned') {
            return back()->with('info', 'This book has already been marked as returned.');
        }

        $issue->update([
            'return_date' => Carbon::today()->toDateString(),
            'status' => 'returned',
        ]);

        $issue->book->increment('available_copies');
        return back()->with('success', "Return of '{$issue->book->title}' recorded.");
    }

    // --- 2. TRANSPORT ---
    public function transport()
    {
        $routes = TransportRoute::withCount('students')->get();
        $students = Student::where('status', 'active')->get();
        $assignedStudents = StudentTransport::with(['student.schoolClass', 'route'])->latest()->paginate(15);

        return view('operations.transport', compact('routes', 'students', 'assignedStudents'));
    }

    public function storeTransportRoute(Request $request)
    {
        $validated = $request->validate([
            'route_name' => 'required|string|max:150',
            'vehicle_registration' => 'required|string|max:50',
            'driver_name' => 'required|string|max:100',
            'driver_phone' => 'required|string|max:20',
            'termly_fare' => 'required|numeric|min:0',
        ]);

        TransportRoute::create($validated);
        return back()->with('success', "Transport route '{$validated['route_name']}' created.");
    }

    public function assignTransport(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'transport_route_id' => 'required|exists:transport_routes,id',
            'pickup_point' => 'required|string|max:100',
        ]);

        StudentTransport::updateOrCreate(
            ['student_id' => $validated['student_id']],
            $validated + ['is_active' => true]
        );

        // Synchronize fee profile
        $student = Student::find($validated['student_id']);
        if ($student) {
            $newProfile = in_array($student->fee_profile, ['boarder', 'boarder_transport']) ? 'boarder_transport' : 'transport';
            $student->update(['fee_profile' => $newProfile]);
        }

        return back()->with('success', 'Student allocated to transport route.');
    }

    // --- 3. HOSTEL / BOARDING ---
    public function hostel()
    {
        $dormitories = Dormitory::with(['rooms', 'students.student'])->get();
        $students = Student::where('status', 'active')->get();
        $hostelAllocations = StudentHostel::with(['student.schoolClass', 'dormitory', 'room'])->latest()->paginate(15);

        return view('operations.hostel', compact('dormitories', 'students', 'hostelAllocations'));
    }

    public function storeDormitory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'gender' => 'required|in:Male,Female',
            'capacity' => 'required|integer|min:1',
            'warden_name' => 'nullable|string',
            'warden_phone' => 'nullable|string',
        ]);

        Dormitory::create($validated);
        return back()->with('success', "Dormitory '{$validated['name']}' added.");
    }

    public function assignHostel(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'dormitory_id' => 'required|exists:dormitories,id',
            'bed_number' => 'nullable|string',
        ]);

        StudentHostel::updateOrCreate(
            ['student_id' => $validated['student_id']],
            $validated
        );

        // Synchronize fee profile
        $student = Student::find($validated['student_id']);
        if ($student) {
            $newProfile = in_array($student->fee_profile, ['transport', 'boarder_transport']) ? 'boarder_transport' : 'boarder';
            $student->update(['fee_profile' => $newProfile]);
        }

        return back()->with('success', 'Student boarding allocation saved.');
    }

    // --- 4. COMMUNICATION & BULK SMS ---
    public function communication()
    {
        $announcements = Announcement::with('creator')->latest()->paginate(10);
        $smsLogs = SmsLog::latest()->paginate(15);
        $students = Student::with('guardians')->get();

        return view('operations.communication', compact('announcements', 'smsLogs', 'students'));
    }

    public function storeAnnouncement(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'target_audience' => 'required|in:All,Teachers,Students,Parents',
            'published_at' => 'required|date',
        ]);

        Announcement::create($validated + ['created_by' => Auth::id()]);
        return back()->with('success', 'Announcement broadcasted successfully.');
    }

    public function sendSms(Request $request)
    {
        $validated = $request->validate([
            'recipient_phone' => 'required|string',
            'recipient_name' => 'nullable|string',
            'message' => 'required|string|max:480',
            'message_type' => 'required|string',
        ]);

        SmsLog::create($validated + ['status' => 'sent', 'cost' => 1.00]);
        return back()->with('success', "SMS dispatched to {$validated['recipient_phone']}.");
    }

    // --- 5. DISCIPLINE MANAGEMENT ---
    public function discipline()
    {
        $incidents = DisciplineIncident::with(['student.schoolClass', 'reporter'])->latest()->paginate(15);
        $students = Student::where('status', 'active')->get();

        return view('operations.discipline', compact('incidents', 'students'));
    }

    public function storeDiscipline(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'incident_date' => 'required|date',
            'title' => 'required|string|max:150',
            'description' => 'required|string',
            'severity' => 'required|in:Low,Medium,High,Critical',
            'action_taken' => 'nullable|string',
            'parent_notified' => 'boolean',
            'status' => 'required|in:open,in_review,resolved',
        ]);

        DisciplineIncident::create($validated + ['reported_by' => Auth::id()]);
        return back()->with('success', 'Discipline record logged.');
    }

    // --- 6. INVENTORY & ASSETS ---
    public function inventory()
    {
        $assets = Asset::latest()->paginate(15);
        $totalCost = Asset::sum('purchase_cost');
        $totalCount = Asset::count();

        return view('operations.inventory', compact('assets', 'totalCost', 'totalCount'));
    }

    public function storeAsset(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'asset_tag' => 'required|string|max:50',
            'category' => 'required|in:Furniture,IT & Computers,Lab Equipment,Sports,Musical,Vehicles',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'required|numeric|min:0',
            'condition' => 'required|in:New,Good,Fair,Needs Repair,Condemned',
            'location' => 'nullable|string',
        ]);

        Asset::create($validated);
        return back()->with('success', "Asset '{$validated['name']}' registered.");
    }

    // --- 7. HR & PAYROLL ---
    public function hr()
    {
        $employees = Employee::with('payrollRecords')->paginate(15);
        $payrolls = PayrollRecord::with('employee')->latest()->take(10)->get();

        return view('operations.hr', compact('employees', 'payrolls'));
    }

    public function storeEmployee(Request $request)
    {
        $validated = $request->validate([
            'employee_no' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'designation' => 'required|string|max:100',
            'department' => 'nullable|string',
            'hire_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
        ]);

        Employee::create($validated + ['status' => 'active']);
        return back()->with('success', "Employee {$validated['first_name']} {$validated['last_name']} added.");
    }

    // --- 8. LEARNING MATERIALS ---
    public function materials()
    {
        $materials = LearningMaterial::with(['schoolClass', 'subject', 'teacher.user'])->latest()->paginate(15);
        $classes = SchoolClass::all();
        $subjects = Subject::all();

        return view('operations.materials', compact('materials', 'classes', 'subjects'));
    }

    public function storeMaterial(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'school_class_id' => 'required|exists:school_classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'type' => 'required|in:notes,assignment,past_paper,revision_material',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
        ]);

        $teacher = Auth::user()->teacher;
        LearningMaterial::create($validated + ['teacher_id' => $teacher ? $teacher->id : null]);

        return back()->with('success', "Learning material '{$validated['title']}' published.");
    }

    // --- 9. REPORTS & ANALYTICS ---
    public function reports()
    {
        $totalStudents = Student::count();
        $maleStudents = Student::where('gender', 'Male')->count();
        $femaleStudents = Student::where('gender', 'Female')->count();

        $cbcStudents = Student::where('curriculum', 'CBC')->count();
        $eightFourFourStudents = Student::where('curriculum', '8-4-4')->count();

        $classEnrollment = SchoolClass::withCount('students')->get();

        $totalInvoiced = StudentInvoice::sum('total_amount');
        $totalCollected = Payment::sum('amount');
        $totalBalance = StudentInvoice::sum('balance');

        return view('operations.reports', compact(
            'totalStudents',
            'maleStudents',
            'femaleStudents',
            'cbcStudents',
            'eightFourFourStudents',
            'classEnrollment',
            'totalInvoiced',
            'totalCollected',
            'totalBalance'
        ));
    }

    public function libraryIssue()
    {
        $books = Book::all();
        $students = Student::where('status', 'active')->get();
        $recentIssues = BookIssue::with(['book', 'student'])->latest()->paginate(15);
        return view('operations.library-issue', compact('books', 'students', 'recentIssues'));
    }

    public function vehicles()
    {
        $routes = TransportRoute::withCount('students')->get();
        return view('operations.vehicles', compact('routes'));
    }

    public function leave()
    {
        $employees = Employee::where('status', 'active')->get();
        return view('operations.leave', compact('employees'));
    }

    public function payroll()
    {
        $employees = Employee::with('payrollRecords')->paginate(15);
        $payrolls = PayrollRecord::with('employee')->latest()->take(10)->get();
        return view('operations.payroll', compact('employees', 'payrolls'));
    }
}
