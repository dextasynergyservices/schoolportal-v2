{{--
    CBT/Study Calendar — shows current month with colored dots for upcoming
    exams, quizzes, and assignment deadlines.

    Props:
    $calendarEvents — flat array of events, each:
        ['date' => 'Y-m-d', 'type' => 'exam|quiz|assignment', 'title' => '...', 'route' => '...', 'urgent' => bool, 'taken' => bool]
--}}
@php
    $today = now()->format('Y-m-d');
    $todayDay = (int) now()->format('j');
    $todayMonth = (int) now()->format('n');
    $todayYear = (int) now()->format('Y');

    // Group events by date for quick lookup in Alpine
    $eventsByDate = [];
    foreach ($calendarEvents as $ev) {
        $eventsByDate[$ev['date']][] = $ev;
    }

    // Month name map
    $monthNames = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
@endphp

<div class="dash-panel dash-animate dash-animate-delay-3" style="padding: 0;"
     x-data="studyCalendar({{ Js::from($calendarEvents) }}, {{ $todayYear }}, {{ $todayMonth }}, {{ $todayDay }})"
     aria-label="{{ __('CBT/Study Calendar') }}">

    {{-- Header --}}
    <div class="dash-panel-header">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
            <flux:icon.calendar-days class="w-4 h-4 text-indigo-500" />
            {{ __('CBT/Study Calendar') }}
        </h2>
        {{-- Month nav --}}
        <div class="flex items-center gap-1">
            <button @click="prevMonth()" class="p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-500 dark:text-zinc-400 transition-colors" aria-label="{{ __('Previous month') }}">
                <flux:icon.chevron-left class="w-4 h-4" />
            </button>
            <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 min-w-[90px] text-center" x-text="monthLabel"></span>
            <button @click="nextMonth()" class="p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-500 dark:text-zinc-400 transition-colors" aria-label="{{ __('Next month') }}">
                <flux:icon.chevron-right class="w-4 h-4" />
            </button>
        </div>
    </div>

    <div class="px-3 pb-3">
        {{-- Day of week header --}}
        <div class="grid grid-cols-7 mb-1" role="row">
            @foreach (['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $d)
                <div class="text-center text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 py-1">{{ $d }}</div>
            @endforeach
        </div>

        {{-- Calendar grid --}}
        <div class="grid grid-cols-7 gap-y-0.5" role="grid">
            <template x-for="cell in calendarCells" :key="cell.key">
                <div
                    @click="cell.day && selectDay(cell)"
                    :class="{
                        'cursor-pointer': cell.day,
                        'hover:bg-zinc-100 dark:hover:bg-zinc-700/50': cell.day,
                        'rounded-lg': cell.day,
                    }"
                    class="relative flex flex-col items-center py-1 transition-colors"
                    :aria-label="cell.day ? cell.ariaLabel : undefined"
                    :role="cell.day ? 'gridcell' : undefined"
                >
                    {{-- Day number --}}
                    <span
                        x-show="cell.day"
                        :class="{
                            'bg-indigo-600 text-white ring-2 ring-indigo-300 dark:ring-indigo-700': cell.isToday,
                            'text-zinc-900 dark:text-white': !cell.isToday && !cell.isPast,
                            'text-zinc-300 dark:text-zinc-600': cell.isPast && !cell.isToday,
                            'bg-indigo-50 dark:bg-indigo-900/20': cell.isSelected && !cell.isToday,
                            'font-bold': cell.hasEvents,
                        }"
                        class="w-6 h-6 rounded-full text-xs flex items-center justify-center transition-colors"
                        x-text="cell.day"
                    ></span>

                    {{-- Event dots --}}
                    <div x-show="cell.day && cell.hasEvents" class="flex items-center justify-center gap-0.5 mt-0.5 h-2">
                        <template x-for="dot in cell.dots" :key="dot.type">
                            <span :class="dot.class" class="w-1.5 h-1.5 rounded-full"></span>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Selected day popup --}}
        <div x-show="selectedDay" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="mt-3">
            <template x-if="selectedDay && selectedDay.events.length">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden shadow-sm">
                    <div class="flex items-center justify-between px-3 py-2 border-b border-zinc-100 dark:border-zinc-700">
                        <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300" x-text="selectedDay.label"></span>
                        <button @click="selectedDay = null" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" :aria-label="'{{ __('Close') }}'">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        <template x-for="ev in selectedDay.events" :key="ev.title + ev.type">
                            <a :href="ev.route"
                               class="flex items-center gap-2.5 px-3 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-700/40 transition-colors"
                               :class="{ 'opacity-70': ev.taken }">
                                <span :class="ev.taken ? 'bg-emerald-500' : ev.dotClass" class="w-2 h-2 rounded-full shrink-0"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-zinc-900 dark:text-white truncate" :class="{ 'line-through text-zinc-400 dark:text-zinc-500': ev.taken }" x-text="ev.title"></p>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 capitalize" x-text="ev.typeLabel"></p>
                                </div>
                                <span x-show="ev.taken" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">✓ {{ __('Taken') }}</span>
                                <span x-show="!ev.taken && ev.urgent" class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400">{{ __('Due Today') }}</span>
                                <svg x-show="!ev.taken" class="w-3.5 h-3.5 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Legend --}}
        <div class="flex items-center gap-3 mt-3 px-1">
            <span class="flex items-center gap-1 text-[10px] text-zinc-500 dark:text-zinc-400">
                <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>{{ __('CBT/Exam') }}
            </span>
            <span class="flex items-center gap-1 text-[10px] text-zinc-500 dark:text-zinc-400">
                <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>{{ __('Quiz') }}
            </span>
            <span class="flex items-center gap-1 text-[10px] text-zinc-500 dark:text-zinc-400">
                <span class="w-2 h-2 rounded-full bg-purple-500 shrink-0"></span>{{ __('Assignment') }}
            </span>
        </div>
    </div>
