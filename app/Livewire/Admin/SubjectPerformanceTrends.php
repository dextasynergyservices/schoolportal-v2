<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AcademicSession;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GradingScale;
use App\Models\GradingScaleItem;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class SubjectPerformanceTrends extends Component
{
    public ?int $classId = null;

    public ?int $sessionId = null;

    public ?string $category = null;

    public function mount(?int $classId = null, ?int $sessionId = null, ?string $category = null): void
    {
        $this->sessionId = $sessionId ?? AcademicSession::where('is_current', true)->value('id');
        $this->classId = $classId;
        $this->category = $category;
    }

    public function updatedClassId(): void
    {
        // Livewire re-renders automatically
    }

    public function updatedSessionId(): void
    {
        // Livewire re-renders automatically
    }

    public function updatedCategory(): void
    {
        // Livewire re-renders automatically
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

        $terms = collect();
        if ($this->sessionId) {
            $terms = Term::where('session_id', $this->sessionId)
                ->orderBy('term_number')
                ->get(['id', 'name', 'term_number']);
        }

        $trendData = [];
        $subjectSummary = [];

        if ($this->classId && $this->sessionId && $terms->isNotEmpty()) {
            // Get all exams for this class/session, optionally filtered by category
            $examQuery = Exam::where('school_id', $schoolId)
                ->where('class_id', $this->classId)
                ->where('session_id', $this->sessionId)
                ->where('is_published', true)
                ->whereNotNull('subject_id');

            if ($this->category) {
                $examQuery->where('category', $this->category);
            }

            $exams = $examQuery->get(['id', 'subject_id', 'term_id', 'title']);

            if ($exams->isNotEmpty()) {
                $examIds = $exams->pluck('id');
                $subjectIds = $exams->pluck('subject_id')->unique();
                $termIds = $terms->pluck('id');

                // Get all graded attempts for these exams
                $attempts = ExamAttempt::whereIn('exam_id', $examIds)
                    ->whereIn('status', ['submitted', 'timed_out', 'grading', 'graded'])
                    ->whereNotNull('percentage')
                    ->select('exam_id', DB::raw('AVG(percentage) as avg_pct'), DB::raw('COUNT(*) as attempt_count'))
                    ->groupBy('exam_id')
                    ->get()
                    ->keyBy('exam_id');

                $subjects = Subject::whereIn('id', $subjectIds)
                    ->orderBy('name')
                    ->get(['id', 'name', 'short_name']);

                // Build trend data: subject → term → average percentage
                foreach ($subjects as $subject) {
                    $subjectExams = $exams->where('subject_id', $subject->id);
                    $termAverages = [];
                    $allPercentages = [];

                    foreach ($terms as $term) {
                        $termExams = $subjectExams->where('term_id', $term->id);
                        $termPcts = [];
                        $termAttemptCount = 0;

                        foreach ($termExams as $exam) {
                            if (isset($attempts[$exam->id])) {
                                $termPcts[] = (float) $attempts[$exam->id]->avg_pct;
                                $termAttemptCount += (int) $attempts[$exam->id]->attempt_count;
                            }
                        }

                        $avg = count($termPcts) > 0 ? round(array_sum($termPcts) / count($termPcts), 1) : null;
                        $termAverages[] = [
                            'term_id' => $term->id,
                            'term_name' => $term->name,
                            'average' => $avg,
                            'exam_count' => $termExams->count(),
                            'attempt_count' => $termAttemptCount,
                        ];
                        if ($avg !== null) {
                            $allPercentages[] = $avg;
                        }
                    }

                    $trendData[] = [
                        'subject_id' => $subject->id,
                        'subject_name' => $subject->short_name ?: $subject->name,
                        'terms' => $termAverages,
                    ];

                    // Summary: overall average, trend direction
                    $overallAvg = count($allPercentages) > 0 ? round(array_sum($allPercentages) / count($allPercentages), 1) : null;
                    $trend = $this->calculateTrend($allPercentages);

                    $subjectSummary[] = [
                        'subject_name' => $subject->name,
                        'short_name' => $subject->short_name,
                        'overall_average' => $overallAvg,
                        'trend' => $trend,
                        'exam_count' => $subjectExams->count(),
                        'term_averages' => collect($termAverages)->pluck('average', 'term_name')->toArray(),
                    ];
                }

                // Sort summary by overall average descending
                usort($subjectSummary, fn ($a, $b) => ($b['overall_average'] ?? 0) <=> ($a['overall_average'] ?? 0));
            }
        }

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

        return view('livewire.admin.subject-performance-trends', compact(
            'classes',
            'sessions',
            'terms',
            'trendData',
            'subjectSummary',
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
