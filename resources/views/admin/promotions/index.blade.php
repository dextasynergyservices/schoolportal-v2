<x-layouts::app :title="__('Student Promotions')">
    <div class="space-y-6">
        <x-admin-header :title="__('Student Promotions')" />

        @if (session('success'))
            <flux:callout variant="success" icon="check-circle">{{ session('success') }}</flux:callout>
        @endif

        {{-- Promotion Form --}}
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6">
            <flux:heading size="sm" class="mb-4">{{ __('Promote Students') }}</flux:heading>
            <form method="POST" action="{{ route('admin.promotions.preview') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:select name="from_class_id" :label="__('From Class')" required>
                        <option value="">{{ __('Select source class...') }}</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }} ({{ $class->level?->name }}) — {{ $class->students_count }} {{ __('students') }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select name="to_class_id" :label="__('To Class')" required>
                        <option value="">{{ __('Select destination class...') }}</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }} ({{ $class->level?->name }})</option>
                        @endforeach
                    </flux:select>

                    <flux:select name="to_session_id" :label="__('For Session')" required>
                        <option value="">{{ __('Select session...') }}</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button type="submit" variant="primary" size="sm">{{ __('Preview Promotion') }}</flux:button>
            </form>
        </div>

        {{-- Recent Promotions --}}
        @if ($recentPromotions->count())
            <div>
                <flux:heading size="sm" class="mb-3">{{ __('Recent Promotions') }}</flux:heading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Student') }}</flux:table.column>
                        <flux:table.column>{{ __('From') }}</flux:table.column>
                        <flux:table.column>{{ __('To') }}</flux:table.column>
                        <flux:table.column>{{ __('Promoted By') }}</flux:table.column>
                        <flux:table.column>{{ __('Date') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($recentPromotions as $promotion)
                            <flux:table.row>
                                <flux:table.cell class="font-medium">{{ $promotion->student?->name ?? '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $promotion->fromClass?->name ?? '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $promotion->toClass?->name ?? '—' }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-500">{{ $promotion->promoter?->name ?? '—' }}</flux:table.cell>
                                <flux:table.cell class="text-zinc-500">{{ $promotion->promoted_at->format('M j, Y') }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </div>
</x-layouts::app>
