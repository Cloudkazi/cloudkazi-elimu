<?php

namespace App\Http\Controllers;

use App\Models\LearningAttempt;
use App\Models\LearningChapter;
use App\Models\LearningChapterProgress;
use App\Models\LearningQuestion;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeacherSubjectAllocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LearningPracticeController extends Controller
{
    public function index(Request $request)
    {
        $student = $this->authenticatedStudent();
        $subjectId = $request->integer('subject_id');

        if (!$this->practiceTablesAvailable()) {
            return view('portals.practice', [
                'student' => $student,
                'chapters' => collect(),
                'subjects' => collect(),
                'subjectId' => $subjectId,
                'latestAttempts' => collect(),
                'progress' => collect(),
                'practiceAvailable' => false,
            ]);
        }

        $chapters = LearningChapter::with([
            'subject',
            'questions' => fn ($query) => $query->where('is_published', true)->orderBy('id'),
        ])
            ->where('school_id', $student->school_id)
            ->where('school_class_id', $student->school_class_id)
            ->where('is_published', true)
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->orderBy('sequence')
            ->get();

        $questionIds = $chapters->flatMap(fn ($chapter) => $chapter->questions->pluck('id'));
        $latestAttempts = LearningAttempt::where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->whereIn('learning_question_id', $questionIds)
            ->orderByDesc('answered_at')
            ->get()
            ->unique('learning_question_id')
            ->keyBy('learning_question_id');
        $progress = LearningChapterProgress::where('school_id', $student->school_id)
            ->where('student_id', $student->id)
            ->whereIn('learning_chapter_id', $chapters->pluck('id'))
            ->get()
            ->keyBy('learning_chapter_id');
        foreach ($chapters as $chapter) {
            $chapterAttempts = LearningAttempt::where('school_id', $student->school_id)
                ->where('student_id', $student->id)
                ->whereIn('learning_question_id', $chapter->questions->pluck('id'))
                ->get();
            $accuracy = $chapterAttempts->isNotEmpty()
                ? $chapterAttempts->where('is_correct', true)->count() / $chapterAttempts->count()
                : null;
            $difficultyOrder = $accuracy !== null && $accuracy >= 0.8
                ? ['hard' => 0, 'medium' => 1, 'easy' => 2]
                : ['easy' => 0, 'medium' => 1, 'hard' => 2];
            $chapter->setRelation('questions', $chapter->questions
                ->sortBy(fn ($question) => [$difficultyOrder[$question->difficulty] ?? 1, $question->id])
                ->values());
        }
        $subjects = Subject::where('school_id', $student->school_id)
            ->whereIn('id', LearningChapter::where('school_id', $student->school_id)
                ->where('school_class_id', $student->school_class_id)
                ->where('is_published', true)
                ->select('subject_id'))
            ->orderBy('name')
            ->get();

        return view('portals.practice', compact('student', 'chapters', 'subjects', 'subjectId', 'latestAttempts', 'progress') + [
            'practiceAvailable' => true,
        ]);
    }

    public function answer(Request $request, int $question)
    {
        $student = $this->authenticatedStudent();
        abort_unless($this->practiceTablesAvailable(), 503, 'Student practice is unavailable until the practice database migration is applied.');
        $question = LearningQuestion::with('chapter')
            ->where('school_id', $student->school_id)
            ->where('is_published', true)
            ->findOrFail($question);

        abort_unless(
            $question->chapter
                && $question->chapter->school_id === $student->school_id
                && $question->chapter->school_class_id === $student->school_class_id
                && $question->chapter->is_published,
            403
        );

        $validated = $request->validate([
            'answer' => 'required|string|max:2000',
        ]);

        $correct = mb_strtolower(trim($validated['answer']))
            === mb_strtolower(trim($question->correct_answer));

        DB::transaction(function () use ($student, $question, $validated, $correct) {
            LearningAttempt::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'learning_question_id' => $question->id,
                'answer' => trim($validated['answer']),
                'is_correct' => $correct,
                'answered_at' => now(),
            ]);

            $chapterQuestionIds = $question->chapter->questions()->select('id');
            $chapterAttempts = LearningAttempt::where('student_id', $student->id)
                ->whereIn('learning_question_id', $chapterQuestionIds);
            $chapterAttemptCount = (clone $chapterAttempts)->count();
            $chapterCorrectCount = (clone $chapterAttempts)->where('is_correct', true)->count();
            $completedQuestionsCount = (clone $chapterAttempts)->distinct('learning_question_id')->count('learning_question_id');
            $totalPublishedQuestions = $question->chapter->questions()->where('is_published', true)->count();

            LearningChapterProgress::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'learning_chapter_id' => $question->learning_chapter_id,
                ],
                [
                    'school_id' => $student->school_id,
                    'attempts_count' => $chapterAttemptCount,
                    'correct_count' => $chapterCorrectCount,
                    'completed_questions_count' => $completedQuestionsCount,
                    'mastery_percent' => $chapterAttemptCount > 0 ? round($chapterCorrectCount / $chapterAttemptCount * 100, 2) : 0,
                    'completion_percent' => $totalPublishedQuestions > 0 ? round($completedQuestionsCount / $totalPublishedQuestions * 100, 2) : 0,
                    'last_attempted_at' => now(),
                ]
            );
        });

        return redirect()->route('portal.practice', ['subject_id' => $question->chapter->subject_id])
            ->with('practice_feedback', [
                'question_id' => $question->id,
                'correct' => $correct,
                'answer' => $question->correct_answer,
                'explanation' => $question->explanation,
            ]);
    }

    public function manage()
    {
        $schoolId = $this->managerSchoolId();
        $this->requirePracticeTables();
        $user = Auth::user();
        $chaptersQuery = LearningChapter::with(['schoolClass', 'subject', 'questions'])
            ->where('school_id', $schoolId);
        $classesQuery = SchoolClass::where('school_id', $schoolId);
        $subjectsQuery = Subject::where('school_id', $schoolId);

        if (!$user->isSuperAdmin() && !$user->hasRole(['super-admin', 'school-admin', 'principal', 'deputy-principal'])) {
            $teacherId = $user->teacher?->id;
            abort_unless($teacherId, 403);
            $allocations = TeacherSubjectAllocation::where('school_id', $schoolId)
                ->where('teacher_id', $teacherId)
                ->get(['school_class_id', 'subject_id']);
            $classesQuery->whereIn('id', $allocations->pluck('school_class_id')->unique());
            $subjectsQuery->whereIn('id', $allocations->pluck('subject_id')->unique());
            $chaptersQuery->where(function ($query) use ($allocations) {
                foreach ($allocations as $allocation) {
                    $query->orWhere(function ($pair) use ($allocation) {
                        $pair->where('school_class_id', $allocation->school_class_id)
                            ->where('subject_id', $allocation->subject_id);
                    });
                }
                if ($allocations->isEmpty()) {
                    $query->whereRaw('1 = 0');
                }
            });
        }

        $classes = $classesQuery->orderBy('numeric_level')->get();
        $subjects = $subjectsQuery->orderBy('name')->get();
        $chapters = $chaptersQuery->latest()->get();

        return view('portals.practice-content', compact('classes', 'subjects', 'chapters'));
    }

    public function storeChapter(Request $request)
    {
        $schoolId = $this->managerSchoolId();
        $this->requirePracticeTables();
        $validated = $request->validate([
            'school_class_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'sequence' => 'required|integer|min:1|max:10000',
            'is_published' => 'nullable|boolean',
        ]);

        $class = SchoolClass::where('school_id', $schoolId)->findOrFail($validated['school_class_id']);
        $subject = Subject::where('school_id', $schoolId)->findOrFail($validated['subject_id']);
        $this->authorizeTeaching($class->id, $subject->id);

        LearningChapter::create([
            'school_id' => $schoolId,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'sequence' => $validated['sequence'],
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('success', 'Learning chapter saved.');
    }

    public function storeQuestion(Request $request)
    {
        $schoolId = $this->managerSchoolId();
        $this->requirePracticeTables();
        $validated = $request->validate([
            'learning_chapter_id' => 'required|integer',
            'topic' => 'nullable|string|max:255',
            'section' => 'nullable|string|max:255',
            'prompt' => 'required|string|max:5000',
            'question_type' => 'required|in:multiple_choice,short_answer',
            'options' => 'required_if:question_type,multiple_choice|array|min:2|max:8',
            'options.*' => 'required|string|max:500',
            'correct_answer' => 'required|string|max:2000',
            'explanation' => 'nullable|string|max:3000',
            'difficulty' => 'required|in:easy,medium,hard',
            'is_published' => 'nullable|boolean',
        ]);

        $chapter = LearningChapter::with('questions')
            ->where('school_id', $schoolId)
            ->findOrFail($validated['learning_chapter_id']);
        $this->authorizeTeaching($chapter->school_class_id, $chapter->subject_id);

        $options = collect($validated['options'] ?? [])
            ->map(fn ($option) => trim($option))
            ->filter()
            ->values();
        if ($validated['question_type'] === 'multiple_choice'
            && (!$options->contains($validated['correct_answer']) || $options->count() < 2)) {
            return back()->withErrors(['correct_answer' => 'Choose a correct answer that exactly matches one of the options.'])->withInput();
        }

        LearningQuestion::create([
            'school_id' => $schoolId,
            'learning_chapter_id' => $chapter->id,
            'topic' => $validated['topic'] ?? null,
            'section' => $validated['section'] ?? null,
            'prompt' => $validated['prompt'],
            'question_type' => $validated['question_type'],
            'options' => $validated['question_type'] === 'multiple_choice' ? $options->all() : null,
            'correct_answer' => $validated['correct_answer'],
            'explanation' => $validated['explanation'] ?? null,
            'difficulty' => $validated['difficulty'],
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('success', 'Practice question saved.');
    }

    public function updateChapter(Request $request, int $chapter)
    {
        $schoolId = $this->managerSchoolId();
        $this->requirePracticeTables();
        $chapter = LearningChapter::where('school_id', $schoolId)->findOrFail($chapter);
        $this->authorizeTeaching($chapter->school_class_id, $chapter->subject_id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'sequence' => 'required|integer|min:1|max:10000',
        ]);

        $chapter->update([
            ...$validated,
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('success', 'Chapter updated.');
    }

    public function updateQuestion(Request $request, int $question)
    {
        $schoolId = $this->managerSchoolId();
        $this->requirePracticeTables();
        $question = LearningQuestion::with('chapter')->where('school_id', $schoolId)->findOrFail($question);
        $this->authorizeTeaching($question->chapter->school_class_id, $question->chapter->subject_id);
        $validated = $request->validate([
            'prompt' => 'required|string|max:5000',
            'topic' => 'nullable|string|max:255',
            'section' => 'nullable|string|max:255',
            'question_type' => 'required|in:multiple_choice,short_answer',
            'options' => 'required_if:question_type,multiple_choice|array|min:2|max:8',
            'options.*' => 'required|string|max:500',
            'correct_answer' => 'required|string|max:2000',
            'explanation' => 'nullable|string|max:3000',
            'difficulty' => 'required|in:easy,medium,hard',
        ]);
        $options = collect($validated['options'] ?? [])->map(fn ($option) => trim($option))->filter()->values();
        if ($validated['question_type'] === 'multiple_choice'
            && (!$options->contains($validated['correct_answer']) || $options->count() < 2)) {
            return back()->withErrors(['correct_answer' => 'Choose a correct answer that exactly matches one of the options.'])->withInput();
        }

        $question->update([
            'prompt' => $validated['prompt'],
            'topic' => $validated['topic'] ?? null,
            'section' => $validated['section'] ?? null,
            'question_type' => $validated['question_type'],
            'options' => $validated['question_type'] === 'multiple_choice' ? $options->all() : null,
            'correct_answer' => $validated['correct_answer'],
            'explanation' => $validated['explanation'] ?? null,
            'difficulty' => $validated['difficulty'],
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('success', 'Practice question updated.');
    }

    private function authenticatedStudent(): \App\Models\Student
    {
        $user = Auth::user();
        abort_unless($user && $user->isStudent(), 403);
        $student = $user->student;
        abort_unless($student && $student->school_id === ($user->school_id ?? $student->school_id), 403);

        return $student;
    }

    private function practiceTablesAvailable(): bool
    {
        foreach ([
            'learning_chapters',
            'learning_questions',
            'learning_attempts',
            'learning_chapter_progress',
        ] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function requirePracticeTables(): void
    {
        abort_unless($this->practiceTablesAvailable(), 503, 'Practice content management is unavailable until the practice database migration is applied.');
    }

    private function managerSchoolId(): int
    {
        $user = Auth::user();
        abort_unless($user && ($user->isSuperAdmin() || $user->hasRole(['super-admin', 'school-admin', 'principal', 'deputy-principal', 'teacher', 'class-teacher'])), 403);
        $schoolId = (int) ($user->school_id ?? session('active_school_id'));
        abort_unless($schoolId > 0, 403);

        return $schoolId;
    }

    private function authorizeTeaching(int $classId, int $subjectId): void
    {
        $user = Auth::user();
        if ($user->isSuperAdmin() || $user->hasRole(['super-admin', 'school-admin', 'principal', 'deputy-principal'])) {
            return;
        }

        $teacherId = $user->teacher?->id;
        abort_unless($teacherId && TeacherSubjectAllocation::where('school_id', $user->school_id)
            ->where('teacher_id', $teacherId)
            ->where('school_class_id', $classId)
            ->where('subject_id', $subjectId)
            ->exists(), 403);
    }
}
