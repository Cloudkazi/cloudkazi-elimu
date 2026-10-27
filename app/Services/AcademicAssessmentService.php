<?php

namespace App\Services;

use App\Models\CbcAssessment;
use App\Models\Examination;
use App\Models\ExamMark844;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentReportSummary;
use Illuminate\Support\Facades\DB;

class AcademicAssessmentService
{
    /**
     * Compute 8-4-4 Grade and KCSE points from raw percentage score
     */
    public function get844GradeAndPoints(float $score): array
    {
        if ($score >= 80) return ['grade' => 'A',  'points' => 12, 'remarks' => 'Excellent'];
        if ($score >= 75) return ['grade' => 'A-', 'points' => 11, 'remarks' => 'Very Good'];
        if ($score >= 70) return ['grade' => 'B+', 'points' => 10, 'remarks' => 'Good'];
        if ($score >= 65) return ['grade' => 'B',  'points' => 9,  'remarks' => 'Fairly Good'];
        if ($score >= 60) return ['grade' => 'B-', 'points' => 8,  'remarks' => 'Above Average'];
        if ($score >= 55) return ['grade' => 'C+', 'points' => 7,  'remarks' => 'Average'];
        if ($score >= 50) return ['grade' => 'C',  'points' => 6,  'remarks' => 'Satisfactory'];
        if ($score >= 45) return ['grade' => 'C-', 'points' => 5,  'remarks' => 'Pass'];
        if ($score >= 40) return ['grade' => 'D+', 'points' => 4,  'remarks' => 'Fair'];
        if ($score >= 35) return ['grade' => 'D',  'points' => 3,  'remarks' => 'Weak'];
        if ($score >= 30) return ['grade' => 'D-', 'points' => 2,  'remarks' => 'Very Weak'];
        return ['grade' => 'E', 'points' => 1, 'remarks' => 'Needs Improvement'];
    }

    /**
     * Compute Mean Grade from Average Points (1 to 12)
     */
    public function getMeanGradeFromPoints(float $points): string
    {
        if ($points >= 11.5) return 'A';
        if ($points >= 10.5) return 'A-';
        if ($points >= 9.5)  return 'B+';
        if ($points >= 8.5)  return 'B';
        if ($points >= 7.5)  return 'B-';
        if ($points >= 6.5)  return 'C+';
        if ($points >= 5.5)  return 'C';
        if ($points >= 4.5)  return 'C-';
        if ($points >= 3.5)  return 'D+';
        if ($points >= 2.5)  return 'D';
        if ($points >= 1.5)  return 'D-';
        return 'E';
    }

