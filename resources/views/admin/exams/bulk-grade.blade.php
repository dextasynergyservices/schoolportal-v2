<x-layouts::app :title="__('Grade Theory')">
    <livewire:admin.theory-grader
        :exam-id="$exam->id"
        :role="$role"
        mode="by_question"
    />
</x-layouts::app>
