{{-- Session/Term filter selector for dashboards --}}
@props(['route', 'allSessions', 'sessionTerms', 'currentSession', 'currentTerm'])

@if ($allSessions->isNotEmpty())
    <div
        x-data="{
            sessionId: '{{ $currentSession?->id ?? '' }}',
            termId: '{{ $currentTerm?->id ?? '' }}',
            navigate(newSessionId = null, newTermId = null) {
                const sid = newSessionId ?? this.sessionId;
                const tid = newTermId ?? this.termId;
                if (!sid) return;
                const url = new URL('{{ route($route) }}', window.location.origin);
                url.searchParams.set('session_id', sid);
                if (tid) url.searchParams.set('term_id', tid);
                window.location.href = url.toString();
            },
            onSessionChange() {
                this.navigate(this.sessionId, null);
            },
            onTermChange() {
                this.navigate();
            }
        }"
        class="flex flex-wrap items-center gap-3"
    >
        <div class="flex items-center gap-2">
            <label for="session-filter" class="text-xs font-medium text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ __('Session:') }}</label>
            <select
                id="session-filter"
                x-model="sessionId"
                x-on:change="onSessionChange()"
                class="rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-700 dark:text-zinc-300 py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-primary/50 focus:border-primary"
            >
                @foreach ($allSessions as $session)
                    <option value="{{ $session->id }}">
                        {{ $session->name }}
                        @if ($session->is_current) ({{ __('current') }}) @endif
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2">
            <label for="term-filter" class="text-xs font-medium text-zinc-500 dark:text-zinc-400 whitespace-nowrap">{{ __('Term:') }}</label>
            <select
                id="term-filter"
                x-model="termId"
                x-on:change="onTermChange()"
                class="rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-sm text-zinc-700 dark:text-zinc-300 py-1.5 pl-3 pr-8 focus:ring-2 focus:ring-primary/50 focus:border-primary"
            >
                @foreach ($sessionTerms as $term)
                    <option value="{{ $term->id }}" @selected($currentTerm?->id === $term->id)>
                        {{ $term->name }}
                    </option>
                @endforeach
            </select>
        </div>

        @if (request()->has('session_id'))
            <a href="{{ route($route) }}" class="text-xs text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 underline decoration-dotted">
                {{ __('Reset to current') }}
            </a>
        @endif
    </div>
@endif
