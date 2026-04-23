<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6 animate-fade-in-up">
        {{-- School branding --}}
        @php
            $school = app()->bound('current.school') ? app('current.school') : null;
        @endphp

        {{-- Mobile-only platform logo (only on platform domain, hidden when a school is resolved) --}}
        @unless ($school)
            <div class="flex flex-col items-center gap-3 lg:hidden">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 no-underline group">
                    <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-[#000c99] shadow-lg shadow-blue-500/25 transition-transform group-hover:scale-105">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold tracking-tight text-zinc-900 dark:text-white">DX-SchoolPortal</span>
                </a>
            </div>
        @endunless

        {{-- School info or default heading --}}
        @if ($school)
            <div class="flex flex-col items-center gap-2 text-center">
                @if ($school->logo_url)
                    <img src="{{ $school->logo_url }}" alt="{{ $school->name }} logo" class="object-contain w-16 h-16 rounded-xl shadow-md">
                @endif
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white">
                    {{ __('Welcome to') }}
                    <span style="color: {{ $school->settings['branding']['primary_color'] ?? '#000c99' }}">{{ $school->name }}</span>
                </h2>
                @if ($school->motto)
                    <p class="text-sm italic text-zinc-500 dark:text-zinc-400">{{ $school->motto }}</p>
                @endif
                <div class="w-12 h-1 rounded-full mt-1" style="background-color: {{ $school->settings['branding']['primary_color'] ?? '#00b2ff' }}"></div>
            </div>
        @else
            <div class="text-center lg:text-left">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Welcome back') }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enter your credentials to access your portal') }}</p>
            </div>
        @endif

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        {{-- General validation errors (e.g. reCAPTCHA, auth failures) --}}
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/50 dark:text-red-400">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            {{-- In local dev without a resolved school, allow school selection --}}
            {{-- School selector: shown on the platform domain when no school is resolved --}}
            @if (! $school)
                @php
                    $schools = \App\Models\School::withoutGlobalScopes()
                        ->where('is_active', true)
                        ->where('slug', '!=', 'platform')
                        ->orderBy('name')
                        ->get(['id', 'name']);
                @endphp
                @if ($schools->isNotEmpty())
                    <div>
                        <label for="school_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('Select Your School') }}</label>
                        <select name="school_id" id="school_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-900 shadow-sm focus:border-[#00b2ff] focus:ring-2 focus:ring-[#00b2ff]/20 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            <option value="">{{ __('— Choose your school —') }}</option>
                            @foreach ($schools as $s)
                                <option value="{{ $s->id }}" @selected(old('school_id', session('school_id')) == $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Super admins can log in without selecting a school.') }}</p>
                    </div>
                @endif
            @endif

            <!-- Username or Email -->
            <flux:input
                name="login"
                :label="__('Username or Email')"
                :value="old('login')"
                type="text"
                required
                autofocus
                autocomplete="username"
                :placeholder="__('Enter your username or email')"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <button type="submit" data-test="login-button" class="w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all dark:focus:ring-offset-zinc-900" style="background-color: {{ $school ? ($school->settings['branding']['primary_color'] ?? '#000c99') : '#000c99' }}" onmouseover="this.style.filter='brightness(0.85)'" onmouseout="this.style.filter='brightness(1)'">
                <span>{{ __('Log in') }}</span>
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </button>

            <x-recaptcha action="login" />
        </form>

        {{-- Mobile-only footer --}}
        <p class="text-center text-xs text-zinc-500 dark:text-zinc-400 lg:hidden mt-2">
            &copy; {{ date('Y') }} DX-SchoolPortal
        </p>
    </div>
</x-layouts::auth>
