<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GradingScale;
use App\Models\GradingScaleItem;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;

class StudentPerformanceTrends extends Component
{
    public ?int $classId = null;

    public ?int $sessionId = null;

    public ?int $studentId = null;

    public ?string $category = null;

    public function mount(?int $classId = null, ?int $sessionId = null, ?int $studentId = null, ?string $category = null): void
    {
        $this->sessionId = $sessionId ?? AcademicSession::where('is_current', true)->value('id');
        $this->classId = $classId;
        $this->studentId = $studentId;
        $this->category = $category;
    }

    public function updatedClassId(): void
    {
        $this->studentId = null;
    }

    public function updatedSessionId(): void
    {
        // Re-render
    }

    public function updatedStudentId(): void
    {
        // Re-render
    }

    public function updatedCategory(): void
    {
        // Re-render
    }

    public function render(): View
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $classQuery = SchoolClass::where('school_id', $schoolId)
            ->where('is_active', true);
        if ($user->role === 'teacher') {
            $classQuery->where('teacher_id', $user->id);
        }
        $classes = $classQuery->orderBy('sort_order')
            ->get(['id', 'name']);

        $sessions = AcademicSession::where('school_id', $schoolId)
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        // Get students for selected class
        $students = collect();
        if ($this->classId) {
            $students = User::where('school_id', $schoolId)
                ->where('role', 'student')
                ->where('is_active', true)
                ->whereHas('studentProfile', fn ($q) => $q->where('class_id', $this->classId))
                ->orderBy('name')
                ->get(['id', 'name', 'username']);
        }

        $terms = collect();
        if ($this->sessionId) {
            $terms = Term::where('session_id', $this->sessionId)
                ->orderBy('term_number')
                ->get(['id', 'name', 'term_number']);
        }

        $studentTrend = [];
        $subjectBreakdown = [];
        $overallStats = null;

        if ($this->studentId && $this->sessionId && $terms->isNotEmpty()) {
            // Get all published exams for this student's class in the session
            $examQuery = Exam::where('school_id', $schoolId)
                ->where('session_id', $this->sessionId)
                ->where('is_published', true)
                ->whereNotNull('subject_id');

            if ($this->classId) {
                $examQuery->where('class_id', $this->classId);
            }

            if ($this->category) {
                $examQuery->where('category', $this->category);
            }

            $exams = $examQuery->with('subject:id,name,short_name')->get();

            if ($exams->isNotEmpty()) {
                $examIds = $exams->pluck('id');

                // Get this student's best attempts for each exam
                $attempts = ExamAttempt::where('student_id', $this->studentId)
                    ->whereIn('exam_id', $examIds)
                    ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
                    ->whereNotNull('percentage')
                    ->get();

                // Best attempt per exam
                $bestAttempts = $attempts->groupBy('exam_id')->map(fn ($group) => $group->sortByDesc('percentage')->first());

                // Build per-subject, per-term breakdown
                $subjects = $exams->pluck('subject')->unique('id')->sortBy('name');

                foreach ($subjects as $subject) {
                    $subjectExams = $exams->where('subject_id', $subject->id);
                    $termScores = [];

                    foreach ($terms as $term) {
                        $termExams = $subjectExams->where('term_id', $term->id);
                        $termPcts = [];

                        foreach ($termExams as $exam) {
                            if (isset($bestAttempts[$exam->id])) {
                                $termPcts[] = (float) $bestAttempts[$exam->id]->percentage;
                            }
                        }

                        $termScores[] = [
                            'term_name' => $term->name,
                            'average' => count($termPcts) > 0 ? round(array_sum($termPcts) / count($termPcts), 1) : null,
                            'exam_count' => $termExams->count(),
                            'attempted' => count($termPcts),
                        ];
                    }

                    $allScores = array_filter(array_column($termScores, 'average'), fn ($v) => $v !== null);
                    $subjectBreakdown[] = [
                        'subject_name' => $subject->short_name ?: $subject->name,
                        'full_name' => $subject->name,
                        'terms' => $termScores,
                        'overall' => count($allScores) > 0 ? round(array_sum($allScores) / count($allScores), 1) : null,
                        'trend' => $this->calculateTrend($allScores),
                    ];
                }

                // Overall term trend (average across all subjects per term)
                foreach ($terms as $term) {
                    $termExams = $exams->where('term_id', $term->id);
                    $termPcts = [];

                    foreach ($termExams as $exam) {
                        if (isset($bestAttempts[$exam->id])) {
                            $termPcts[] = (float) $bestAttempts[$exam->id]->percentage;
                        }
                    }

                    $studentTrend[] = [
                        'term_name' => $term->name,
                        'average' => count($termPcts) > 0 ? round(array_sum($termPcts) / count($termPcts), 1) : null,
                        'exams_taken' => count($termPcts),
                        'exams_available' => $termExams->count(),
                    ];
                }

                // Overall stats
                $allBestPcts = $bestAttempts->pluck('percentage')->filter();
                $overallStats = [
                    'exams_taken' => $bestAttempts->count(),
                    'exams_available' => $exams->count(),
                    'overall_average' => $allBestPcts->count() > 0 ? round($allBestPcts->avg(), 1) : null,
                    'highest' => $allBestPcts->max(),
                    'lowest' => $allBestPcts->min(),
                    'passed' => $bestAttempts->where('passed', true)->count(),
                    'failed' => $bestAttempts->where('passed', false)->count(),
                ];
            }
        }

        // Sort subject breakdown by overall average descending
        usort($subjectBreakdown, fn ($a, $b) => ($b['overall'] ?? 0) <=> ($a['overall'] ?? 0));

        // Load grading scale items for grade display
        $gradingItems = collect();
        $defaultScale = GradingScale::where('school_id', $schoolId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
        if ($defaultScale) {
            $gradingItems = GradingScaleItem::where('grading_scale_id', $defaultScale->id)
                ->orderByDesc('min_score')
                ->get(['grade', 'label', 'min_score', 'max_score']);
        }

        return view('livewire.admin.student-performance-trends', compact(
            'classes',
            'sessions',
            'students',
            'terms',
            'studentTrend',
            'subjectBreakdown',
            'overallStats',
            'gradingItems',
        ));
    }

    private function calculateTrend(array $values): string
    {
        $filtered = array_values(array_filter($values, fn ($v) => $v !== null));
        if (count($filtered) < 2) {
            return 'stable';
        }

        $last = end($filtered);
        $first = reset($filtered);
        $diff = $last - $first;

        if ($diff > 3) {
            return 'up';
        }
        if ($diff < -3) {
            return 'down';
        }

        return 'stable';
    }
}
