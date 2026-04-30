<x-layouts::app :title="__('Edit ' . $categoryLabel)">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Edit ' . $categoryLabel . ': :name', ['name' => $exam->title])"
            :description="__('Update and resubmit for admin approval.')"
        />

        @if (session('error'))
            <flux:callout variant="danger" icon="x-circle">{{ session('error') }}</flux:callout>
        @endif

        @if ($exam->latestTeacherAction?->status === 'rejected' && $exam->latestTeacherAction?->rejection_reason)
            <flux:callout variant="danger" icon="x-circle">
                <strong>{{ __('Reason for rejection:') }}</strong> {{ $exam->latestTeacherAction->rejection_reason }}
            </flux:callout>
        @endif

        @include('admin.exams._manual-form', [
            'classes' => $classes,
            'subjects' => $subjects,
            'scoreComponents' => $scoreComponents,
            'questions' => $exam->questions->sortBy('sort_order')->map(fn ($q) => [
                'type' => $q->type,
                'question_text' => $q->question_text,
                'options' => $q->options ?? [],
                'correct_answer' => $q->correct_answer,
                'marking_guide' => $q->marking_guide,
                'sample_answer' => $q->sample_answer,
                'min_words' => $q->min_words,
                'max_words' => $q->max_words,
                'explanation' => $q->explanation,
                'points' => $q->points,
                'section_label' => $q->section_label,
            ])->values()->toArray(),
            'exam' => $exam,
            'updateRoute' => route($routePrefix . '.update', $exam),
            'indexRoute' => route($routePrefix . '.index'),
            'routePrefix' => $routePrefix,
        ])
    </div>
</x-layouts::app>
