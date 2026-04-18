@props([
    'name' => 'password',
    'label' => null,
    'required' => false,
    'autocomplete' => 'new-password',
    'withStrengthMeter' => true,
    'placeholder' => null,
    'description' => null,
])

@php
    $label ??= __('Password');
    $inputId = $attributes->get('id', $name);
    $labelBadge = $required ? __('Required') : null;
@endphp

<div
    x-data="{{ $withStrengthMeter ? 'passwordStrength()' : '{}' }}"
    @if ($withStrengthMeter) x-modelable="value" x-on:input="evaluate($event.target.value)" @endif
    class="space-y-2"
>
    <flux:field>
        <flux:label :badge="$labelBadge">{{ $label }}</flux:label>

        @if ($description)
            <flux:description>{{ $description }}</flux:description>
        @endif

        <flux:input
            type="password"
            viewable
            name="{{ $name }}"
            id="{{ $inputId }}"
            autocomplete="{{ $autocomplete }}"
            :placeholder="$placeholder"
            :required="$required"
            {{ $attributes->except(['id']) }}
        />

        <flux:error name="{{ $name }}" />
    </flux:field>

    @if ($withStrengthMeter)
        <div class="space-y-1.5" x-cloak x-show="value.length > 0">
            <div class="flex items-center gap-2">
                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                    <div
                        class="h-full transition-all duration-200"
                        :class="barClass"
                        :style="`width: ${percent}%`"
                    ></div>
                </div>
                <span class="text-xs font-medium tabular-nums" :class="labelClass" x-text="label"></span>
            </div>

            <ul class="grid grid-cols-2 gap-x-3 gap-y-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                <li class="flex items-center gap-1.5" :class="{ 'text-emerald-600 dark:text-emerald-400': checks.length }">
                    <span x-text="checks.length ? '✓' : '•'" class="font-bold"></span>
                    {{ __('At least 8 characters') }}
                </li>
                <li class="flex items-center gap-1.5" :class="{ 'text-emerald-600 dark:text-emerald-400': checks.upper }">
                    <span x-text="checks.upper ? '✓' : '•'" class="font-bold"></span>
                    {{ __('Uppercase letter') }}
                </li>
                <li class="flex items-center gap-1.5" :class="{ 'text-emerald-600 dark:text-emerald-400': checks.lower }">
                    <span x-text="checks.lower ? '✓' : '•'" class="font-bold"></span>
                    {{ __('Lowercase letter') }}
                </li>
                <li class="flex items-center gap-1.5" :class="{ 'text-emerald-600 dark:text-emerald-400': checks.number }">
                    <span x-text="checks.number ? '✓' : '•'" class="font-bold"></span>
                    {{ __('Number') }}
                </li>
                <li class="flex items-center gap-1.5 col-span-2" :class="{ 'text-emerald-600 dark:text-emerald-400': checks.symbol }">
                    <span x-text="checks.symbol ? '✓' : '•'" class="font-bold"></span>
                    {{ __('Special character (e.g. !@#$%)') }}
                </li>
            </ul>
        </div>
    @endif
</div>

@once
    @if ($withStrengthMeter)
        @push('scripts')
            <script>
                window.passwordStrength = function () {
                    return {
                        value: '',
                        checks: { length: false, upper: false, lower: false, number: false, symbol: false },
                        score: 0,
                        percent: 0,
                        label: '',
                        barClass: 'bg-zinc-300',
                        labelClass: 'text-zinc-500',
                        evaluate(value) {
                            this.value = value || '';
                            const v = this.value;
                            this.checks = {
                                length: v.length >= 8,
                                upper: /[A-Z]/.test(v),
                                lower: /[a-z]/.test(v),
                                number: /\d/.test(v),
                                symbol: /[^A-Za-z0-9]/.test(v),
                            };
                            this.score = Object.values(this.checks).filter(Boolean).length;
                            const map = {
                                0: { pct: 0,   label: '',              bar: 'bg-zinc-300',   txt: 'text-zinc-500' },
                                1: { pct: 20,  label: 'Very weak',      bar: 'bg-red-500',    txt: 'text-red-600 dark:text-red-400' },
                                2: { pct: 40,  label: 'Weak',           bar: 'bg-orange-500', txt: 'text-orange-600 dark:text-orange-400' },
                                3: { pct: 60,  label: 'Fair',           bar: 'bg-amber-500',  txt: 'text-amber-600 dark:text-amber-400' },
                                4: { pct: 80,  label: 'Good',           bar: 'bg-lime-500',   txt: 'text-lime-600 dark:text-lime-400' },
                                5: { pct: 100, label: 'Strong',         bar: 'bg-emerald-500', txt: 'text-emerald-600 dark:text-emerald-400' },
                            };
                            const m = map[this.score];
                            this.percent = m.pct;
                            this.label = m.label;
                            this.barClass = m.bar;
                            this.labelClass = m.txt;
                        },
                    };
                };
            </script>
        @endpush
    @endif
@endonce
