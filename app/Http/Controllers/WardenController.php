<?php

namespace App\Http\Controllers;

use App\Models\Dormitory;
use App\Models\StudentHostel;
use App\Models\Student;
use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

class WardenController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        // Double-guard: only Hostel Warden may access this controller
        if (!$user || !$user->isHostelWarden()) {
            abort(403, 'Unauthorized. Only Hostel Wardens can access the Warden Portal.');
        }

        $schoolId = $user->school_id ?? session('active_school_id');

        // --- Dormitory statistics (scoped to this school via BelongsToSchool) ---
        $dormitories = Dormitory::with(['rooms', 'students.student'])->get();

        $totalDormitories = $dormitories->count();
        $totalCapacity    = $dormitories->sum('capacity');

        // Occupied beds = students currently allocated
        $occupiedBeds = StudentHostel::count();

        $availableBeds = max(0, $totalCapacity - $occupiedBeds);

        // Boarders: students whose fee_profile indicates boarding
        $totalBoarders = Student::whereIn('fee_profile', ['boarder', 'boarder_transport'])->count();

        // Students allocated to a hostel room/bed
        $allocatedStudents = StudentHostel::count();

        // Students who are boarders but not yet allocated a bed
        $boarderStudentIds = Student::whereIn('fee_profile', ['boarder', 'boarder_transport'])
            ->pluck('id');
        $allocatedStudentIds = StudentHostel::pluck('student_id');
        $unallocatedBoarders = $boarderStudentIds->diff($allocatedStudentIds)->count();

        // Recent hostel allocations (last 10)
        $recentAllocations = StudentHostel::with([
            'student.schoolClass',
            'student.stream',
            'dormitory',
            'room',
        ])
        ->latest()
        ->take(10)
        ->get();

        // Notices visible to warden (latest 5)
        $announcements = Announcement::latest()->take(5)->get();

        return view('warden.dashboard', compact(
            'dormitories',
            'totalDormitories',
            'totalCapacity',
            'occupiedBeds',
            'availableBeds',
            'totalBoarders',
            'allocatedStudents',
            'unallocatedBoarders',
            'recentAllocations',
            'announcements',
        ));
    }
}