    /**
     * Compute Examination Summaries & Ranks for a class/stream.
     *
     * Marks are de-duplicated using the following rule:
     *  - If a mark record has a subject_offering_id, that is the canonical identity.
     *    Only one mark per offering is used (highest score wins on conflict).
     *  - If no subject_offering_id exists (legacy records), fall back to subject_id.
     *
     * This eliminates the "194/300 Mathematics" problem where the same subject
     * was counted multiple times due to ambiguous teacher allocations.
     */
    public function computeExamSummaries(int $examinationId, int $classId): void
    {
        $exam     = Examination::findOrFail($examinationId);
        $schoolId = $exam->school_id;

        $students = Student::where('school_id', $schoolId)
            ->where('school_class_id', $classId)
            ->whereIn('status', ['active', 'alumni', 'enrolled'])
            ->get();

        foreach ($students as $student) {
            // Fetch all raw marks for this student in this exam
            $rawMarks = ExamMark844::where('examination_id', $examinationId)
                ->where('student_id', $student->id)
                ->get();

            // De-duplicate by offering identity:
            // Key = "offering_{id}" when subject_offering_id is set, else "subject_{id}"
            // Within each group keep the highest score if duplicates exist.
            $deduped = [];
            foreach ($rawMarks as $mark) {
                $key = $mark->subject_offering_id
                    ? 'offering_' . $mark->subject_offering_id
                    : 'subject_'  . $mark->subject_id;

                if (!isset($deduped[$key]) || $mark->score > $deduped[$key]->score) {
                    $deduped[$key] = $mark;
                }
            }

            $dedupedMarks = collect(array_values($deduped));

            $totalScore   = $dedupedMarks->sum('score');
            $subjectCount = $dedupedMarks->count();
            $totalPoints  = $dedupedMarks->sum('points');
            $meanScore    = $subjectCount > 0 ? round($totalScore / $subjectCount, 2) : 0;
            $meanPoints   = $subjectCount > 0 ? round($totalPoints / $subjectCount, 2) : 0;
            $meanGrade    = $this->getMeanGradeFromPoints($meanPoints);

            // CBC Pathway recommendation if Grade 9 / Junior School
            $pathway = null;
            if ($student->curriculum === 'CBC') {
                $pathway = $this->calculateCbcPathwayRecommendation($student->id, $examinationId);
            }

            StudentReportSummary::updateOrCreate([
                'school_id'      => $schoolId,
                'examination_id' => $examinationId,
                'student_id'     => $student->id,
            ], [
                'school_class_id'        => $classId,
                'stream_id'              => $student->stream_id,
                'total_score'            => $totalScore,
                'mean_score'             => $meanScore,
                'mean_grade'             => $meanGrade,
                'mean_points'            => $meanPoints,
                'pathway_recommendation' => $pathway,
                'class_teacher_remarks'  => $this->generateRemarks($meanGrade),
                'principal_remarks'      => $this->generatePrincipalRemarks($meanGrade),
            ]);
        }

        // Calculate Class Ranks (sorted by total_score desc)
        $ranked = StudentReportSummary::where('examination_id', $examinationId)
            ->where('school_class_id', $classId)
            ->orderBy('total_score', 'desc')
            ->get();

        $rank = 1;
        foreach ($ranked as $item) {
            $item->update(['class_rank' => $rank++]);
        }

        // Calculate Stream Ranks
        $streams = Stream::where('school_class_id', $classId)->get();
        foreach ($streams as $stream) {
            $streamRanked = StudentReportSummary::where('examination_id', $examinationId)
                ->where('stream_id', $stream->id)
                ->orderBy('total_score', 'desc')
                ->get();

            $sRank = 1;
            foreach ($streamRanked as $sItem) {
                $sItem->update(['stream_rank' => $sRank++]);
            }
        }
    }

    /**
     * Compute CBC Pathway Recommendation for Grade 9 Learners
     */
    public function calculateCbcPathwayRecommendation(int $studentId, int $examinationId): string
    {
        $assessments = CbcAssessment::where('student_id', $studentId)
            ->with('subject')
            ->get();

        if ($assessments->isEmpty()) {
            return 'STEM';
        }

        $stemScore = 0;
        $socialScore = 0;
        $artsScore = 0;

        foreach ($assessments as $assess) {
            $subjectName = strtolower($assess->subject->name ?? '');
            $points = $assess->score_points; // 4=EE, 3=ME, 2=AE, 1=BE

            if (str_contains($subjectName, 'math') || str_contains($subjectName, 'scien') || str_contains($subjectName, 'comput') || str_contains($subjectName, 'agri')) {
                $stemScore += $points;
            } elseif (str_contains($subjectName, 'social') || str_contains($subjectName, 'history') || str_contains($subjectName, 'religio') || str_contains($subjectName, 'geograph')) {
                $socialScore += $points;
            } else {
                $artsScore += $points;
            }
        }

        if ($stemScore >= $socialScore && $stemScore >= $artsScore) {
            return 'STEM (Science, Technology, Engineering & Mathematics)';
        } elseif ($socialScore >= $artsScore) {
            return 'Social Sciences & Humanities';
        }

        return 'Arts & Sports Science';
    }

    private function generateRemarks(string $grade): string
    {
        return match ($grade) {
            'A', 'A-' => 'Exemplary academic achievement. Keep up the high standards!',
            'B+', 'B', 'B-' => 'Commendable performance with steady progress. Aim higher next term.',
            'C+', 'C', 'C-' => 'Satisfactory effort shown, but has greater potential with focused revision.',
            'D+', 'D', 'D-' => 'Below target. Needs dedicated remedial support and extra study hours.',
            default => 'Urgent academic intervention and parent consultation required.',
        };
    }

    private function generatePrincipalRemarks(string $grade): string
    {
        return match ($grade) {
            'A', 'A-' => 'Outstanding results. Highly commended for discipline and diligence.',
            'B+', 'B', 'B-' => 'Good work. Demonstrated consistent intellectual curiosity.',
            'C+', 'C' => 'A fair performance with room for meaningful improvement.',
            default => 'Encouraged to redouble efforts and seek academic guidance.',
        };
    }
}