</div>

@once
    @push('scripts')
    <script>
    function studyCalendar(events, todayYear, todayMonth, todayDay) {
        const MONTH_NAMES = [
            'January','February','March','April','May','June',
            'July','August','September','October','November','December'
        ];
        const TYPE_DOTS = {
            exam:       'bg-indigo-500',
            assessment: 'bg-indigo-400',
            assignment: 'bg-purple-500',
            quiz:       'bg-amber-500',
        };
        const TYPE_LABELS = {
            exam: 'CBT Exam',
            assessment: 'CBT Assessment',
            assignment: 'Assignment',
            quiz: 'Quiz',
        };

        // Group events by 'YYYY-MM-DD'
        const eventMap = {};
        for (const ev of events) {
            if (!eventMap[ev.date]) eventMap[ev.date] = [];
            ev.dotClass = ev.taken ? 'bg-emerald-500 ring-1 ring-emerald-300' : (TYPE_DOTS[ev.type] || 'bg-zinc-400');
            ev.typeLabel = TYPE_LABELS[ev.type] || ev.type;
            eventMap[ev.date].push(ev);
        }

        return {
            year: todayYear,
            month: todayMonth,   // 1-based
            todayYear,
            todayMonth,
            todayDay,
            selectedDay: null,
            eventMap,

            get monthLabel() {
                return MONTH_NAMES[this.month - 1] + ' ' + this.year;
            },

            get calendarCells() {
                // First day of month (0=Sun)
                const firstDow = new Date(this.year, this.month - 1, 1).getDay();
                const daysInMonth = new Date(this.year, this.month, 0).getDate();
                const cells = [];

                // Leading blanks
                for (let i = 0; i < firstDow; i++) {
                    cells.push({ key: 'blank-' + i, day: 0, hasEvents: false });
                }

                for (let d = 1; d <= daysInMonth; d++) {
                    const mm = String(this.month).padStart(2, '0');
                    const dd = String(d).padStart(2, '0');
                    const dateStr = `${this.year}-${mm}-${dd}`;
                    const evs = this.eventMap[dateStr] || [];
                    const isToday = d === this.todayDay && this.month === this.todayMonth && this.year === this.todayYear;
                    const isPast = new Date(this.year, this.month - 1, d) < new Date(this.todayYear, this.todayMonth - 1, this.todayDay);

                    // Unique dot types (max 3 dots); taken events use emerald dot
                    const seenTypes = new Set();
                    const dots = [];
                    for (const ev of evs) {
                        const key = (ev.type === 'assessment' ? 'exam' : ev.type) + (ev.taken ? '_taken' : '');
                        if (!seenTypes.has(key)) {
                            seenTypes.add(key);
                            dots.push({ type: key, class: ev.taken ? 'bg-emerald-500' : (TYPE_DOTS[ev.type] || 'bg-zinc-400') });
                        }
                        if (dots.length >= 3) break;
                    }

                    cells.push({
                        key: dateStr,
                        day: d,
                        dateStr,
                        isToday,
                        isPast,
                        hasEvents: evs.length > 0,
                        events: evs,
                        dots,
                        isSelected: this.selectedDay?.dateStr === dateStr,
                        ariaLabel: `${d} ${MONTH_NAMES[this.month - 1]}${evs.length ? ', ' + evs.length + ' item' + (evs.length > 1 ? 's' : '') : ''}`,
                    });
                }

                return cells;
            },

            prevMonth() {
                this.selectedDay = null;
                if (this.month === 1) { this.month = 12; this.year--; }
                else { this.month--; }
            },
            nextMonth() {
                this.selectedDay = null;
                if (this.month === 12) { this.month = 1; this.year++; }
                else { this.month++; }
            },
            selectDay(cell) {
                if (!cell.day || !cell.hasEvents) { this.selectedDay = null; return; }
                if (this.selectedDay?.dateStr === cell.dateStr) { this.selectedDay = null; return; }
                const d = String(cell.day).padStart(2, '0');
                const m = String(this.month).padStart(2, '0');
                this.selectedDay = {
                    ...cell,
                    label: `${MONTH_NAMES[this.month - 1]} ${cell.day}, ${this.year}`,
                };
            },
        };
    }
    </script>
    @endpush
@endonce
