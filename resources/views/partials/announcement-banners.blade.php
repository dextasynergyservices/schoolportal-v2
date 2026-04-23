{{-- Announcement banners: platform-wide + school-level --}}
@php
    $user = auth()->user();
    $bannerAnnouncements = collect();

    // 1. Platform announcements (for school_admin: must click "Read" to dismiss)
    if ($user->isSchoolAdmin() && $user->school_id) {
        $platformAnnouncements = \App\Models\PlatformAnnouncement::active()
            ->whereDoesntHave('reads', function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->latest()
            ->get()
            ->map(fn ($a) => (object) [
                'id' => $a->id,
                'title' => $a->title,
                'content' => $a->content,
                'priority' => $a->priority,
                'type' => 'platform',
                'created_at' => $a->created_at,
            ]);

        $bannerAnnouncements = $bannerAnnouncements->merge($platformAnnouncements);
    }

    // 2. School announcements (for teacher/student/parent: dismissible)
    if (in_array($user->role, ['teacher', 'student', 'parent']) && $user->school_id) {
        $schoolAnnouncements = \App\Models\SchoolAnnouncement::where('school_id', $user->school_id)
            ->active()
            ->forRole($user->role)
            ->whereDoesntHave('dismissals', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->latest()
            ->get()
            ->map(fn ($a) => (object) [
                'id' => $a->id,
                'title' => $a->title,
                'content' => $a->content,
                'priority' => $a->priority,
                'type' => 'school',
                'created_at' => $a->created_at,
            ]);

        $bannerAnnouncements = $bannerAnnouncements->merge($schoolAnnouncements);
    }

    // School admin also sees school announcements they created (but as "school" type for their staff)
    // No need — school admin manages them on the announcements page

    $priorityColors = [
        'info' => 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700 text-blue-800 dark:text-blue-200',
        'warning' => 'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200',
        'critical' => 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700 text-red-800 dark:text-red-200',
    ];

    $priorityIcons = [
        'info' => 'information-circle',
        'warning' => 'exclamation-triangle',
        'critical' => 'exclamation-circle',
    ];
@endphp

@if ($bannerAnnouncements->isNotEmpty())
<div class="space-y-3">
@foreach ($bannerAnnouncements as $banner)
    <div class="rounded-lg border p-4 shadow-sm {{ $priorityColors[$banner->priority] ?? $priorityColors['info'] }}"
         x-data="{ show: true }" x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2">
        <div class="flex items-start gap-3">
            <flux:icon :name="$priorityIcons[$banner->priority] ?? 'information-circle'" class="mt-0.5 size-5 shrink-0" />

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold leading-snug">
                    @if ($banner->type === 'platform')
                        <span class="mr-1.5 inline-flex items-center rounded-full bg-indigo-100 dark:bg-indigo-800 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-200 align-middle">{{ __('Platform') }}</span>
                    @endif
                    {{ $banner->title }}
                </p>
                @if ($banner->content)
                    <p class="mt-1 text-sm opacity-85 leading-relaxed">{!! nl2br(e(Str::limit($banner->content, 300))) !!}</p>
                @endif
            </div>

            <div class="shrink-0 ml-2">
                @if ($banner->type === 'platform')
                    {{-- School admin must click "Mark as Read" --}}
                    <form method="POST" action="{{ route('admin.platform-announcement.read', $banner->id) }}">
                        @csrf
                        <flux:button type="submit" size="xs" variant="filled" icon="check">
                            {{ __('Mark as Read') }}
                        </flux:button>
                    </form>
                @else
                    {{-- School announcements can be dismissed --}}
                    <form method="POST" action="{{ route('announcement.dismiss', $banner->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="rounded-full p-1.5 opacity-50 hover:opacity-100 hover:bg-black/5 dark:hover:bg-white/10 transition-all"
                                title="{{ __('Dismiss') }}">
                            <flux:icon name="x-mark" class="size-4" />
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endforeach
</div>
@endif
