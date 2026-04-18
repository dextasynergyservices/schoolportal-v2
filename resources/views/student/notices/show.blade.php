<x-layouts::app :title="$notice->title">
    <div class="space-y-6">
        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2">
            <flux:link href="{{ route('student.notices.index') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">
                {{ __('Notices') }}
            </flux:link>
            <flux:icon.chevron-right class="w-3 h-3 text-zinc-400" />
            <flux:text class="text-sm line-clamp-1">{{ $notice->title }}</flux:text>
        </div>

        <article class="max-w-3xl">
            {{-- Header --}}
            <flux:heading size="xl">{{ $notice->title }}</flux:heading>
            <div class="flex flex-wrap items-center gap-3 mt-2">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $notice->published_at->format('F j, Y') }}
                </flux:text>
                @if ($notice->creator)
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        &mdash; {{ __('By :name', ['name' => $notice->creator->name]) }}
                    </flux:text>
                @endif
                @if ($notice->expires_at)
                    <flux:badge size="sm" color="{{ $notice->expires_at->isPast() ? 'red' : 'zinc' }}">
                        {{ __('Expires :date', ['date' => $notice->expires_at->format('M j, Y')]) }}
                    </flux:badge>
                @endif
            </div>

            {{-- Image --}}
            @if ($notice->image_url)
                <div class="mt-6 rounded-lg overflow-hidden">
                    <img
                        src="{{ $notice->image_url }}"
                        alt="{{ $notice->title }}"
                        class="w-full h-auto max-h-96 object-cover"
                        loading="lazy"
                    />
                </div>
            @endif

            {{-- Content --}}
            <div class="mt-6 prose prose-zinc dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-line">{{ $notice->content }}</div>
        </article>

        {{-- Back link --}}
        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <flux:button variant="subtle" size="sm" icon="arrow-left" href="{{ route('student.notices.index') }}" wire:navigate>
                {{ __('Back to Notices') }}
            </flux:button>
        </div>
    </div>
</x-layouts::app>
