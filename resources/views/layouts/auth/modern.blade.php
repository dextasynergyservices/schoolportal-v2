<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        @php
            $school = app()->bound('current.school') ? app('current.school') : null;
            $schoolBranding = $school?->settings['branding'] ?? [];
            $brandColor = $schoolBranding['primary_color'] ?? '#000c99';
        @endphp
        <style>
            /* ── Login page animations ── */
            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes pulse-soft {
                0%, 100% { opacity: 0.4; }
                50% { opacity: 0.7; }
            }
            @keyframes shimmer {
                0% { background-position: -200% 0; }
                100% { background-position: 200% 0; }
            }
            .animate-fade-in-up {
                animation: fadeInUp 0.6s ease-out both;
            }
            .animate-fade-in {
                animation: fadeIn 0.8s ease-out both;
            }
            .animate-delay-100 { animation-delay: 0.1s; }
            .animate-delay-200 { animation-delay: 0.2s; }
            .animate-delay-300 { animation-delay: 0.3s; }
            .animate-delay-400 { animation-delay: 0.4s; }

            .login-brand-panel {
                background: {{ $school ? ($schoolBranding['primary_color'] ?? '#000c99') : '#000c99' }};
                position: relative;
                overflow: hidden;
            }
            .login-orb {
                position: absolute;
                border-radius: 50%;
                filter: blur(80px);
                pointer-events: none;
            }
            .login-orb-1 {
                width: 300px;
                height: 300px;
                background: rgba(0, 178, 255, 0.3);
                top: -50px;
                right: -50px;
                animation: pulse-soft 8s ease-in-out infinite;
            }
            .login-orb-2 {
                width: 200px;
                height: 200px;
                background: rgba(0, 12, 153, 0.4);
                bottom: -30px;
                left: -30px;
                animation: pulse-soft 10s ease-in-out infinite 2s;
            }
            .login-grid {
                position: absolute;
                inset: 0;
                background-image:
                    repeating-linear-gradient(0deg, rgba(255,255,255,0.03) 0px, rgba(255,255,255,0.03) 1px, transparent 1px, transparent 40px),
                    repeating-linear-gradient(90deg, rgba(255,255,255,0.03) 0px, rgba(255,255,255,0.03) 1px, transparent 1px, transparent 40px);
                pointer-events: none;
            }
            .login-card {
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
            }
        </style>
    </head>
    <body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
        {{-- Skip to main content (accessibility) --}}
        <a href="#login-form" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:shadow-lg focus:ring-2 focus:ring-blue-500">
            {{ __('Skip to login form') }}
        </a>

        <div class="flex min-h-screen">
            {{-- Left: Brand Panel (hidden on mobile, visible on lg+) --}}
            <div class="login-brand-panel hidden lg:flex lg:w-1/2 xl:w-[55%] flex-col justify-between p-12 xl:p-16">
                <div class="login-grid"></div>
                <div class="login-orb login-orb-1"></div>
                <div class="login-orb login-orb-2"></div>

                {{-- Top: Logo --}}
                <div class="relative z-10 animate-fade-in">
                    @if ($school?->logo_url)
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-white no-underline group">
                            <img src="{{ $school->logo_url }}" alt="{{ $school->name }}" class="h-10 w-10 rounded-xl object-contain bg-white/10 backdrop-blur-sm p-1 transition-transform group-hover:scale-105" />
                            <span class="text-lg font-bold tracking-tight">{{ $school->name }}</span>
                        </a>
                    @elseif ($school)
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-white no-underline group">
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm transition-transform group-hover:scale-105">
                                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                </svg>
                            </div>
                            <span class="text-lg font-bold tracking-tight">{{ $school->name }}</span>
                        </a>
                    @else
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-white no-underline group">
                            <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm transition-transform group-hover:scale-105">
                                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                                </svg>
                            </div>
                            <span class="text-lg font-bold tracking-tight">DX-SchoolPortal</span>
                        </a>
                    @endif
                </div>

                {{-- Center: Tagline + floating cards --}}
                <div class="relative z-10 flex-1 flex flex-col justify-center max-w-lg">
                    @if ($school)
                        <h1 class="text-3xl xl:text-4xl font-extrabold text-white leading-tight mb-4 animate-fade-in-up">
                            {{ __('Welcome to') }}
                            <span style="color: {{ $schoolBranding['secondary_color'] ?? '#00b2ff' }}">{{ $school->name }}</span>
                        </h1>
                        @if ($school->motto)
                            <p class="text-white/60 text-base leading-relaxed mb-8 animate-fade-in-up animate-delay-100">
                                {{ $school->motto }}
                            </p>
                        @endif
                    @else
                        <h1 class="text-3xl xl:text-4xl font-extrabold text-white leading-tight mb-4 animate-fade-in-up">
                            Shaping the future of education,
                            <span class="text-[#00b2ff]">together.</span>
                        </h1>
                        <p class="text-white/60 text-base leading-relaxed mb-8 animate-fade-in-up animate-delay-100">
                            Manage students, deliver results, create AI-powered quizzes, and connect with parents — all from one platform.
                        </p>
                    @endif

                    {{-- Floating preview cards --}}
                    <div class="space-y-3 animate-fade-in-up animate-delay-200">
                        <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-emerald-400/20">
                                <svg class="w-5 h-5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">AI-Powered Quiz Generation</p>
                                <p class="text-xs text-white/50">Generate quizzes from documents in seconds</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-blue-400/20">
                                <svg class="w-5 h-5 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">Real-Time Results & Analytics</p>
                                <p class="text-xs text-white/50">Track student performance instantly</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-xl px-4 py-3 border border-white/10">
                            <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-purple-400/20">
                                <svg class="w-5 h-5 text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">Parent-Teacher Collaboration</p>
                                <p class="text-xs text-white/50">Keep parents in the loop, always</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bottom: Copyright --}}
                <div class="relative z-10 animate-fade-in animate-delay-400">
                    <p class="text-white/30 text-xs">&copy; {{ date('Y') }} {{ $school?->name ?? 'DX-SchoolPortal' }}. {{ __('All rights reserved.') }}</p>
                </div>
            </div>

            {{-- Right: Login Form --}}
            <div class="flex flex-1 flex-col items-center justify-center p-6 sm:p-8 md:p-12 lg:p-16 bg-white dark:bg-zinc-900">
                <div class="w-full max-w-sm" id="login-form">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
