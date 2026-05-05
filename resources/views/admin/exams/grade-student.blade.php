<x-layouts::app :title="__('Grade Theory')">
    <livewire:admin.theory-grader
        :exam-id="$exam->id"
        :role="$role"
        mode="by_student"
        :attempt-id="$attempt->id"
    />
</x-layouts::app>
