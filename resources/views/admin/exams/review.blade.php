<x-layouts::app :title="__('Review Generated Questions')">
    <div class="space-y-6">
        <x-admin-header
            :title="__('Review AI-Generated Questions')"
            :description="__('Review, edit, and finalize the questions before saving.')"
        />

        <flux:callout variant="info" icon="sparkles">
            {{ __('AI generated :count questions. Review each question carefully — you can edit, add, remove, or reorder them.', ['count' => count($questions)]) }}
        </flux:callout>

        @include('admin.exams._manual-form', [
            'classes' => $classes,
            'subjects' => $subjects,
            'scoreComponents' => $scoreComponents,
            'currentSession' => $currentSession,
            'currentTerm' => $currentTerm,
            'questions' => $questions,
            'selectedClassId' => $selectedClassId,
            'selectedSubjectId' => $selectedSubjectId,
            'selectedScoreComponentId' => $selectedScoreComponentId,
            'sourceType' => $sourceType,
            'sourcePrompt' => $sourcePrompt,
            'sourceDocumentUrl' => $sourceDocumentUrl,
            'sourceDocumentPublicId' => $sourceDocumentPublicId,
            'difficulty' => $difficulty,
        ])
    </div>
</x-layouts::app>
