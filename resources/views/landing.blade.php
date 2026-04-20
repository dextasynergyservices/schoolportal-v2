<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DX-SchoolPortal — Modern School Management Platform</title>
    <meta name="description" content="DX-SchoolPortal is a professional multi-tenant school management platform with AI-powered quizzes, educational games, result management, and parent portals.">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:300,400,500,600,700,800,900" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/landing.js'])

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --sp-blue: #00b2ff;
            --sp-blue-alt: #00abff;
            --sp-navy: #000c99;
            --sp-dark: #00075d;
        }

        html { scroll-behavior: smooth; overflow-x: hidden; }

        body {
            font-family: 'Montserrat', sans-serif;
            color: #1a1a2e;
            overflow-x: hidden;
            width: 100%;
        }

        /* ── Navbar ── */
        .sp-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 1rem 0;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .sp-nav.scrolled {
            background: rgba(0, 7, 93, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 0.6rem 0;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.15);
        }

        /* ── Hero ── */
        .hero-section {
            background: var(--sp-navy);
            position: relative;
            overflow: hidden;
        }
        @media (min-width: 1024px) {
            .hero-section {
                min-height: 100vh;
                display: flex;
                align-items: center;
            }
        }
        .hero-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 178, 255, 0.05);
            pointer-events: none;
        }

        /* Floating grid pattern */
        .hero-grid {
            position: absolute;
            inset: 0;
            background-image:
                repeating-linear-gradient(0deg, rgba(255,255,255,0.03) 0px, rgba(255,255,255,0.03) 1px, transparent 1px, transparent 60px),
                repeating-linear-gradient(90deg, rgba(255,255,255,0.03) 0px, rgba(255,255,255,0.03) 1px, transparent 1px, transparent 60px);
            pointer-events: none;
        }

        /* Animated orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            pointer-events: none;
        }
        .orb-1 {
            width: min(500px, 80vw);
            height: min(500px, 80vw);
            background: var(--sp-blue);
            top: -10%;
            right: 0;
            animation: orbFloat1 12s ease-in-out infinite;
        }
        .orb-2 {
            width: min(350px, 60vw);
            height: min(350px, 60vw);
            background: var(--sp-navy);
            bottom: -5%;
            left: 0;
            animation: orbFloat2 15s ease-in-out infinite;
        }
        .orb-3 {
            width: min(200px, 40vw);
            height: min(200px, 40vw);
            background: #00d4ff;
            top: 40%;
            left: 30%;
            animation: orbFloat3 10s ease-in-out infinite;
        }
        @keyframes orbFloat1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(-40px, 30px) scale(1.1); }
        }
        @keyframes orbFloat2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -20px) scale(1.05); }
        }
        @keyframes orbFloat3 {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.3; }
            50% { transform: translate(-20px, -30px) scale(1.2); opacity: 0.5; }
        }

        /* ── Section styling ── */
        .section-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            background: rgba(0, 178, 255, 0.1);
            color: var(--sp-blue);
            border: 1px solid rgba(0, 178, 255, 0.2);
        }

        /* ── Feature cards ── */
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid #e8edf2;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 60px rgba(0, 12, 153, 0.1);
            border-color: var(--sp-blue);
        }
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--sp-blue);
            opacity: 0;
            transition: opacity 0.4s;
        }
        .feature-card:hover::before {
            opacity: 1;
        }
        .feature-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            background: rgba(0, 178, 255, 0.1);
            color: var(--sp-navy);
        }

        /* ── Step cards ── */
        .step-card {
            position: relative;
            padding: 2rem;
            border-radius: 20px;
            background: white;
            border: 1px solid #e8edf2;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .step-card:hover {
            box-shadow: 0 20px 50px rgba(0, 12, 153, 0.08);
            border-color: var(--sp-blue);
        }
        .step-number {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.25rem;
            background: var(--sp-navy);
            color: white;
            margin-bottom: 1.25rem;
        }

        /* ── Stat cards ── */
        .stat-card {
            text-align: center;
            padding: 2rem;
        }
        .stat-number {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--sp-blue);
            line-height: 1.1;
        }

        /* ── CTA Section ── */
        .cta-section {
            background: var(--sp-navy);
            position: relative;
            overflow: hidden;
        }
        .cta-section::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 178, 255, 0.05);
            pointer-events: none;
        }

        /* ── Buttons ── */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            background: var(--sp-blue);
            color: white;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 178, 255, 0.35);
        }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.25);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            text-decoration: none;
            backdrop-filter: blur(10px);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.4);
            transform: translateY(-2px);
        }
        .btn-dark {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            background: var(--sp-dark);
            color: white;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-dark:hover {
            background: var(--sp-navy);
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 7, 93, 0.35);
        }

        /* ── Role cards ── */
        .role-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 2rem;
            backdrop-filter: blur(10px);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .role-card:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--sp-blue);
            transform: translateY(-6px);
        }

        /* ── Footer ── */
        .footer-link {
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            transition: color 0.3s;
            font-size: 0.9rem;
        }
        .footer-link:hover {
            color: var(--sp-blue);
        }

        /* ── Reveal animation base ── */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
        }
        .reveal-left {
            opacity: 0;
            transform: translateX(-40px);
        }
        .reveal-right {
            opacity: 0;
            transform: translateX(40px);
        }
        .reveal-scale {
            opacity: 0;
            transform: scale(0.9);
        }

        /* ── Mobile menu ── */
        .mobile-menu {
            position: fixed;
            inset: 0;
            z-index: 99;
            background: rgba(0, 7, 93, 0.98);
            backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            gap: 1rem;
            padding: 5rem 1.5rem 2rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s;
        }
        .mobile-menu.active {
            opacity: 1;
            pointer-events: all;
        }
        .mobile-menu a {
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s;
        }
        .mobile-menu a:hover { color: var(--sp-blue); }

        /* ── Hamburger ── */
        .hamburger {
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            z-index: 101;
            background: none;
            border: none;
            padding: 4px;
        }
        .hamburger span {
            display: block;
            width: 24px;
            height: 2px;
            background: white;
            border-radius: 2px;
            transition: all 0.3s;
        }
        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* ── Smooth counter ── */
        .counter { display: inline-block; }

        /* ── Marquee for trust band ── */
        .marquee-track {
            display: flex;
            gap: 3rem;
            animation: marquee 30s linear infinite;
        }
        @keyframes marquee {
            from { transform: translateX(0); }
            to { transform: translateX(-50%); }
        }

        /* Gradient text utility */
        .gradient-text {
            color: var(--sp-blue);
        }

        /* ── Responsive improvements ── */
        @media (min-width: 640px) {
            .stat-number { font-size: 3rem; }
        }
        @media (max-width: 639px) {
            .feature-card { padding: 1.75rem 1.5rem; }
            .step-card { padding: 1.5rem; }
            .role-card { padding: 1.5rem; }
            .stat-card { padding: 1rem; }
        }
    </style>
