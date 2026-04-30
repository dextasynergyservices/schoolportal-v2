@php
    $user = auth()->user();
    $role = $user->role;
@endphp

{{-- Super Admin Navigation --}}
@if ($user->isSuperAdmin())
    <flux:sidebar.group expandable :heading="__('Platform')" icon="globe-alt" :expanded="request()->routeIs('super-admin.dashboard', 'super-admin.schools.*', 'super-admin.credits.*', 'super-admin.announcements.*', 'super-admin.emails.*', 'super-admin.analytics', 'super-admin.analytics.*', 'super-admin.settings.*', 'super-admin.audit-logs.*', 'super-admin.content.*', 'super-admin.system-health')" class="grid">
        <flux:sidebar.item icon="home" :href="route('super-admin.dashboard')" :current="request()->routeIs('super-admin.dashboard')" wire:navigate>
            {{ __('Dashboard') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="building-office-2" :href="route('super-admin.schools.index')" :current="request()->routeIs('super-admin.schools.*')" wire:navigate>
            {{ __('Schools') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="squares-2x2" :href="route('super-admin.content.index')" :current="request()->routeIs('super-admin.content.*')" wire:navigate>
            {{ __('Content') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="sparkles" :href="route('super-admin.credits.index')" :current="request()->routeIs('super-admin.credits.*')" wire:navigate>
            {{ __('AI Credits') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="megaphone" :href="route('super-admin.announcements.index')" :current="request()->routeIs('super-admin.announcements.*')" wire:navigate>
            {{ __('Announcements') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="envelope" :href="route('super-admin.emails.index')" :current="request()->routeIs('super-admin.emails.*')" wire:navigate>
            {{ __('Email Schools') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="chart-bar-square" :href="route('super-admin.analytics')" :current="request()->routeIs('super-admin.analytics', 'super-admin.analytics.*')" wire:navigate>
            {{ __('Analytics') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="clipboard-document-list" :href="route('super-admin.audit-logs.index')" :current="request()->routeIs('super-admin.audit-logs.*')" wire:navigate>
            {{ __('Audit Logs') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="cog-6-tooth" :href="route('super-admin.settings.index')" :current="request()->routeIs('super-admin.settings.*')" wire:navigate>
            {{ __('Platform Settings') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="signal" :href="route('super-admin.system-health')" :current="request()->routeIs('super-admin.system-health')" wire:navigate>
            {{ __('System Health') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('People')" icon="user-group" :expanded="request()->routeIs('super-admin.students.*', 'super-admin.teachers.*', 'super-admin.parents.*')" class="grid">
        <flux:sidebar.item icon="academic-cap" :href="route('super-admin.students.index')" :current="request()->routeIs('super-admin.students.*')" wire:navigate>
            {{ __('Students') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="user-group" :href="route('super-admin.teachers.index')" :current="request()->routeIs('super-admin.teachers.*')" wire:navigate>
            {{ __('Teachers') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="users" :href="route('super-admin.parents.index')" :current="request()->routeIs('super-admin.parents.*')" wire:navigate>
            {{ __('Parents') }}
        </flux:sidebar.item>
    </flux:sidebar.group>
@endif

{{-- School Admin Navigation --}}
@if ($user->isSchoolAdmin())
    <flux:sidebar.group expandable :heading="__('Overview')" icon="building-office" :expanded="request()->routeIs('admin.dashboard', 'admin.analytics', 'admin.insights')" class="grid">
        <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
            {{ __('Dashboard') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="chart-bar-square" :href="route('admin.analytics')" :current="request()->routeIs('admin.analytics', 'admin.insights')" wire:navigate>
            {{ __('Analytics & Insights') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('People')" icon="user-group" :expanded="request()->routeIs('admin.students.*', 'admin.teachers.*', 'admin.parents.*')" class="grid">
        <flux:sidebar.item icon="academic-cap" :href="route('admin.students.index')" :current="request()->routeIs('admin.students.*')" wire:navigate>
            {{ __('Students') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="user-group" :href="route('admin.teachers.index')" :current="request()->routeIs('admin.teachers.*')" wire:navigate>
            {{ __('Teachers') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="users" :href="route('admin.parents.index')" :current="request()->routeIs('admin.parents.*')" wire:navigate>
            {{ __('Parents') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('Academics')" icon="book-open" :expanded="request()->routeIs('admin.classes.*', 'admin.sessions.*', 'admin.subjects.*', 'admin.assignments.*')" class="grid">
        <flux:sidebar.item icon="building-library" :href="route('admin.classes.index')" :current="request()->routeIs('admin.classes.*')" wire:navigate>
            {{ __('Classes') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="book-open" :href="route('admin.subjects.index')" :current="request()->routeIs('admin.subjects.*')" wire:navigate>
            {{ __('Subjects') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="calendar-days" :href="route('admin.sessions.index')" :current="request()->routeIs('admin.sessions.*')" wire:navigate>
            {{ __('Sessions & Terms') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="clipboard-document-list" :href="route('admin.assignments.index')" :current="request()->routeIs('admin.assignments.*')" wire:navigate>
            {{ __('Assignments') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('Scores & Reports')" icon="document-chart-bar" :expanded="request()->routeIs('admin.grading.*', 'admin.scores.*', 'admin.results.*')" class="grid">
        <flux:sidebar.item icon="chart-bar" :href="route('admin.grading.index')" :current="request()->routeIs('admin.grading.*')" wire:navigate>
            {{ __('Grading Setup') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="calculator" :href="route('admin.scores.index')" :current="request()->routeIs('admin.scores.index', 'admin.scores.enter', 'admin.scores.export')" wire:navigate>
            {{ __('Score Entry') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="document-chart-bar" :href="route('admin.scores.reports')" :current="request()->routeIs('admin.scores.reports*', 'admin.scores.generate*')" wire:navigate>
            {{ __('Report Cards') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="document-text" :href="route('admin.results.index')" :current="request()->routeIs('admin.results.*')" wire:navigate>
            {{ __('Uploaded Results') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('CBT & Interactive')" icon="computer-desktop" :expanded="request()->routeIs('admin.exams.*', 'admin.quizzes.*', 'admin.games.*', 'admin.performance.*')" class="grid">
        <flux:sidebar.item icon="computer-desktop" :href="route('admin.exams.index')" :current="request()->routeIs('admin.exams.*')" wire:navigate>
            {{ __('CBT') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="question-mark-circle" :href="route('admin.quizzes.index')" :current="request()->routeIs('admin.quizzes.*')" wire:navigate>
            {{ __('Quizzes') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="puzzle-piece" :href="route('admin.games.index')" :current="request()->routeIs('admin.games.*')" wire:navigate>
            {{ __('Games') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="chart-bar" :href="route('admin.performance.subjects')" :current="request()->routeIs('admin.performance.*')" wire:navigate>
            {{ __('Performance') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('Communication')" icon="megaphone" :expanded="request()->routeIs('admin.notices.*', 'admin.announcements.*')" class="grid">
        <flux:sidebar.item icon="megaphone" :href="route('admin.notices.index')" :current="request()->routeIs('admin.notices.*')" wire:navigate>
            {{ __('Notices') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="signal" :href="route('admin.announcements.index')" :current="request()->routeIs('admin.announcements.*')" wire:navigate>
            {{ __('Announcements') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    @php
        $pendingApprovalCount = \App\Models\TeacherAction::where('status', 'pending')->count();
    @endphp

    <flux:sidebar.group expandable :heading="__('Management')" icon="cog-6-tooth" :expanded="request()->routeIs('admin.approvals.*', 'admin.promotions.*', 'admin.levels.*', 'admin.audit-logs.*', 'admin.credits.*', 'admin.settings.*', 'admin.help', 'admin.students.move')" class="grid">
        <flux:sidebar.item icon="check-badge" :href="route('admin.approvals.index')" :current="request()->routeIs('admin.approvals.*')" :badge="$pendingApprovalCount > 0 ? $pendingApprovalCount : null" badge:color="red" wire:navigate>
            {{ __('Approvals') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="arrow-up-on-square-stack" :href="route('admin.promotions.index')" :current="request()->routeIs('admin.promotions.*')" wire:navigate>
            {{ __('Promotions') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="adjustments-horizontal" :href="route('admin.levels.index')" :current="request()->routeIs('admin.levels.*')" wire:navigate>
            {{ __('School Levels') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="arrows-right-left" :href="route('admin.students.move')" :current="request()->routeIs('admin.students.move')" wire:navigate>
            {{ __('Move Student') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="sparkles" :href="route('admin.credits.index')" :current="request()->routeIs('admin.credits.*')" wire:navigate>
            {{ __('AI Credits') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.settings.index')" :current="request()->routeIs('admin.settings.*')" wire:navigate>
            {{ __('School Settings') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="shield-check" :href="route('admin.audit-logs.index')" :current="request()->routeIs('admin.audit-logs.*')" wire:navigate>
            {{ __('Audit Logs') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="question-mark-circle" :href="route('admin.help')" :current="request()->routeIs('admin.help')" wire:navigate>
            {{ __('Help Guide') }}
        </flux:sidebar.item>
    </flux:sidebar.group>
@endif

{{-- Teacher Navigation --}}
@if ($role === 'teacher')
    <flux:sidebar.group expandable :heading="__('Teaching')" icon="academic-cap" class="grid">
        <flux:sidebar.item icon="home" :href="route('teacher.dashboard')" :current="request()->routeIs('teacher.dashboard')" wire:navigate>
            {{ __('Dashboard') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="academic-cap" :href="route('teacher.students.index')" :current="request()->routeIs('teacher.students.*')" wire:navigate>
            {{ __('My Students') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('Scores & Reports')" icon="document-chart-bar" :expanded="request()->routeIs('teacher.scores.*')" class="grid">
        <flux:sidebar.item icon="calculator" :href="route('teacher.scores.index')" :current="request()->routeIs('teacher.scores.index')" wire:navigate>
            {{ __('Score Entry') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="document-chart-bar" :href="route('teacher.scores.reports')" :current="request()->routeIs('teacher.scores.reports*')" wire:navigate>
            {{ __('Report Cards') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('Content')" icon="document-text" :expanded="request()->routeIs('teacher.results.*', 'teacher.assignments.*')" class="grid">
        <flux:sidebar.item icon="document-text" :href="route('teacher.results.index')" :current="request()->routeIs('teacher.results.*')" wire:navigate>
            {{ __('Uploaded Results') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="clipboard-document-list" :href="route('teacher.assignments.index')" :current="request()->routeIs('teacher.assignments.*')" wire:navigate>
            {{ __('Assignments') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('CBT & Interactive')" icon="computer-desktop" :expanded="request()->routeIs('teacher.exams.*', 'teacher.quizzes.*', 'teacher.games.*', 'teacher.performance.*')" class="grid">
        <flux:sidebar.item icon="computer-desktop" :href="route('teacher.exams.index')" :current="request()->routeIs('teacher.exams.*')" wire:navigate>
            {{ __('CBT') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="question-mark-circle" :href="route('teacher.quizzes.index')" :current="request()->routeIs('teacher.quizzes.*')" wire:navigate>
            {{ __('Quizzes') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="puzzle-piece" :href="route('teacher.games.index')" :current="request()->routeIs('teacher.games.*')" wire:navigate>
            {{ __('Games') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="chart-bar" :href="route('teacher.performance.subjects')" :current="request()->routeIs('teacher.performance.*')" wire:navigate>
            {{ __('Performance') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('Tracking')" icon="clock" :expanded="request()->routeIs('teacher.submissions.*', 'teacher.insights')" class="grid">
        <flux:sidebar.item icon="magnifying-glass" :href="route('teacher.insights')" :current="request()->routeIs('teacher.insights')" wire:navigate>
            {{ __('Student Insights') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="clock" :href="route('teacher.submissions.index')" :current="request()->routeIs('teacher.submissions.*')" wire:navigate>
            {{ __('My Submissions') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('Communications')" icon="chat-bubble-left-right" :expanded="request()->routeIs('teacher.notices.*')" class="grid">
        <flux:sidebar.item icon="megaphone" :href="route('teacher.notices.index')" :current="request()->routeIs('teacher.notices.*')" wire:navigate>
            {{ __('Notices') }}
        </flux:sidebar.item>
    </flux:sidebar.group>
@endif

{{-- Student Navigation --}}
@if ($role === 'student')
    <flux:sidebar.group expandable :heading="__('My Portal')" icon="academic-cap" class="grid">
        <flux:sidebar.item icon="home" :href="route('student.dashboard')" :current="request()->routeIs('student.dashboard')" wire:navigate>
            {{ __('Dashboard') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="user-circle" :href="route('student.profile')" :current="request()->routeIs('student.profile')" wire:navigate>
            {{ __('My Profile') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('Academics')" icon="book-open" :expanded="request()->routeIs('student.results.*', 'student.report-cards.*', 'student.assignments.*')" class="grid">
        <flux:sidebar.item icon="document-chart-bar" :href="route('student.report-cards.index')" :current="request()->routeIs('student.report-cards.index', 'student.report-cards.show', 'student.report-cards.session-summary', 'student.report-cards.cbt-results')" wire:navigate>
            {{ __('Report Cards') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="document-text" :href="route('student.results.index')" :current="request()->routeIs('student.results.*')" wire:navigate>
            {{ __('Uploaded Results') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="clipboard-document-list" :href="route('student.assignments.index')" :current="request()->routeIs('student.assignments.*')" wire:navigate>
            {{ __('Assignments') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('CBT & Interactive')" icon="computer-desktop" :expanded="request()->routeIs('student.exams.*', 'student.quizzes.*', 'student.games.*')" class="grid">
        <flux:sidebar.item icon="computer-desktop" :href="route('student.exams.index')" :current="request()->routeIs('student.exams.*')" wire:navigate>
            {{ __('CBT') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="question-mark-circle" :href="route('student.quizzes.index')" :current="request()->routeIs('student.quizzes.*')" wire:navigate>
            {{ __('Quizzes') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="puzzle-piece" :href="route('student.games.index')" :current="request()->routeIs('student.games.*')" wire:navigate>
            {{ __('Games') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('Communications')" icon="chat-bubble-left-right" :expanded="request()->routeIs('student.notices.*')" class="grid">
        <flux:sidebar.item icon="megaphone" :href="route('student.notices.index')" :current="request()->routeIs('student.notices.*')" wire:navigate>
            {{ __('Notices') }}
        </flux:sidebar.item>
    </flux:sidebar.group>
@endif

{{-- Parent Navigation --}}
@if ($role === 'parent')
    <flux:sidebar.group expandable :heading="__('My Children')" icon="heart" class="grid">
        <flux:sidebar.item icon="home" :href="route('parent.dashboard')" :current="request()->routeIs('parent.dashboard')" wire:navigate>
            {{ __('Dashboard') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('Academics')" icon="book-open" :expanded="request()->routeIs('parent.results*', 'parent.children.report-cards*', 'parent.assignments*', 'parent.children.results*', 'parent.children.assignments*', 'parent.report-cards*')" class="grid">
        <flux:sidebar.item icon="document-chart-bar" :href="route('parent.report-cards.index')" :current="request()->routeIs('parent.report-cards*', 'parent.children.report-cards*')" wire:navigate>
            {{ __('Report Cards') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="document-text" :href="route('parent.results.index')" :current="request()->routeIs('parent.results*', 'parent.children.results*')" wire:navigate>
            {{ __('Uploaded Results') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="clipboard-document-list" :href="route('parent.assignments.index')" :current="request()->routeIs('parent.assignments*', 'parent.children.assignments*')" wire:navigate>
            {{ __('Assignments') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('CBT & Interactive')" icon="computer-desktop" :expanded="request()->routeIs('parent.cbt*', 'parent.children.cbt*', 'parent.quizzes*', 'parent.children.quizzes*', 'parent.games*', 'parent.children.games*')" class="grid">
        @if ($user->school?->setting('portal.enable_cbt_results_for_parents', true))
            <flux:sidebar.item icon="computer-desktop" :href="route('parent.cbt.index')" :current="request()->routeIs('parent.cbt*', 'parent.children.cbt*')" wire:navigate>
                {{ __('CBT Results') }}
            </flux:sidebar.item>
        @endif
        <flux:sidebar.item icon="question-mark-circle" :href="route('parent.quizzes.index')" :current="request()->routeIs('parent.quizzes*', 'parent.children.quizzes*')" wire:navigate>
            {{ __('Quizzes') }}
        </flux:sidebar.item>
        <flux:sidebar.item icon="puzzle-piece" :href="route('parent.games.index')" :current="request()->routeIs('parent.games*', 'parent.children.games*')" wire:navigate>
            {{ __('Games') }}
        </flux:sidebar.item>
    </flux:sidebar.group>

    <flux:sidebar.group expandable :heading="__('Communication')" icon="megaphone" :expanded="request()->routeIs('parent.notices.*')" class="grid">
        <flux:sidebar.item icon="megaphone" :href="route('parent.notices.index')" :current="request()->routeIs('parent.notices.*')" wire:navigate>
            {{ __('Notices') }}
        </flux:sidebar.item>
    </flux:sidebar.group>
@endif
