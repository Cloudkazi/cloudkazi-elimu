<?php

namespace App\Services;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Subject;
use App\Models\SubjectRule;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherSubjectAllocation;
use App\Models\Timetable;
use App\Models\TimetableAllocation;
use App\Models\TimetableGenerationSetting;
use App\Models\TimetableSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TimetableEngineService
{
    /**
     * Generate standard weekly timetable slots for a school
     */
    public function generateStandardSlots(Timetable $timetable): void
    {
        $schoolId = $timetable->school_id;
        $settings = $this->getGenerationSettings($timetable);

        $days = $settings->school_days ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $startTimeStr = $settings->school_start_time ?? '08:00:00';
        $endTimeStr = $settings->school_end_time ?? '16:30:00';
        $durationMinutes = (int) ($settings->lesson_duration_minutes ?? 45);
        $activityBlocks = $settings->activity_blocks ?? [
            ['name' => 'Tea Break', 'type' => 'break', 'start_time' => '10:15:00', 'duration_minutes' => 30],
            ['name' => 'Lunch Break', 'type' => 'break', 'start_time' => '13:00:00', 'duration_minutes' => 60],
            ['name' => 'Games & Clubs', 'type' => 'break', 'start_time' => '15:30:00', 'duration_minutes' => 60],
        ];

        // Sort activity blocks by start_time
        usort($activityBlocks, function ($a, $b) {
            return strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
        });

        foreach ($days as $day) {
            $periodNumber = 1;
            $current = Carbon::parse($startTimeStr);
            $schoolEnd = Carbon::parse($endTimeStr);
            $processedActivities = [];

            while ($current->lt($schoolEnd)) {
                $currentTimeStr = $current->format('H:i:s');

                // Check if an activity / break starts at or before current time
                $matchingActivity = null;
                $matchingIdx = null;
                foreach ($activityBlocks as $idx => $act) {
                    if (in_array($idx, $processedActivities)) {
                        continue;
                    }
                    $actStart = Carbon::parse($act['start_time']);
                    if ($current->gte($actStart)) {
                        $matchingActivity = $act;
                        $matchingIdx = $idx;
                        break;
                    }
                }

                if ($matchingActivity) {
                    $processedActivities[] = $matchingIdx;
                    $actDuration = (int) ($matchingActivity['duration_minutes'] ?? 30);
                    $actEnd = $current->copy()->addMinutes($actDuration);

                    TimetableSlot::firstOrCreate([
                        'school_id' => $schoolId,
                        'timetable_id' => $timetable->id,
                        'day_of_week' => $day,
                        'period_number' => $periodNumber,
                    ], [
                        'start_time' => $current->format('H:i:s'),
                        'end_time' => $actEnd->format('H:i:s'),
                        'duration_minutes' => $actDuration,
                        'is_break' => true,
                        'is_teaching_period' => false,
                        'break_label' => $matchingActivity['name'] ?? 'Break',
                        'type' => 'break',
                    ]);

                    $periodNumber++;
                    $current = $actEnd;
                    continue;
                }

                $slotEnd = $current->copy()->addMinutes($durationMinutes);
                if ($slotEnd->gt($schoolEnd)) {
                    break;
                }

                TimetableSlot::firstOrCreate([
                    'school_id' => $schoolId,
                    'timetable_id' => $timetable->id,
                    'day_of_week' => $day,
                    'period_number' => $periodNumber,
                ], [
                    'start_time' => $current->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'duration_minutes' => $durationMinutes,
                    'is_break' => false,
                    'is_teaching_period' => true,
                    'break_label' => null,
                    'type' => 'lesson',
                ]);

                $periodNumber++;
                $current = $slotEnd;
            }
        }
    }

    /**
     * Retrieve active non-break teaching slots for the timetable
     */
    protected function getTeachingSlots(Timetable $timetable): \Illuminate\Support\Collection
    {
        $settings = $this->getGenerationSettings($timetable);
        $days = $settings->school_days ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $slots = TimetableSlot::where('timetable_id', $timetable->id)
            ->where('is_break', false)
            ->when(!empty($days), fn ($query) => $query->whereIn('day_of_week', $days))
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->get();

        if ($slots->isEmpty()) {
            $this->generateStandardSlots($timetable);
            $slots = TimetableSlot::where('timetable_id', $timetable->id)
                ->where('is_break', false)
                ->when(!empty($days), fn ($query) => $query->whereIn('day_of_week', $days))
                ->orderBy('day_of_week')
                ->orderBy('period_number')
                ->get();
        }

        return $slots;
    }

    /**
     * Build teaching requirements grouped by actual stream ID.
     * Expands "All Streams" allocations to all actual streams belonging to the class.
     * Validates that normal lesson allocations have a real, valid stream_id.
     */
    public function buildTeachingRequirements(Timetable $timetable): array
    {
        $schoolId = $timetable->school_id;
        $requirements = [];

        $allocations = TeacherSubjectAllocation::where('school_id', $schoolId)
            ->with(['subject', 'teacher.user', 'schoolClass.streams', 'stream'])
            ->get();

        foreach ($allocations as $allocation) {
            if (empty($allocation->teacher_id) || empty($allocation->subject_id) || empty($allocation->school_class_id)) {
                continue;
            }

            $requiredLessons = (int) ($allocation->lessons_per_week ?? 0);
            if ($requiredLessons <= 0) {
                continue;
            }

            $teacher = $allocation->teacher;
            $subject = $allocation->subject;
            $schoolClass = $allocation->schoolClass;

            if ($allocation->stream_id) {
                // Specific stream allocation
                $stream = $allocation->stream;
                if (!$stream || $stream->school_id != $schoolId || $stream->school_class_id != $allocation->school_class_id) {
                    Log::warning("Skipping allocation #{$allocation->id}: Stream #{$allocation->stream_id} does not belong to class #{$allocation->school_class_id} in school #{$schoolId}");
                    continue;
                }

                $requirements[$stream->id][] = [
                    'stream_id' => $stream->id,
                    'stream_name' => $stream->name,
                    'class_id' => $allocation->school_class_id,
                    'class_name' => $schoolClass?->name ?? 'Class',
                    'subject_id' => $allocation->subject_id,
                    'subject_name' => $subject?->name ?? 'Unknown subject',
                    'subject_code' => $subject?->code ?? '',
                    'teacher_id' => $teacher?->id,
                    'teacher_name' => $teacher?->user?->name ?? ($teacher?->name ?? 'Unassigned'),
                    'required_lessons' => $requiredLessons,
                    'room_requirement' => $subject?->code ?? null,
                ];
            } else {
                // "All Streams" allocation: expand to every real stream belonging to this class
                $classStreams = Stream::where('school_class_id', $allocation->school_class_id)
                    ->where('school_id', $schoolId)
                    ->get();

                if ($classStreams->isEmpty()) {
                    Log::warning("Allocation #{$allocation->id} (Class {$schoolClass?->name}) has no streams defined. Normal lesson timetable allocations require a valid stream.");
                    continue;
                }

                foreach ($classStreams as $stream) {
                    $requirements[$stream->id][] = [
                        'stream_id' => $stream->id,
                        'stream_name' => $stream->name,
                        'class_id' => $allocation->school_class_id,
                        'class_name' => $schoolClass?->name ?? 'Class',
                        'subject_id' => $allocation->subject_id,
                        'subject_name' => $subject?->name ?? 'Unknown subject',
                        'subject_code' => $subject?->code ?? '',
                        'teacher_id' => $teacher?->id,
                        'teacher_name' => $teacher?->user?->name ?? ($teacher?->name ?? 'Unassigned'),
                        'required_lessons' => $requiredLessons,
                        'room_requirement' => $subject?->code ?? null,
                    ];
                }
            }
        }

        return $requirements;
    }

    public function getSchoolTeachingAllocationSummary(int $schoolId): array
    {
        $allocations = DB::table('teacher_subject_allocations')
            ->where('school_id', $schoolId)
            ->get();

        $validAllocations = $allocations->filter(function ($allocation) {
            return !empty($allocation->teacher_id)
                && !empty($allocation->subject_id)
                && !empty($allocation->school_class_id)
                && (int) ($allocation->lessons_per_week ?? 0) > 0;
        });

        $incompleteAllocations = $allocations->reject(fn ($allocation) => $validAllocations->contains('id', $allocation->id));

        return [
            'school_id' => $schoolId,
            'teacher_subject_allocations' => $validAllocations->count(),
            'required_weekly_lessons' => (int) $validAllocations->sum('lessons_per_week'),
            'teachers' => $validAllocations->pluck('teacher_id')->filter()->unique()->count(),
            'subjects' => $validAllocations->pluck('subject_id')->filter()->unique()->count(),
            'classes' => $validAllocations->pluck('school_class_id')->filter()->unique()->count(),
            'total_allocations' => $allocations->count(),
            'incomplete_allocations' => $incompleteAllocations->count(),
            'issues' => $incompleteAllocations->map(function ($allocation) {
                $parts = [];
                if (empty($allocation->teacher_id)) {
                    $parts[] = 'teacher missing';
                }
                if (empty($allocation->subject_id)) {
                    $parts[] = 'subject missing';
                }
                if (empty($allocation->school_class_id)) {
                    $parts[] = 'class missing';
                }
                if ((int) ($allocation->lessons_per_week ?? 0) <= 0) {
                    $parts[] = 'lessons_per_week is zero or missing';
                }

                return 'TeacherSubjectAllocation #' . ($allocation->id ?? 'unknown') . ' is incomplete: ' . implode(', ', $parts) . '.';
            })->values()->all(),
        ];
    }

    public function getReadinessReport(Timetable $timetable): array
    {
        $settings = $this->getGenerationSettings($timetable);
        $assignmentSummary = $this->getSchoolTeachingAllocationSummary($timetable->school_id);
        $requirements = $this->buildTeachingRequirements($timetable);
        $issues = [];
        $missingAssignments = 0;
        $requiredLessons = 0;

        if (($assignmentSummary['teacher_subject_allocations'] ?? 0) <= 0) {
            $issues[] = 'No teacher-subject allocations were found for the current school.';
        }

        foreach ($requirements as $streamId => $streamRequirements) {
            foreach ($streamRequirements as $requirement) {
                $requiredLessons += (int) ($requirement['required_lessons'] ?? 0);
                if (empty($requirement['teacher_id'])) {
                    $issues[] = $requirement['class_name'] . ' (' . $requirement['stream_name'] . ') / ' . $requirement['subject_name'] . ' — No teacher assigned';
                    $missingAssignments++;
                }
            }
        }

        $availableSlots = $this->getTeachingSlots($timetable)->count();

        return [
            'required_lessons' => $requiredLessons,
            'available_teaching_slots' => $availableSlots,
            'missing_teacher_assignments' => $missingAssignments,
            'issues' => $issues,
            'strict_validation' => $settings->strict_validation ?? true,
            'requirements' => $requirements,
            'teacher_subject_allocations' => $assignmentSummary['teacher_subject_allocations'],
            'teachers' => $assignmentSummary['teachers'],
            'subjects' => $assignmentSummary['subjects'],
            'classes' => $assignmentSummary['classes'],
            'required_weekly_lessons' => $assignmentSummary['required_weekly_lessons'],
            'allocation_summary' => $assignmentSummary,
        ];
    }

    /**
     * Automatic Timetable Generation Algorithm
     * Assigns subjects and teachers to stream slots while strictly avoiding conflicts
     */
    public function autoGenerateAllocations(Timetable $timetable): array
    {
        $schoolId = $timetable->school_id;
        $settings = $this->getGenerationSettings($timetable);

        $readiness = $this->getReadinessReport($timetable);
        if (($settings->strict_validation ?? true) && !empty($readiness['issues'])) {
            throw new \RuntimeException('Unassigned teaching requirements were found: ' . implode('; ', $readiness['issues']));
        }

        $teachingSlots = $this->getTeachingSlots($timetable)
            ->filter(fn ($slot) => !$slot->is_break && ($slot->is_teaching_period ?? true))
            ->values();

        if ($teachingSlots->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No active teaching slots configured for the school week.',
                'allocated_slots' => 0,
                'required_lessons' => 0,
                'detected_conflicts' => 0,
                'unscheduled_lessons' => 0,
            ];
        }

        $activeDays = array_values(array_unique($teachingSlots->pluck('day_of_week')->all()));
        $numDays = max(1, count($activeDays));

        // Real Database Resources
        $streams = Stream::where('school_id', $schoolId)->with('schoolClass')->get()->keyBy('id');
        $schoolClasses = SchoolClass::where('school_id', $schoolId)->get()->keyBy('id');
        $classrooms = Classroom::where('school_id', $schoolId)->get();
        $rooms = Schema::hasTable('rooms') ? Room::where('school_id', $schoolId)->get()->keyBy('id') : collect();
        $teacherAvailabilities = Schema::hasTable('teacher_availabilities')
            ? TeacherAvailability::where('school_id', $schoolId)->get()->keyBy('teacher_id')
            : collect();

        // 1. Build stream-specific teaching requirements
        $requirementsByStream = $this->buildTeachingRequirements($timetable);
        $totalRequired = 0;
        $totalByStream = [];
        $totalByTeacher = [];
        $totalBySubjectStream = [];
        $lessonUnits = [];

        foreach ($requirementsByStream as $streamId => $streamRequests) {
            foreach ($streamRequests as $req) {
                $teacherId = (int) ($req['teacher_id'] ?? 0);
                $subjectId = (int) ($req['subject_id'] ?? 0);
                $classId = (int) ($req['class_id'] ?? 0);
                $streamId = (int) ($req['stream_id'] ?? 0);
                $requiredLessons = max(0, (int) ($req['required_lessons'] ?? 0));

                if (!$teacherId || !$subjectId || !$classId || !$streamId || $requiredLessons <= 0) {
                    continue;
                }

                $totalRequired += $requiredLessons;
                $totalByStream[$streamId] = ($totalByStream[$streamId] ?? 0) + $requiredLessons;
                $totalByTeacher[$teacherId] = ($totalByTeacher[$teacherId] ?? 0) + $requiredLessons;
                $totalBySubjectStream[$streamId][$subjectId] = $requiredLessons;

                $subjectName = $req['subject_name'] ?? 'Subject';
                $subjectCode = $req['subject_code'] ?? '';
                $className = $req['class_name'] ?? 'Class';
                $streamName = $req['stream_name'] ?? '';
                $teacherName = $req['teacher_name'] ?? 'Teacher';
                $isPractical = $this->isPracticalSubject($subjectName, $subjectCode);

                for ($lessonIdx = 1; $lessonIdx <= $requiredLessons; $lessonIdx++) {
                    $lessonUnits[] = [
                        'class_id' => $classId,
                        'class_name' => $className,
                        'stream_id' => $streamId,
                        'stream_name' => $streamName,
                        'subject_id' => $subjectId,
                        'subject_name' => $subjectName,
                        'subject_code' => $subjectCode,
                        'teacher_id' => $teacherId,
                        'teacher_name' => $teacherName,
                        'required_lessons' => $requiredLessons,
                        'lesson_index' => $lessonIdx,
                        'is_practical' => $isPractical,
                    ];
                }
            }
        }

        // 2. Ordering strategy for lesson units
        $strategy = $settings->preferred_scheduling_strategy ?: 'Balanced';
        usort($lessonUnits, function ($a, $b) use ($strategy, $totalByTeacher) {
            if ($a['lesson_index'] !== $b['lesson_index']) {
                return $a['lesson_index'] <=> $b['lesson_index'];
            }

            if ($strategy === 'Teacher First') {
                $teacherLoadA = $totalByTeacher[$a['teacher_id']] ?? 0;
                $teacherLoadB = $totalByTeacher[$b['teacher_id']] ?? 0;
                if ($teacherLoadA !== $teacherLoadB) {
                    return $teacherLoadB <=> $teacherLoadA;
                }
            } elseif ($strategy === 'Subject First') {
                if ($a['required_lessons'] !== $b['required_lessons']) {
                    return $b['required_lessons'] <=> $a['required_lessons'];
                }
            }

            if ($a['stream_id'] !== $b['stream_id']) {
                return $a['stream_id'] <=> $b['stream_id'];
            }

            return $a['subject_id'] <=> $b['subject_id'];
        });

        // 3. Execution state
        $state = [
            'teacherBusy' => [],
            'streamBusy' => [],
            'roomBusy' => [],
            'teacherDayCount' => [],
            'streamDayCount' => [],
            'streamSubjectDayCount' => [],
            'teacherPeriods' => [],
            'streamPeriods' => [],
        ];

        $allocationsToCreate = [];
        $unscheduled = [];

        // 4. Scheduling loop
        foreach ($lessonUnits as $unit) {
            $classId = $unit['class_id'];
            $streamId = $unit['stream_id'];
            $subjectId = $unit['subject_id'];
            $teacherId = $unit['teacher_id'];
            $subjectName = $unit['subject_name'];
            $className = $unit['class_name'];
            $streamName = $unit['stream_name'];
            $teacherName = $unit['teacher_name'];
            $requiredLessons = $unit['required_lessons'];
            $isPractical = $unit['is_practical'];

            $targetStreamDaily = (int) ceil(($totalByStream[$streamId] ?? 0) / $numDays);
            $targetTeacherDaily = (int) ceil(($totalByTeacher[$teacherId] ?? 0) / $numDays);

            $teacherAvail = $teacherAvailabilities->get($teacherId);
            $candidateSlots = [];

            foreach ($teachingSlots as $slot) {
                // Find available classroom for this school if classrooms exist
                $roomId = null;
                if ($classrooms->isNotEmpty()) {
                    $availableRoom = $classrooms->first(function ($r) use ($state, $slot) {
                        return empty($state['roomBusy'][$slot->day_of_week][$slot->period_number][$r->id]);
                    });
                    if ($availableRoom) {
                        $roomId = $availableRoom->id;
                    }
                }

                $roomAvail = $roomId ? $rooms->get($roomId) : null;

                // Validate hard constraints: no teacher conflict, no stream conflict, no room conflict, no break period
                if (!$this->validateHardConstraints(
                    $slot,
                    $classId,
                    $streamId,
                    $subjectId,
                    $teacherId,
                    $roomId,
                    $state,
                    $settings,
                    $teacherAvail,
                    $roomAvail
                )) {
                    continue;
                }

                // Calculate balancing score
                $score = $this->scoreCandidateSlot(
                    $slot,
                    $classId,
                    $streamId,
                    $subjectId,
                    $subjectName,
                    $teacherId,
                    $requiredLessons,
                    $isPractical,
                    $state,
                    $settings,
                    $numDays,
                    $targetStreamDaily,
                    $targetTeacherDaily
                );

                $candidateSlots[] = [
                    'slot' => $slot,
                    'room_id' => $roomId,
                    'score' => $score,
                ];
            }

            if (empty($candidateSlots)) {
                $unscheduled[] = [
                    'class_name' => $className . ($streamName ? ' (' . $streamName . ')' : ''),
                    'stream_id' => $streamId,
                    'subject_name' => $subjectName,
                    'teacher_name' => $teacherName,
                    'required_lessons' => $requiredLessons,
                    'lesson_index' => $unit['lesson_index'],
                    'reason' => "No conflict-free teaching slot available across all configured school days respecting constraints for teacher '{$teacherName}' or stream '{$streamName}'.",
                ];
                continue;
            }

            usort($candidateSlots, fn ($a, $b) => $b['score'] <=> $a['score']);

            $topScore = $candidateSlots[0]['score'];
            $topCohort = array_filter($candidateSlots, fn ($cand) => ($topScore - $cand['score']) <= 3.5);
            $topCohort = array_values($topCohort);
            $chosenCandidate = $topCohort[array_rand($topCohort)];

            $slot = $chosenCandidate['slot'];
            $roomId = $chosenCandidate['room_id'];
            $day = $slot->day_of_week;
            $period = (int) $slot->period_number;

            // Commit slot to state
            $state['teacherBusy'][$day][$period][$teacherId] = true;
            $state['streamBusy'][$day][$period][$streamId] = true;
            if ($roomId) {
                $state['roomBusy'][$day][$period][$roomId] = true;
            }

            $state['teacherDayCount'][$teacherId][$day] = ($state['teacherDayCount'][$teacherId][$day] ?? 0) + 1;
            $state['streamDayCount'][$streamId][$day] = ($state['streamDayCount'][$streamId][$day] ?? 0) + 1;
            $state['streamSubjectDayCount'][$streamId][$subjectId][$day] = ($state['streamSubjectDayCount'][$streamId][$subjectId][$day] ?? 0) + 1;
            $state['teacherPeriods'][$teacherId][$day][$period] = true;
            $state['streamPeriods'][$streamId][$day][$period] = $subjectId;

            // Normal lesson allocation: stream_id MUST NOT be null
            $allocationsToCreate[] = [
                'school_id' => $schoolId,
                'timetable_id' => $timetable->id,
                'timetable_slot_id' => $slot->id,
                'stream_id' => $streamId,
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'classroom_id' => $roomId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            Log::info("Timetable lesson scheduled", [
                'school_id' => $schoolId,
                'timetable_id' => $timetable->id,
                'class_id' => $classId,
                'stream_id' => $streamId,
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'timetable_slot_id' => $slot->id,
                'classroom_id' => $roomId,
            ]);
        }

        // 5. Persist allocations in transaction
        DB::transaction(function () use ($timetable, $allocationsToCreate, $unscheduled, $schoolId) {
            TimetableAllocation::where('timetable_id', $timetable->id)->delete();
            foreach (array_chunk($allocationsToCreate, 100) as $chunk) {
                TimetableAllocation::insert($chunk);
            }

            // Record conflicts if conflicts table exists
            if (Schema::hasTable('timetable_conflicts')) {
                DB::table('timetable_conflicts')->where('timetable_id', $timetable->id)->delete();
                foreach ($unscheduled as $conflict) {
                    DB::table('timetable_conflicts')->insert([
                        'school_id' => $schoolId,
                        'timetable_id' => $timetable->id,
                        'type' => 'scheduling_conflict',
                        'entity_type' => 'stream',
                        'entity_id' => $conflict['stream_id'] ?? 0,
                        'day_of_week' => null,
                        'period_number' => null,
                        'message' => $conflict['reason'] ?? 'Unscheduled lesson',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $allocatedCount = count($allocationsToCreate);
        $unscheduledCount = count($unscheduled);

        return [
            'success' => true,
            'allocated_slots' => $allocatedCount,
            'required_lessons' => $totalRequired,
            'teaching_days_count' => $numDays,
            'detected_conflicts' => $unscheduledCount,
            'unscheduled_lessons' => $unscheduledCount,
            'unscheduled_details' => $unscheduled,
        ];
    }

    protected function validateHardConstraints(
        TimetableSlot $slot,
        int $classId,
        int $streamId,
        int $subjectId,
        int $teacherId,
        ?int $roomId,
        array $state,
        TimetableGenerationSetting $settings,
        $teacherAvailability,
        $roomAvailability
    ): bool {
        $day = $slot->day_of_week;
        $period = (int) $slot->period_number;

        // 1. Slot must be a valid teaching period and not a break
        if ($slot->is_break || !($slot->is_teaching_period ?? true)) {
            return false;
        }

        // 2. Teacher conflict
        if (!empty($state['teacherBusy'][$day][$period][$teacherId])) {
            return false;
        }

        // 3. Stream conflict: stream cannot have two subjects at the same time
        if (!empty($state['streamBusy'][$day][$period][$streamId])) {
            return false;
        }

        // 4. Room conflict: room cannot be double-booked
        if ($roomId && !empty($state['roomBusy'][$day][$period][$roomId])) {
            return false;
        }

        // 5. Max lessons per teacher per day
        $maxTeacherPerDay = (int) ($settings->max_lessons_per_teacher_per_day > 0 ? $settings->max_lessons_per_teacher_per_day : 6);
        $teacherDayLessons = $state['teacherDayCount'][$teacherId][$day] ?? 0;
        if ($teacherDayLessons >= $maxTeacherPerDay) {
            return false;
        }

        // 6. Max lessons per stream per day
        $maxStreamPerDay = (int) ($settings->max_lessons_per_stream_per_day > 0 ? $settings->max_lessons_per_stream_per_day : 6);
        $streamDayLessons = $state['streamDayCount'][$streamId][$day] ?? 0;
        if ($streamDayLessons >= $maxStreamPerDay) {
            return false;
        }

        // 7. Max consecutive lessons per teacher
        $maxConsecutiveTeacher = (int) ($settings->max_consecutive_lessons_per_teacher > 0 ? $settings->max_consecutive_lessons_per_teacher : 2);
        $consecutive = 1;
        $p = $period - 1;
        while (!empty($state['teacherPeriods'][$teacherId][$day][$p])) {
            $consecutive++;
            $p--;
        }
        $p = $period + 1;
        while (!empty($state['teacherPeriods'][$teacherId][$day][$p])) {
            $consecutive++;
            $p++;
        }
        if ($consecutive > $maxConsecutiveTeacher) {
            return false;
        }

        // 8. Teacher availability
        if ($settings->enforce_teacher_availability && $teacherAvailability) {
            if (!empty($teacherAvailability->available_days) && is_array($teacherAvailability->available_days)) {
                if (!in_array($day, $teacherAvailability->available_days)) {
                    return false;
                }
            }
            if (!empty($teacherAvailability->available_periods) && is_array($teacherAvailability->available_periods)) {
                if (!in_array($period, $teacherAvailability->available_periods)) {
                    return false;
                }
            }
            if (!empty($teacherAvailability->blocked_periods) && is_array($teacherAvailability->blocked_periods)) {
                $blocked = $teacherAvailability->blocked_periods;
                if (in_array($period, $blocked) || in_array("{$day}-{$period}", $blocked) || in_array("{$day}:{$period}", $blocked) || in_array(strtolower("{$day}-{$period}"), array_map('strtolower', $blocked))) {
                    return false;
                }
            }
            if (!empty($teacherAvailability->max_workload_per_day) && $teacherAvailability->max_workload_per_day > 0) {
                if ($teacherDayLessons >= $teacherAvailability->max_workload_per_day) {
                    return false;
                }
            }
        }

        // 9. Room availability
        if ($settings->enforce_room_availability && $roomId && $roomAvailability) {
            if (!$roomAvailability->is_available) {
                return false;
            }
        }

        return true;
    }

    protected function scoreCandidateSlot(
        TimetableSlot $slot,
        int $classId,
        int $streamId,
        int $subjectId,
        string $subjectName,
        int $teacherId,
        int $requiredLessons,
        bool $isPractical,
        array $state,
        TimetableGenerationSetting $settings,
        int $numDays,
        int $targetStreamDaily,
        int $targetTeacherDaily
    ): float {
        $day = $slot->day_of_week;
        $period = (int) $slot->period_number;
        $strategy = $settings->preferred_scheduling_strategy ?: 'Balanced';
        $allowDouble = (bool) ($settings->allow_double_lessons ?? true);
        $practicalConsecOnly = (bool) ($settings->practical_subjects_consecutive ?? false);

        $streamDayLessons   = $state['streamDayCount'][$streamId][$day] ?? 0;
        $teacherDayLessons  = $state['teacherDayCount'][$teacherId][$day] ?? 0;
        $subjectDayLessons  = $state['streamSubjectDayCount'][$streamId][$subjectId][$day] ?? 0;

        $streamAllDayCounts = $state['streamDayCount'][$streamId] ?? [];
        $minStreamDayLoad   = empty($streamAllDayCounts) ? 0 : min(array_values($streamAllDayCounts));
        $overloadVsMin      = $streamDayLessons - $minStreamDayLoad;

        $score = 1000.0;

        if ($overloadVsMin > 0) {
            $score -= ($overloadVsMin * 180.0);
        }
        if ($streamDayLessons === $minStreamDayLoad) {
            $score += 120.0;
        }

        if ($subjectDayLessons === 0) {
            $score += 60.0;
        } elseif ($subjectDayLessons === 1) {
            if ($requiredLessons > $numDays) {
                $isAdjacent = (!empty($state['streamPeriods'][$streamId][$day][$period - 1]) && $state['streamPeriods'][$streamId][$day][$period - 1] === $subjectId)
                    || (!empty($state['streamPeriods'][$streamId][$day][$period + 1]) && $state['streamPeriods'][$streamId][$day][$period + 1] === $subjectId);

                if ($allowDouble && $isAdjacent && (!$practicalConsecOnly || $isPractical)) {
                    $score += 80.0;
                } else {
                    $score -= 150.0;
                }
            } else {
                $score -= 500.0;
            }
        } else {
            $score -= 950.0;
        }

        $prevSubject = $state['streamPeriods'][$streamId][$day][$period - 1] ?? null;
        $nextSubject = $state['streamPeriods'][$streamId][$day][$period + 1] ?? null;
        if ($prevSubject === $subjectId || $nextSubject === $subjectId) {
            if ($allowDouble && $subjectDayLessons === 1 && $requiredLessons > $numDays && (!$practicalConsecOnly || $isPractical)) {
                $score += 60.0;
            } else {
                $score -= 650.0;
            }
        }

        $score -= ($streamDayLessons * 45.0);
        if ($streamDayLessons < $targetStreamDaily) {
            $score += ($targetStreamDaily - $streamDayLessons) * 30.0;
        }

        $teacherAllDayCounts = $state['teacherDayCount'][$teacherId] ?? [];
        $minTeacherDayLoad   = empty($teacherAllDayCounts) ? 0 : min(array_values($teacherAllDayCounts));
        $teacherOverloadVsMin = $teacherDayLessons - $minTeacherDayLoad;
        if ($teacherOverloadVsMin > 0) {
            $score -= ($teacherOverloadVsMin * 140.0);
        }
        $score -= ($teacherDayLessons * 35.0);
        if ($teacherDayLessons < $targetTeacherDaily) {
            $score += ($targetTeacherDaily - $teacherDayLessons) * 25.0;
        }

        if ($strategy === 'Teacher First') {
            $score -= ($teacherDayLessons * 50.0);
            if (!empty($state['teacherPeriods'][$teacherId][$day][$period - 1]) ||
                !empty($state['teacherPeriods'][$teacherId][$day][$period + 1])) {
                $score += 30.0;
            }
        } elseif ($strategy === 'Subject First') {
            if ($subjectDayLessons > 0) {
                $score -= 350.0;
            }
            if (in_array(strtolower($subjectName), ['english', 'maths', 'mathematics', 'science', 'kiswahili']) && $period <= 4) {
                $score += 35.0;
            }
        }

        $tieBreaker = (mt_rand(0, 1000) / 1000.0) * 2.5;
        return $score + $tieBreaker;
    }

    protected function isPracticalSubject(?string $subjectName, ?string $subjectCode): bool
    {
        $text = strtolower(trim(($subjectName ?? '') . ' ' . ($subjectCode ?? '')));
        $keywords = ['science', 'chemistry', 'physics', 'biology', 'computer', 'agriculture', 'art', 'craft', 'music', 'home science', 'practical', 'lab', 'pe', 'physical'];
        foreach ($keywords as $kw) {
            if (str_contains($text, $kw)) {
                return true;
            }
        }

        return false;
    }

    public function getGenerationSettings(Timetable $timetable): TimetableGenerationSetting
    {
        $defaults = [
            'max_lessons_per_teacher_per_day' => 6,
            'max_consecutive_lessons_per_teacher' => 2,
            'max_lessons_per_stream_per_day' => 6,
            'allow_double_lessons' => true,
            'practical_subjects_consecutive' => false,
            'core_subjects_distributed' => true,
            'enforce_teacher_availability' => true,
            'enforce_room_availability' => true,
            'conflict_free_generation' => true,
            'preferred_scheduling_strategy' => 'Balanced',
            'school_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'lessons_per_day' => 8,
            'lesson_duration_minutes' => 45,
            'school_start_time' => '08:00:00',
            'school_end_time' => '16:30:00',
            'break_duration_minutes' => 30,
            'activity_blocks' => [
                ['name' => 'Tea Break', 'type' => 'break', 'start_time' => '10:15:00', 'duration_minutes' => 30],
                ['name' => 'Lunch Break', 'type' => 'break', 'start_time' => '13:00:00', 'duration_minutes' => 60],
                ['name' => 'Games & Clubs', 'type' => 'break', 'start_time' => '15:30:00', 'duration_minutes' => 60],
            ],
            'strict_validation' => true,
            'allow_incomplete_timetable' => false,
            'generate_draft_only' => true,
        ];

        if (!Schema::hasTable('timetable_generation_settings')) {
            $settings = new TimetableGenerationSetting();
            $settings->fill($defaults);
            $settings->school_id = $timetable->school_id;
            $settings->timetable_id = $timetable->id;
            return $settings;
        }

        $settings = TimetableGenerationSetting::firstOrCreate(
            ['school_id' => $timetable->school_id, 'timetable_id' => $timetable->id],
            $defaults
        );

        if (empty($settings->max_lessons_per_teacher_per_day) || (int) $settings->max_lessons_per_teacher_per_day <= 0) {
            $settings->max_lessons_per_teacher_per_day = 6;
        }
        if (empty($settings->max_consecutive_lessons_per_teacher) || (int) $settings->max_consecutive_lessons_per_teacher <= 0) {
            $settings->max_consecutive_lessons_per_teacher = 2;
        }
        if (empty($settings->max_lessons_per_stream_per_day) || (int) $settings->max_lessons_per_stream_per_day <= 0) {
            $settings->max_lessons_per_stream_per_day = 6;
        }
        if (empty($settings->preferred_scheduling_strategy)) {
            $settings->preferred_scheduling_strategy = 'Balanced';
        }
        if (empty($settings->school_days)) {
            $settings->school_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        }

        return $settings;
    }

    public function getTimetableHealth(Timetable $timetable): array
    {
        if (!Schema::hasTable('timetable_allocations')) {
            return [
                'teacher_conflicts' => 0,
                'stream_conflicts' => 0,
                'room_conflicts' => 0,
                'unscheduled_lessons' => 0,
                'teacher_overload' => 0,
                'room_capacity_warnings' => 0,
                'score' => 100,
                'status_color' => 'green',
            ];
        }

        $allocations = TimetableAllocation::where('timetable_id', $timetable->id)->get();
        $teacherConflicts = 0;
        $streamConflicts = 0;
        $roomConflicts = 0;
        $unscheduledLessons = 0;
        $teacherOverload = 0;
        $roomCapacityWarnings = 0;

        $teacherSlots = [];
        $streamSlots = [];
        $roomSlots = [];

        foreach ($allocations as $allocation) {
            $slotKey = $allocation->timetable_slot_id;
            if ($allocation->teacher_id) {
                $teacherSlots[$allocation->teacher_id][$slotKey] = ($teacherSlots[$allocation->teacher_id][$slotKey] ?? 0) + 1;
                if (($teacherSlots[$allocation->teacher_id][$slotKey] ?? 0) > 1) {
                    $teacherConflicts++;
                }
            }
            if ($allocation->stream_id) {
                $streamSlots[$allocation->stream_id][$slotKey] = ($streamSlots[$allocation->stream_id][$slotKey] ?? 0) + 1;
                if (($streamSlots[$allocation->stream_id][$slotKey] ?? 0) > 1) {
                    $streamConflicts++;
                }
            }
            if ($allocation->classroom_id) {
                $roomSlots[$allocation->classroom_id][$slotKey] = ($roomSlots[$allocation->classroom_id][$slotKey] ?? 0) + 1;
                if (($roomSlots[$allocation->classroom_id][$slotKey] ?? 0) > 1) {
                    $roomConflicts++;
                }
            }
        }

        foreach (Teacher::where('school_id', $timetable->school_id)->get() as $teacher) {
            $workload = $allocations->where('teacher_id', $teacher->id)->count();
            if ($workload > ($teacher->max_weekly_lessons ?: 28)) {
                $teacherOverload++;
            }
        }

        if (Schema::hasTable('rooms')) {
            foreach (Room::where('school_id', $timetable->school_id)->get() as $room) {
                $usage = $allocations->where('classroom_id', $room->id)->count();
                if ($usage > $room->capacity) {
                    $roomCapacityWarnings++;
                }
            }
        }

        $score = max(0, 100 - ($teacherConflicts * 8) - ($streamConflicts * 9) - ($roomConflicts * 8) - $unscheduledLessons - $teacherOverload - $roomCapacityWarnings);
        if ($score >= 90) {
            $statusColor = 'green';
        } elseif ($score >= 70) {
            $statusColor = 'yellow';
        } else {
            $statusColor = 'red';
        }

        return [
            'teacher_conflicts' => $teacherConflicts,
            'stream_conflicts' => $streamConflicts,
            'room_conflicts' => $roomConflicts,
            'unscheduled_lessons' => $unscheduledLessons,
            'teacher_overload' => $teacherOverload,
            'room_capacity_warnings' => $roomCapacityWarnings,
            'score' => $score,
            'status_color' => $statusColor,
        ];
    }

    public function repairTimetable(Timetable $timetable): array
    {
        $schoolId = $timetable->school_id;
        $allocations = TimetableAllocation::where('timetable_id', $timetable->id)->get();
        $teacherSlots = [];
        $streamSlots = [];
        $roomSlots = [];

        foreach ($allocations as $allocation) {
            $slotKey = $allocation->timetable_slot_id;
            if ($allocation->teacher_id) {
                $teacherSlots[$allocation->teacher_id][$slotKey] = ($teacherSlots[$allocation->teacher_id][$slotKey] ?? 0) + 1;
            }
            if ($allocation->stream_id) {
                $streamSlots[$allocation->stream_id][$slotKey] = ($streamSlots[$allocation->stream_id][$slotKey] ?? 0) + 1;
            }
            if ($allocation->classroom_id) {
                $roomSlots[$allocation->classroom_id][$slotKey] = ($roomSlots[$allocation->classroom_id][$slotKey] ?? 0) + 1;
            }
        }

        $toDelete = [];
        foreach ($allocations as $allocation) {
            $slotId = $allocation->timetable_slot_id;
            if (($allocation->teacher_id && ($teacherSlots[$allocation->teacher_id][$slotId] ?? 0) > 1) ||
                ($allocation->stream_id && ($streamSlots[$allocation->stream_id][$slotId] ?? 0) > 1) ||
                ($allocation->classroom_id && ($roomSlots[$allocation->classroom_id][$slotId] ?? 0) > 1) ||
                empty($allocation->stream_id)) {
                $toDelete[] = $allocation->id;
            }
        }

        if (!empty($toDelete)) {
            TimetableAllocation::whereIn('id', $toDelete)->delete();
        }

        $remaining = TimetableAllocation::where('timetable_id', $timetable->id)->count();

        return [
            'allocated_slots' => $remaining,
            'repair_count' => count($toDelete),
        ];
    }

    public function resolveAllocationClassName(TimetableAllocation $allocation): ?string
    {
        $className = $allocation?->stream?->schoolClass?->name ?? null;

        if (!empty($className)) {
            return $className;
        }

        if (empty($allocation->teacher_id) || empty($allocation->subject_id) || empty($allocation->school_id)) {
            return null;
        }

        $sourceAllocation = TeacherSubjectAllocation::where('school_id', $allocation->school_id)
            ->where('teacher_id', $allocation->teacher_id)
            ->where('subject_id', $allocation->subject_id)
            ->when($allocation->stream_id !== null, fn ($query) => $query->where('stream_id', $allocation->stream_id), fn ($query) => $query->whereNull('stream_id'))
            ->first();

        return $sourceAllocation?->schoolClass?->name ?? null;
    }

    /**
     * Get school timetable matrix grouped by days and periods.
     */
    public function getSchoolTimetableMatrix(int $timetableId, ?int $classId = null, ?int $teacherId = null, ?string $dayFilter = null): array
    {
        $slots = TimetableSlot::where('timetable_id', $timetableId)
            ->when($dayFilter, fn ($query) => $query->where('day_of_week', $dayFilter))
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->get();

        $query = TimetableAllocation::where('timetable_id', $timetableId)
            ->with(['subject', 'teacher.user', 'stream.schoolClass', 'classroom', 'timetableSlot', 'schoolClass', 'room']);

        if ($classId) {
            $query->where(function ($q) use ($classId) {
                $q->whereHas('stream', fn ($streamQuery) => $streamQuery->where('school_class_id', $classId))
                  ->orWhereHas('schoolClass', fn ($scQuery) => $scQuery->where('school_classes.id', $classId));
            });
        }

        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }

        $allocations = $query->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $matrix = [];

        foreach ($days as $day) {
            $matrix[$day] = [];
            $daySlots = $slots->where('day_of_week', $day);

            foreach ($daySlots as $slot) {
                if ($slot->is_break) {
                    $matrix[$day][$slot->period_number] = [
                        [
                            'slot' => $slot,
                            'allocation' => null,
                            'is_break' => true,
                            'break_label' => $slot->break_label,
                            'start_time' => $slot->start_time,
                            'end_time' => $slot->end_time,
                            'period_number' => $slot->period_number,
                            'subject_name' => $slot->break_label ?: 'Break',
                            'subject_code' => '',
                            'teacher_name' => '',
                            'teacher_id' => null,
                            'stream_name' => '',
                            'class_id' => null,
                            'class_name' => 'All Classes',
                            'classroom_name' => '',
                            'room_name' => '',
                        ]
                    ];
                    continue;
                }

                $slotAllocations = $allocations->filter(fn ($allocation) => (int) $allocation->timetable_slot_id === (int) $slot->id)->values();

                $matrix[$day][$slot->period_number] = $slotAllocations->map(function ($allocation) use ($slot) {
                    return [
                        'slot' => $slot,
                        'allocation' => $allocation,
                        'is_break' => false,
                        'break_label' => null,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time,
                        'period_number' => $slot->period_number,
                        'subject_name' => $allocation?->subject?->name ?? null,
                        'subject_code' => $allocation?->subject?->code ?? '',
                        'teacher_name' => $allocation?->teacher?->user?->name ?? ($allocation?->teacher?->name ?? null),
                        'teacher_id' => $allocation?->teacher_id,
                        'stream_name' => $allocation?->stream?->name ?? null,
                        'class_id' => $allocation?->stream?->school_class_id ?? ($allocation?->schoolClass?->id ?? null),
                        'class_name' => $allocation?->stream?->schoolClass?->name ?? ($allocation?->schoolClass?->name ?? null),
                        'classroom_name' => $allocation?->classroom?->name ?? '',
                        'room_name' => $allocation?->room?->name ?? ($allocation?->classroom?->name ?? ''),
                    ];
                })->all();
            }
        }

        return $matrix;
    }

    /**
     * Get stream timetable matrix grouped by days and periods.
     * Contains both teaching allocations and break periods.
     */
    public function getStreamTimetableMatrix(int $timetableId, int $streamId): array
    {
        $slots = TimetableSlot::where('timetable_id', $timetableId)
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->get();

        $allocations = TimetableAllocation::where('timetable_id', $timetableId)
            ->where('stream_id', $streamId)
            ->with(['subject', 'teacher.user', 'stream.schoolClass', 'classroom', 'timetableSlot', 'schoolClass', 'room'])
            ->get()
            ->keyBy('timetable_slot_id');

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $matrix = [];

        foreach ($days as $day) {
            $matrix[$day] = [];
            $daySlots = $slots->where('day_of_week', $day);
            foreach ($daySlots as $slot) {
                $allocation = $allocations->get($slot->id);
                $matrix[$day][$slot->period_number] = [
                    'slot' => $slot,
                    'allocation' => $allocation,
                    'is_break' => (bool) $slot->is_break,
                    'break_label' => $slot->break_label,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'period_number' => $slot->period_number,
                    'subject_name' => $allocation?->subject?->name ?? null,
                    'subject_code' => $allocation?->subject?->code ?? '',
                    'teacher_name' => $allocation?->teacher?->user?->name ?? ($allocation?->teacher?->name ?? null),
                    'teacher_id' => $allocation?->teacher_id,
                    'stream_name' => $allocation?->stream?->name ?? null,
                    'classroom_name' => $allocation?->classroom?->name ?? '',
                    'room_name' => $allocation?->room?->name ?? ($allocation?->classroom?->name ?? ''),
                    'class_name' => $allocation?->stream?->schoolClass?->name ?? ($allocation?->schoolClass?->name ?? null),
                ];
            }
        }

        return $matrix;
    }

    /**
     * Get teacher timetable matrix grouped by days and periods.
     */
    public function getTeacherTimetableMatrix(int $timetableId, int $teacherId): array
    {
        $slots = TimetableSlot::where('timetable_id', $timetableId)
            ->orderBy('day_of_week')
            ->orderBy('period_number')
            ->get();

        $allocations = TimetableAllocation::where('timetable_id', $timetableId)
            ->where('teacher_id', $teacherId)
            ->with(['subject', 'stream.schoolClass', 'teacher.user', 'classroom', 'timetableSlot', 'schoolClass', 'room'])
            ->get()
            ->keyBy('timetable_slot_id');

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $matrix = [];

        foreach ($days as $day) {
            $matrix[$day] = [];
            $daySlots = $slots->where('day_of_week', $day);
            foreach ($daySlots as $slot) {
                $allocation = $allocations->get($slot->id);
                $matrix[$day][$slot->period_number] = [
                    'slot' => $slot,
                    'allocation' => $allocation,
                    'is_break' => (bool) $slot->is_break,
                    'break_label' => $slot->break_label,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'period_number' => $slot->period_number,
                    'subject_name' => $allocation?->subject?->name ?? null,
                    'subject_code' => $allocation?->subject?->code ?? '',
                    'teacher_name' => $allocation?->teacher?->user?->name ?? ($allocation?->teacher?->name ?? null),
                    'teacher_id' => $allocation?->teacher_id,
                    'stream_name' => $allocation?->stream?->name ?? null,
                    'classroom_name' => $allocation?->classroom?->name ?? '',
                    'room_name' => $allocation?->room?->name ?? ($allocation?->classroom?->name ?? ''),
                    'class_name' => $allocation?->stream?->schoolClass?->name ?? ($allocation?->schoolClass?->name ?? null),
                ];
            }
        }

        return $matrix;
    }
}