</head>
<body>
<div x-data style="overflow-x: hidden; width: 100%; max-width: 100vw;">

    {{-- ══════════════════════════════════════════════════════
         NAVIGATION
    ══════════════════════════════════════════════════════ --}}
    <nav class="sp-nav" id="navbar">
        <div class="mx-auto max-w-7xl px-6 flex items-center justify-between">
            {{-- Logo --}}
            <a href="#" class="flex items-center gap-2.5 text-white no-underline">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight hidden sm:inline">DX-SchoolPortal</span>
            </a>

            {{-- Desktop nav links --}}
            <div class="hidden lg:flex items-center gap-8">
                <a href="#features" class="text-white/70 hover:text-white text-sm font-medium no-underline transition-colors">Features</a>
                <a href="#how-it-works" class="text-white/70 hover:text-white text-sm font-medium no-underline transition-colors">How It Works</a>
                <a href="#roles" class="text-white/70 hover:text-white text-sm font-medium no-underline transition-colors">Who It's For</a>
                <a href="#ai" class="text-white/70 hover:text-white text-sm font-medium no-underline transition-colors">AI Tools</a>
                <a href="https://wa.me/2348103208297" target="_blank" rel="noopener noreferrer" class="text-white/70 hover:text-white text-sm font-medium no-underline transition-colors">Contact</a>
            </div>

            {{-- Desktop CTA --}}
            <div class="hidden lg:flex items-center gap-3">
                <a href="{{ route('login') }}" class="btn-secondary !py-2.5 !px-5 !text-sm">Sign In</a>
            </div>

            {{-- Mobile/Tablet hamburger --}}
            <button class="hamburger flex lg:hidden" id="hamburger" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </nav>

    {{-- Mobile menu overlay --}}
    <div class="mobile-menu" id="mobileMenu">
        <a href="#features" onclick="closeMobileMenu()">Features</a>
        <a href="#how-it-works" onclick="closeMobileMenu()">How It Works</a>
        <a href="#roles" onclick="closeMobileMenu()">Who It's For</a>
        <a href="#ai" onclick="closeMobileMenu()">AI Tools</a>
        <a href="https://wa.me/2348103208297" target="_blank" rel="noopener noreferrer" onclick="closeMobileMenu()">Contact</a>
        <div class="mt-2 flex flex-col gap-3">
            <a href="https://wa.me/2348103208297" target="_blank" rel="noopener noreferrer" class="btn-primary" onclick="closeMobileMenu()">
                Get Started
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
            </a>
            <a href="{{ route('login') }}" class="btn-secondary" onclick="closeMobileMenu()">Sign In</a>
        </div>
    </div>


    {{-- ══════════════════════════════════════════════════════
         HERO SECTION
    ══════════════════════════════════════════════════════ --}}
    <section class="hero-section" id="hero">
        <div class="hero-grid"></div>
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <div class="relative mx-auto max-w-7xl px-5 sm:px-6 pt-20 pb-8 sm:pt-24 sm:pb-12 md:pt-32 md:pb-16 lg:pt-40 lg:pb-20 w-full">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                {{-- Left: Text --}}
                <div class="hero-content">
                    <div class="section-label mb-6" style="background: rgba(255,255,255,0.1); color: white; border-color: rgba(255,255,255,0.2);">
                        <span class="inline-block w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                        Now Available
                    </div>
                    <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-6">
                        Shaping the future<br>
                        of education,
                        <span class="gradient-text">together.</span>
                    </h1>
                    <p class="text-base sm:text-lg text-white/70 max-w-lg mb-8 sm:mb-10 leading-relaxed">
                        A professional multi-tenant school management platform with AI-powered quizzes, real-time results, educational games, and seamless parent-teacher collaboration.
                    </p>
                    <div class="flex flex-col sm:flex-row flex-wrap gap-3 sm:gap-4">
                        <button type="button" x-on:click="$dispatch('open-video-modal')" class="btn-primary justify-center">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                            Learn More
                        </button>
                        <a href="https://wa.me/2348103208297" target="_blank" rel="noopener noreferrer" class="btn-secondary justify-center">
                            Get Started
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                        </a>
                    </div>
                </div>

                {{-- Right: Visual --}}
                <div class="hero-visual hidden lg:flex justify-center">
                    <div class="relative w-full max-w-md">
                        {{-- Main card --}}
                        <div class="bg-white/10 backdrop-blur-xl rounded-3xl border border-white/20 p-8 shadow-2xl">
                            <div class="flex items-center gap-3 mb-6">
                                <div class="w-10 h-10 rounded-full bg-[#000c99] flex items-center justify-center text-white text-sm font-bold">A</div>
                                <div>
                                    <div class="text-white font-semibold text-sm">Admin Dashboard</div>
                                    <div class="text-white/50 text-xs">2025/2026 Session</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mb-6">
                                <div class="bg-white/10 rounded-xl p-4 text-center">
                                    <div class="text-2xl font-bold text-white">245</div>
                                    <div class="text-white/50 text-xs mt-1">Students</div>
                                </div>
                                <div class="bg-white/10 rounded-xl p-4 text-center">
                                    <div class="text-2xl font-bold text-white">12</div>
                                    <div class="text-white/50 text-xs mt-1">Classes</div>
                                </div>
                                <div class="bg-white/10 rounded-xl p-4 text-center">
                                    <div class="text-2xl font-bold text-white">8</div>
                                    <div class="text-white/50 text-xs mt-1">Teachers</div>
                                </div>
                                <div class="bg-white/10 rounded-xl p-4 text-center">
                                    <div class="text-2xl font-bold text-white">96%</div>
                                    <div class="text-white/50 text-xs mt-1">Pass Rate</div>
                                </div>
                            </div>
                            <div class="bg-white/5 rounded-xl p-4">
                                <div class="text-white/50 text-xs mb-3 font-medium">Pending Approvals</div>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-yellow-400"></div>
                                        <span class="text-white/80 text-xs">Result upload by Teacher Amina</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-blue-400"></div>
                                        <span class="text-white/80 text-xs">Assignment by Teacher Bola</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Floating badge --}}
                        <div class="absolute -top-4 -right-4 bg-white rounded-2xl shadow-xl px-4 py-3 flex items-center gap-2 hero-float-card">
                            <div class="w-8 h-8 rounded-lg bg-green-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-900">Results Published</div>
                                <div class="text-xs text-gray-500">Term 1, 2025/2026</div>
                            </div>
                        </div>

                        {{-- Floating AI card --}}
                        <div class="absolute -bottom-4 -left-4 bg-white rounded-2xl shadow-xl px-4 py-3 flex items-center gap-2 hero-float-card-2">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z"/></svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-gray-900">AI Quiz Generated</div>
                                <div class="text-xs text-gray-500">10 questions ready</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- ══════════════════════════════════════════════════════
         STATS BAR
    ══════════════════════════════════════════════════════ --}}
    <section class="py-10 sm:py-16 bg-gray-50 border-b border-gray-100">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="stat-card reveal">
                    <div class="stat-number counter" data-target="500">0</div>
                    <div class="text-sm text-gray-500 font-medium mt-2">Students Managed</div>
                </div>
                <div class="stat-card reveal">
                    <div class="stat-number counter" data-target="50">0</div>
                    <div class="text-sm text-gray-500 font-medium mt-2">Classes Created</div>
                </div>
                <div class="stat-card reveal">
                    <div class="stat-number counter" data-target="1000">0</div>
                    <div class="text-sm text-gray-500 font-medium mt-2">Results Delivered</div>
                </div>
                <div class="stat-card reveal">
                    <div class="stat-number"><span class="counter" data-target="99">0</span>%</div>
                    <div class="text-sm text-gray-500 font-medium mt-2">Uptime Guaranteed</div>
                </div>
            </div>
        </div>
    </section>


    {{-- ══════════════════════════════════════════════════════
         FEATURES
    ══════════════════════════════════════════════════════ --}}
    <section class="py-14 sm:py-24 bg-white" id="features">
        <div class="mx-auto max-w-7xl px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-16">
                <div class="section-label mx-auto mb-4 reveal">Features</div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4 reveal">
                    Everything your school needs,<br>
                    <span style="color: var(--sp-navy);">in one platform.</span>
                </h2>
                <p class="text-gray-500 leading-relaxed reveal">
                    From student enrollment to AI-powered quizzes, DX-SchoolPortal streamlines every aspect of school management with a professional, mobile-first experience.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- Feature 1 --}}
                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Student Management</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Enroll students, manage profiles, bulk import via CSV, and track academic journeys across sessions and terms.</p>
                </div>

                {{-- Feature 2 --}}
                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Result & Assignment Delivery</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Upload results as PDFs, manage assignments by week and term, with secure signed URLs for private access.</p>
                </div>

                {{-- Feature 3 --}}
                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">AI-Powered Quizzes</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Generate quizzes from documents or prompts using Google Gemini AI. Students take interactive, timed quizzes online.</p>
                </div>

                {{-- Feature 4 --}}
                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 0 1-.657.643 48.491 48.491 0 0 1-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 0 1-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 0 0-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 0 1-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 0 0 .657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 0 1-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 0 0 5.427-.63 48.05 48.05 0 0 0 .582-4.717.532.532 0 0 0-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 0 0 .658-.663 48.422 48.422 0 0 0-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 0 1-.61-.58Z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Educational Games</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Memory match, word scramble, quiz race, and flashcards. AI-generated or manually created to reinforce learning.</p>
                </div>

                {{-- Feature 5 --}}
                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Parent Portal</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Parents access their children's results, assignments, quiz scores, and school notices from a dedicated dashboard.</p>
                </div>

                {{-- Feature 6 --}}
                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Teacher Approval Workflow</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Teachers upload content that flows through admin approval. Full audit trail with transparency at every step.</p>
                </div>
            </div>

            {{-- Watch Video CTA --}}
            <div class="mt-10 sm:mt-14 text-center reveal">
                <button
                    type="button"
                    x-on:click="$dispatch('open-video-modal')"
                    class="group inline-flex items-center gap-3 px-6 py-3.5 rounded-full bg-[var(--sp-navy)] text-white font-semibold text-sm shadow-lg shadow-[var(--sp-navy)]/20 hover:shadow-xl hover:shadow-[var(--sp-navy)]/30 hover:-translate-y-0.5 transition-all duration-300"
                >
                    <span class="flex items-center justify-center w-10 h-10 rounded-full bg-white/15 group-hover:bg-white/25 transition-colors">
                        <svg class="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                    Watch How It Works
                    <svg class="w-4 h-4 opacity-50 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </button>
            </div>
        </div>
    </section>


    {{-- ══════════════════════════════════════════════════════
         HOW IT WORKS
    ══════════════════════════════════════════════════════ --}}
    <section class="py-14 sm:py-24 bg-gray-50" id="how-it-works">
        <div class="mx-auto max-w-7xl px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-16">
                <div class="section-label mx-auto mb-4 reveal">How It Works</div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4 reveal">
                    Get started in <span style="color: var(--sp-navy);">3 simple steps</span>
                </h2>
                <p class="text-gray-500 leading-relaxed reveal">
                    Onboard your school in under 60 seconds with our guided setup wizard.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="step-card reveal">
                    <div class="step-number">1</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Register Your School</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Enter your school details, upload your logo, and configure your custom domain. Your portal is ready instantly.</p>
                </div>
                <div class="step-card reveal">
                    <div class="step-number">2</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Set Up Classes & Users</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Add school levels, classes, teachers, and students. Import students in bulk via CSV or add them one by one.</p>
                </div>
                <div class="step-card reveal">
                    <div class="step-number">3</div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Go Live</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Start uploading results, creating AI quizzes, posting notices, and inviting parents. Your school is fully digital.</p>
                </div>
            </div>
        </div>
    </section>


    {{-- ══════════════════════════════════════════════════════
         WHO IT'S FOR (Roles Section)
    ══════════════════════════════════════════════════════ --}}
    <section class="cta-section py-14 sm:py-24" id="roles">
        <div class="relative mx-auto max-w-7xl px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-16">
                <div class="section-label mx-auto mb-4 reveal" style="background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.9); border-color: rgba(255,255,255,0.15);">Who It's For</div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4 reveal">
                    Built for every role in<br>
                    <span class="gradient-text">your school.</span>
                </h2>
                <p class="text-white/60 leading-relaxed reveal">
                    Dedicated dashboards and tools for every user — admins, teachers, students, and parents.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="role-card reveal">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/15 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75"/></svg>
                    </div>
                    <h3 class="text-white font-bold mb-2">School Admins</h3>
                    <p class="text-white/50 text-sm leading-relaxed">Full control over school settings, users, classes, approvals, AI credits, and analytics.</p>
                </div>
                <div class="role-card reveal">
                    <div class="w-12 h-12 rounded-xl bg-green-500/15 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                    </div>
                    <h3 class="text-white font-bold mb-2">Teachers</h3>
                    <p class="text-white/50 text-sm leading-relaxed">Upload results, create AI quizzes and games, manage assignments — all within their assigned classes.</p>
                </div>
                <div class="role-card reveal">
                    <div class="w-12 h-12 rounded-xl bg-yellow-500/15 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>
                    </div>
                    <h3 class="text-white font-bold mb-2">Students</h3>
                    <p class="text-white/50 text-sm leading-relaxed">View results, take quizzes, play educational games, and access assignments from a mobile-first dashboard.</p>
                </div>
                <div class="role-card reveal">
                    <div class="w-12 h-12 rounded-xl bg-pink-500/15 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-pink-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                    </div>
                    <h3 class="text-white font-bold mb-2">Parents</h3>
                    <p class="text-white/50 text-sm leading-relaxed">Monitor all linked children's progress — results, quiz scores, game stats, and school notices in one place.</p>
                </div>
            </div>
        </div>
    </section>


    {{-- ══════════════════════════════════════════════════════
         AI SECTION
    ══════════════════════════════════════════════════════ --}}
    <section class="py-14 sm:py-24 bg-white" id="ai">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
                <div>
                    <div class="section-label mb-4 reveal-left">AI-Powered</div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-6 reveal-left">
                        Intelligence that<br>
                        <span style="color: var(--sp-navy);">transforms teaching.</span>
                    </h2>
                    <p class="text-gray-500 leading-relaxed mb-8 reveal-left">
                        Powered by Google Gemini AI, teachers can generate quizzes and educational games from any document or simple prompt in seconds. Each school gets 15 free AI credits monthly.
                    </p>
                    <div class="space-y-4">
                        <div class="flex items-start gap-4 reveal-left">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5" style="color: var(--sp-navy);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 mb-1">From Document</h4>
                                <p class="text-sm text-gray-500">Upload a PDF, DOCX, or image of lesson notes and AI generates quiz questions instantly.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 reveal-left">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5" style="color: var(--sp-navy);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 mb-1">From Prompt</h4>
                                <p class="text-sm text-gray-500">Simply describe what you want: "10 questions on photosynthesis for Primary 5" — and it's done.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4 reveal-left">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-5 h-5" style="color: var(--sp-navy);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/></svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 mb-1">Review & Edit</h4>
                                <p class="text-sm text-gray-500">Every AI-generated question is fully editable. Teachers retain complete control before publishing.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- AI Visual --}}
                <div class="reveal-right">
                    <div class="relative">
                        <div class="bg-blue-50 rounded-3xl p-8 border border-gray-100">
                            <div class="bg-white rounded-2xl shadow-lg p-6 mb-4">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: var(--sp-navy);">
                                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                                    </div>
                                    <span class="font-bold text-sm text-gray-900">AI Quiz Generator</span>
                                </div>
                                <div class="space-y-3">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <div class="text-xs text-gray-400 mb-1">Q1. Multiple Choice</div>
                                        <div class="text-sm font-medium text-gray-800">What is the primary pigment in photosynthesis?</div>
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            <div class="text-xs bg-white rounded-lg px-3 py-1.5 text-gray-600 border">A. Melanin</div>
                                            <div class="text-xs rounded-lg px-3 py-1.5 text-white font-medium" style="background: var(--sp-navy);">B. Chlorophyll ✓</div>
                                            <div class="text-xs bg-white rounded-lg px-3 py-1.5 text-gray-600 border">C. Hemoglobin</div>
                                            <div class="text-xs bg-white rounded-lg px-3 py-1.5 text-gray-600 border">D. Keratin</div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <div class="text-xs text-gray-400 mb-1">Q2. True / False</div>
                                        <div class="text-sm font-medium text-gray-800">Photosynthesis occurs only in leaves.</div>
                                        <div class="mt-2 flex gap-2">
                                            <div class="text-xs bg-white rounded-lg px-4 py-1.5 text-gray-600 border">True</div>
                                            <div class="text-xs rounded-lg px-4 py-1.5 text-white font-medium" style="background: var(--sp-navy);">False ✓</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center text-xs text-gray-400 font-medium">
                                10 questions generated in 3.2 seconds · 1 AI credit used
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- ══════════════════════════════════════════════════════
         MULTI-TENANT
    ══════════════════════════════════════════════════════ --}}
    <section class="py-14 sm:py-24 bg-gray-50">
        <div class="mx-auto max-w-7xl px-6">
            <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-16">
                <div class="section-label mx-auto mb-4 reveal">Multi-Tenant</div>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-4 reveal">
                    One platform,<br>
                    <span style="color: var(--sp-navy);">unlimited schools.</span>
                </h2>
                <p class="text-gray-500 leading-relaxed reveal">
                    Each school gets its own branded portal on its own custom domain. Complete data isolation, personalized themes, and independent management.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Custom Domains</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Each school uses its own domain. pearschool.com/portal — not a subdomain of our platform.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Per-School Branding</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">Custom colors, logos, mottos. Each school's portal feels like their own app, not a generic platform.</p>
                </div>
                <div class="feature-card reveal">
                    <div class="feature-icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Complete Data Isolation</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">School A never sees School B's data. Global scopes enforce tenant isolation at every query.</p>
                </div>
            </div>
        </div>
    </section>


    {{-- ══════════════════════════════════════════════════════
         CTA SECTION
    ══════════════════════════════════════════════════════ --}}
    <section class="cta-section py-14 sm:py-24" id="cta">
        <div class="relative mx-auto max-w-4xl px-6 text-center">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white mb-6 reveal-scale">
                Ready to transform<br>your school?
            </h2>
            <p class="text-white/60 text-lg max-w-xl mx-auto mb-10 reveal-scale">
                Join schools already using DX-SchoolPortal to deliver results, create AI quizzes, and connect with parents — all from one platform.
            </p>
            <div class="flex flex-col sm:flex-row flex-wrap justify-center gap-4 reveal-scale">
                <a href="https://wa.me/2348103208297" target="_blank" rel="noopener noreferrer" class="btn-primary !py-4 !px-8 !text-base justify-center">
                    Get Started Now
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                </a>
                <a href="https://wa.me/2348103208297" target="_blank" rel="noopener noreferrer" class="btn-secondary !py-4 !px-8 !text-base justify-center">
                    Contact Us
                </a>
            </div>
        </div>
    </section>


    {{-- ══════════════════════════════════════════════════════
         CONTACT / FOOTER
    ══════════════════════════════════════════════════════ --}}
    <footer class="py-16" style="background: var(--sp-dark);" id="contact">
        <div class="mx-auto max-w-7xl px-6">
            <div class="grid md:grid-cols-4 gap-12 mb-12">
                {{-- Brand --}}
                <div class="md:col-span-2">
                    <div class="flex items-center gap-2.5 mb-4">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10">
                            <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-white tracking-tight">DX-SchoolPortal</span>
                    </div>
                    <p class="text-white/50 text-sm leading-relaxed max-w-sm">
                        A professional multi-tenant school management platform with AI-powered learning tools, built for Nigerian schools and beyond.
                    </p>
                </div>

                {{-- Links --}}
                <div>
                    <h4 class="text-white font-bold text-sm mb-4">Platform</h4>
                    <div class="space-y-2.5">
                        <a href="#features" class="footer-link block">Features</a>
                        <a href="#how-it-works" class="footer-link block">How It Works</a>
                        <a href="#ai" class="footer-link block">AI Tools</a>
                        <a href="#roles" class="footer-link block">Who It's For</a>
                    </div>
                </div>

                {{-- Contact --}}
                <div>
                    <h4 class="text-white font-bold text-sm mb-4">Contact</h4>
                    <div class="space-y-2.5">
                        <a href="https://wa.me/2348103208297" target="_blank" rel="noopener noreferrer" class="footer-link flex items-center gap-2">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            WhatsApp
                        </a>
                        <a href="mailto:hello@schoolportal.ng" class="footer-link block">hello@schoolportal.ng</a>
                        <a href="{{ route('login') }}" class="footer-link block">Sign In</a>
                    </div>
                </div>
            </div>

            {{-- Bottom bar --}}
            <div class="border-t border-white/10 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                <p class="text-white/40 text-sm">&copy; {{ date('Y') }} DX-SchoolPortal. All rights reserved.</p>
                <div class="flex items-center gap-6">
                    <a href="#" class="text-white/40 hover:text-white/70 text-sm transition-colors">Privacy Policy</a>
                    <a href="#" class="text-white/40 hover:text-white/70 text-sm transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

</div>

{{-- Video Modal (reusable) --}}
@include('partials.video-modal', ['videoUrl' => 'https://www.youtube.com/embed/dQw4w9WgXcQ'])

</body>
</html>
