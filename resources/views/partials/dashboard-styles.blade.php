{{-- Dashboard shared styles - included by all dashboard views --}}
<style>
    /* ── Animations ── */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes countUp {
        from { opacity: 0; transform: scale(0.8); }
        to { opacity: 1; transform: scale(1); }
    }
    @keyframes slideInRight {
        from { opacity: 0; transform: translateX(16px); }
        to { opacity: 1; transform: translateX(0); }
    }
    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    @keyframes pulse-ring {
        0% { transform: scale(0.8); opacity: 0.5; }
        80%, 100% { transform: scale(2); opacity: 0; }
    }

    .dash-animate {
        animation: fadeInUp 0.5s ease-out both;
    }
    .dash-animate-delay-1 { animation-delay: 0.05s; }
    .dash-animate-delay-2 { animation-delay: 0.1s; }
    .dash-animate-delay-3 { animation-delay: 0.15s; }
    .dash-animate-delay-4 { animation-delay: 0.2s; }
    .dash-animate-delay-5 { animation-delay: 0.25s; }
    .dash-animate-delay-6 { animation-delay: 0.3s; }
    .dash-animate-delay-7 { animation-delay: 0.35s; }
    .dash-animate-fade {
        animation: fadeIn 0.6s ease-out both;
    }
    .dash-animate-slide {
        animation: slideInRight 0.5s ease-out both;
    }

    /* ── Stat Cards ── */
    .stat-card {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        padding: 1.25rem;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        filter: blur(40px);
        opacity: 0.15;
        pointer-events: none;
    }
    .stat-card-blue { background: #eff6ff; border: 1px solid #bfdbfe; }
    .stat-card-blue::before { background: #3b82f6; }
    .stat-card-emerald { background: #ecfdf5; border: 1px solid #a7f3d0; }
    .stat-card-emerald::before { background: #10b981; }
    .stat-card-purple { background: #faf5ff; border: 1px solid #d8b4fe; }
    .stat-card-purple::before { background: #a855f7; }
    .stat-card-amber { background: #fffbeb; border: 1px solid #fde68a; }
    .stat-card-amber::before { background: #f59e0b; }
    .stat-card-indigo { background: #eef2ff; border: 1px solid #c7d2fe; }
    .stat-card-indigo::before { background: #6366f1; }
    .stat-card-cyan { background: #ecfeff; border: 1px solid #a5f3fc; }
    .stat-card-cyan::before { background: #06b6d4; }
    .stat-card-teal { background: #f0fdfa; border: 1px solid #99f6e4; }
    .stat-card-teal::before { background: #14b8a6; }
    .stat-card-pink { background: #fdf2f8; border: 1px solid #fbcfe8; }
    .stat-card-pink::before { background: #ec4899; }
    .stat-card-rose { background: #fff1f2; border: 1px solid #fecdd3; }
    .stat-card-rose::before { background: #f43f5e; }

    /* Dark mode stat cards */
    :is(.dark .stat-card-blue) { background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.2); }
    :is(.dark .stat-card-emerald) { background: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.2); }
    :is(.dark .stat-card-purple) { background: rgba(168, 85, 247, 0.08); border-color: rgba(168, 85, 247, 0.2); }
    :is(.dark .stat-card-amber) { background: rgba(245, 158, 11, 0.08); border-color: rgba(245, 158, 11, 0.2); }
    :is(.dark .stat-card-indigo) { background: rgba(99, 102, 241, 0.08); border-color: rgba(99, 102, 241, 0.2); }
    :is(.dark .stat-card-cyan) { background: rgba(6, 182, 212, 0.08); border-color: rgba(6, 182, 212, 0.2); }
    :is(.dark .stat-card-teal) { background: rgba(20, 184, 166, 0.08); border-color: rgba(20, 184, 166, 0.2); }
    :is(.dark .stat-card-pink) { background: rgba(236, 72, 153, 0.08); border-color: rgba(236, 72, 153, 0.2); }
    :is(.dark .stat-card-rose) { background: rgba(244, 63, 94, 0.08); border-color: rgba(244, 63, 94, 0.2); }

    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.025em;
    }

    /* ── Dashboard Panels ── */
    .dash-panel {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
        transition: box-shadow 0.3s ease;
    }
    .dash-panel:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    }
    :is(.dark .dash-panel) {
        background: rgb(39 39 42);
        border-color: rgb(63 63 70);
    }
    .dash-panel-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    :is(.dark .dash-panel-header) {
        border-color: rgb(63 63 70);
    }
    .dash-panel-body {
        padding: 1.25rem;
    }

    /* ── Activity timeline ── */
    .activity-item {
        display: flex;
        gap: 0.75rem;
        padding: 0.875rem 1.25rem;
        transition: background 0.2s;
    }
    .activity-item:hover {
        background: rgba(0, 0, 0, 0.02);
    }
    :is(.dark .activity-item:hover) {
        background: rgba(255, 255, 255, 0.02);
    }
    .activity-dot {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }

    /* ── Progress bars ── */
    .progress-track {
        width: 100%;
        height: 8px;
        border-radius: 999px;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        border-radius: 999px;
        transition: width 1s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* ── Quick action button ── */
    .quick-action {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem 0.75rem;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: white;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        text-decoration: none;
        color: inherit;
        cursor: pointer;
        min-width: 0;
    }
    .quick-action:hover {
        border-color: #3b82f6;
        background: #eff6ff;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.1);
    }
    :is(.dark .quick-action) {
        border-color: rgb(63 63 70);
        background: rgb(39 39 42);
    }
    :is(.dark .quick-action:hover) {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, 0.1);
    }
    .quick-action-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .quick-action:hover .quick-action-icon {
        transform: scale(1.12);
    }

    /* ── Alert banners ── */
    .dash-alert {
        border-radius: 12px;
        padding: 0.875rem 1rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .dash-alert-amber {
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    :is(.dark .dash-alert-amber) {
        background: rgba(245, 158, 11, 0.1);
        border-color: rgba(245, 158, 11, 0.2);
    }
    .dash-alert-red {
        background: #fef2f2;
        border: 1px solid #fecaca;
    }
    :is(.dark .dash-alert-red) {
        background: rgba(239, 68, 68, 0.1);
        border-color: rgba(239, 68, 68, 0.2);
    }
    .dash-alert-blue {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    :is(.dark .dash-alert-blue) {
        background: rgba(59, 130, 246, 0.1);
        border-color: rgba(59, 130, 246, 0.2);
    }

    /* ── Welcome header ── */
    .dash-welcome {
        background: #000c99;
        border-radius: 16px;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
    }
    /* Role-themed welcome banners */
    .dash-welcome-admin   { background: #312e81; }
    .dash-welcome-teacher { background: #134e4a; }
    .dash-welcome-student { background: #1e3a5f; }
    .dash-welcome-parent  { background: #14532d; }
    .dash-welcome-super   { background: #000c99; }
    .dash-welcome::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        filter: blur(60px);
        pointer-events: none;
    }
    /* Role-themed glow orbs */
    .dash-welcome::before                  { background: rgba(0, 178, 255, 0.15); }
    .dash-welcome-admin::before            { background: rgba(129, 140, 248, 0.2); }
    .dash-welcome-teacher::before          { background: rgba(45, 212, 191, 0.2); }
    .dash-welcome-student::before          { background: rgba(96, 165, 250, 0.2); }
    .dash-welcome-parent::before           { background: rgba(74, 222, 128, 0.2); }
    .dash-welcome::after {
        content: '';
        position: absolute;
        bottom: -50%;
        left: -10%;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        filter: blur(50px);
        pointer-events: none;
    }
    .dash-welcome::after                   { background: rgba(0, 12, 153, 0.3); }
    .dash-welcome-admin::after             { background: rgba(99, 102, 241, 0.2); }
    .dash-welcome-teacher::after           { background: rgba(20, 184, 166, 0.15); }
    .dash-welcome-student::after           { background: rgba(59, 130, 246, 0.15); }
    .dash-welcome-parent::after            { background: rgba(34, 197, 94, 0.15); }

    /* ── Children cards (parent) ── */
    .child-card {
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        background: white;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .child-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        border-color: #3b82f6;
    }
    :is(.dark .child-card) {
        background: rgb(39 39 42);
        border-color: rgb(63 63 70);
    }
    :is(.dark .child-card:hover) {
        border-color: #3b82f6;
    }

    /* ── Badge pulse for live indicators ── */
    .badge-pulse {
        position: relative;
    }
    .badge-pulse::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        background: inherit;
    }

    /* ── Responsive refinements ── */
    @media (max-width: 639px) {
        .stat-card { padding: 1rem; }
        .stat-value { font-size: 1.5rem; }
        .stat-icon { width: 38px; height: 38px; }
        .dash-welcome { padding: 1.25rem; }
        .dash-panel-body { padding: 1rem; }
    }

    /* ── Progress bar entrance animation ── */
    .progress-fill:not(.animated),
    .occupancy-fill:not(.animated) {
        width: 0% !important;
    }

    /* ── SVG ring entrance animation ── */
    .metric-ring circle.ring-value:not(.animated) {
        stroke-dashoffset: var(--ring-circumference, 251) !important;
    }
    .metric-ring circle.ring-value {
        transition: stroke-dashoffset 1.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
</style>

{{-- Micro-animation script: number counters + progress bar reveals --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ── Animated number counters ──
    // Easing: ease-out cubic
    function easeOutCubic(t) { return 1 - Math.pow(1 - t, 3); }

    function animateNumber(node, target, duration) {
        var start = performance.now();
        var hasComma = target >= 1000;
        function step(now) {
            var t = Math.min((now - start) / duration, 1);
            var val = Math.round(easeOutCubic(t) * target);
            node.textContent = hasComma ? val.toLocaleString() : val;
            if (t < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    var countObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var el = entry.target;
            // Find the first text node containing a digit
            for (var i = 0; i < el.childNodes.length; i++) {
                var n = el.childNodes[i];
                if (n.nodeType === 3 && /\d/.test(n.textContent)) {
                    var raw = n.textContent.trim().replace(/,/g, '');
                    var num = parseInt(raw, 10);
                    if (!isNaN(num) && num > 0) {
                        n.textContent = '0';
                        animateNumber(n, num, 900);
                    }
                    break;
                }
            }
            countObserver.unobserve(el);
        });
    }, { threshold: 0.25 });

    document.querySelectorAll('.stat-value').forEach(function (el) {
        countObserver.observe(el);
    });

    // ── Progress bar & occupancy bar entrance ──
    var barObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            // Small rAF delay so the transition triggers properly
            requestAnimationFrame(function () {
                entry.target.classList.add('animated');
            });
            barObserver.unobserve(entry.target);
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.progress-fill, .occupancy-fill').forEach(function (el) {
        barObserver.observe(el);
    });

    // ── SVG ring entrance ──
    document.querySelectorAll('.metric-ring circle.ring-value').forEach(function (circle) {
        var circumference = 2 * Math.PI * (circle.getAttribute('r') || 40);
        circle.style.setProperty('--ring-circumference', circumference);
        var ringObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                requestAnimationFrame(function () {
                    entry.target.classList.add('animated');
                });
                ringObserver.unobserve(entry.target);
            });
        }, { threshold: 0.3 });
        ringObserver.observe(circle);
    });
});
</script>
