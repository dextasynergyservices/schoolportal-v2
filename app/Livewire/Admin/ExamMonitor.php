<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Jobs\GradeExamAttempt;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class ExamMonitor extends Component
{
    public Exam $exam;

    /** Number of tab switches that marks a student as suspicious. */
    public int $maxTabSwitchWarning = 3;

    public function mount(Exam $exam): void
    {
        $this->exam = $exam->load(['class:id,name', 'subject:id,name']);
        $this->maxTabSwitchWarning = $exam->max_tab_switches ?? 3;
    }

    /**
     * Force-end the exam for a single in-progress student.
     * Marks the attempt as timed_out and dispatches grading.
     */
    public function forceEnd(int $studentId): void
    {
        $attempt = ExamAttempt::where('exam_id', $this->exam->id)
            ->where('student_id', $studentId)
            ->where('status', 'in_progress')
            ->first();

        if (! $attempt) {
            return;
        }

        DB::transaction(function () use ($attempt): void {
            $elapsed = $this->exam->time_limit_minutes
                ? $this->exam->time_limit_minutes * 60
                : (int) $attempt->started_at->diffInSeconds(now());

            $attempt->update([
                'submitted_at' => now(),
                'time_spent_seconds' => $elapsed,
                'status' => 'timed_out',
            ]);
        });

        GradeExamAttempt::dispatch($attempt);
    }

    /**
     * Force-end the exam for ALL currently in-progress students.
     */
    public function forceEndAll(): void
    {
        $attempts = ExamAttempt::where('exam_id', $this->exam->id)
            ->where('status', 'in_progress')
            ->get();

        foreach ($attempts as $attempt) {
            DB::transaction(function () use ($attempt): void {
                $elapsed = $this->exam->time_limit_minutes
                    ? $this->exam->time_limit_minutes * 60
                    : (int) $attempt->started_at->diffInSeconds(now());

                $attempt->update([
                    'submitted_at' => now(),
                    'time_spent_seconds' => $elapsed,
                    'status' => 'timed_out',
                ]);
            });

            GradeExamAttempt::dispatch($attempt);
        }
    }

    public function render(): View
    {
        $classStudents = StudentProfile::where('class_id', $this->exam->class_id)
            ->with('user:id,name,username')
            ->get()
            ->pluck('user')
            ->filter();

        $attempts = ExamAttempt::where('exam_id', $this->exam->id)
            ->with('student:id,name,username')
            ->get()
            ->keyBy('student_id');

        $students = $classStudents->map(function ($student) use ($attempts) {
            $attempt = $attempts->get($student->id);

            return (object) [
                'id' => $student->id,
                'name' => $student->name,
                'username' => $student->username,
                'status' => $this->resolveStatus($attempt),
                'status_color' => $this->resolveStatusColor($attempt),
                'started_at' => $attempt?->started_at,
                'submitted_at' => $attempt?->submitted_at,
                'time_spent' => $attempt?->time_spent_seconds,
                'score' => $attempt?->score,
                'total_points' => $attempt?->total_points,
                'percentage' => $attempt?->percentage,
                'passed' => $attempt?->passed,
                'tab_switches' => $attempt?->tab_switches ?? 0,
                'answered_count' => $attempt ? $attempt->answers()->whereNotNull('selected_answer')->count() : 0,
                'attempt_status' => $attempt?->status,
                'flagged' => ($attempt?->tab_switches ?? 0) > $this->maxTabSwitchWarning,
            ];
        })->sortBy('name')->values();

        $summary = [
            'total' => $students->count(),
            'not_started' => $students->where('status', 'Not Started')->count(),
            'in_progress' => $students->where('status', 'In Progress')->count(),
            'submitted' => $students->whereIn('status', ['Submitted', 'Timed Out', 'Grading', 'Graded'])->count(),
        ];

        return view('livewire.admin.exam-monitor', compact('students', 'summary'));
    }

    private function resolveStatus(?ExamAttempt $attempt): string
    {
        if (! $attempt) {
            return 'Not Started';
        }

        return match ($attempt->status) {
            'in_progress' => 'In Progress',
            'submitted' => 'Submitted',
            'timed_out' => 'Timed Out',
            'grading' => 'Grading',
            'graded' => 'Graded',
            default => ucfirst($attempt->status),
        };
    }

    private function resolveStatusColor(?ExamAttempt $attempt): string
    {
        if (! $attempt) {
            return 'zinc';
        }

        return match ($attempt->status) {
            'in_progress' => 'amber',
            'submitted' => 'green',
            'timed_out' => 'orange',
            'grading' => 'blue',
            'graded' => 'indigo',
            default => 'zinc',
        };
    }
}
