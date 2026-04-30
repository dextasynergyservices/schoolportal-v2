/**
 * Analytics Charts — Chart.js powered charts for the super-admin analytics page.
 * Called by the inline script in super-admin/analytics.blade.php after the DOM is ready.
 */
import {
    BarElement,
    CategoryScale,
    Chart,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(CategoryScale, LinearScale, BarElement, PointElement, LineElement, Filler, Tooltip, Legend);

// ── Shared helpers ──────────────────────────────────────────────────────────

const isDark = () => document.documentElement.classList.contains('dark');

const gridColor = () => (isDark() ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)');
const labelColor = () => (isDark() ? '#a1a1aa' : '#71717a');

const baseTooltip = {
    backgroundColor: isDark() ? '#27272a' : '#18181b',
    titleColor: '#fafafa',
    bodyColor: '#d4d4d8',
    borderColor: isDark() ? '#3f3f46' : '#3f3f46',
    borderWidth: 1,
    padding: 10,
    cornerRadius: 8,
    displayColors: true,
    boxWidth: 10,
    boxHeight: 10,
};

const baseScales = (yTickCallback) => ({
    x: {
        grid: { display: false },
        border: { display: false },
        ticks: { color: labelColor, font: { size: 11 }, maxRotation: 0 },
    },
    y: {
        grid: { color: gridColor, drawBorder: false },
        border: { display: false, dash: [4, 4] },
        ticks: {
            color: labelColor,
            font: { size: 11 },
            callback: yTickCallback ?? ((v) => v),
            maxTicksLimit: 5,
        },
        beginAtZero: true,
    },
});

// ── Bar chart factory ────────────────────────────────────────────────────────

function makeBarChart(canvasId, labels, data, { color, yTickCallback, tooltipLabel }) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    // Create gradient
    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, `${color}dd`);
    gradient.addColorStop(1, `${color}55`);

    return new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    data,
                    backgroundColor: gradient,
                    hoverBackgroundColor: color,
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.65,
                    categoryPercentage: 0.85,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 700, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...baseTooltip,
                    callbacks: {
                        label: (ctx) => tooltipLabel(ctx.parsed.y),
                    },
                },
            },
            scales: baseScales(yTickCallback),
        },
    });
}

// ── Area line chart factory ──────────────────────────────────────────────────

function makeLineChart(canvasId, labels, data, { color, yTickCallback, tooltipLabel }) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, `${color}33`);
    gradient.addColorStop(1, `${color}00`);

    return new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    data,
                    fill: true,
                    backgroundColor: gradient,
                    borderColor: color,
                    borderWidth: 2.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: color,
                    pointBorderColor: isDark() ? '#27272a' : '#ffffff',
                    pointBorderWidth: 2,
                    tension: 0.4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 700, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    ...baseTooltip,
                    callbacks: {
                        label: (ctx) => tooltipLabel(ctx.parsed.y),
                    },
                },
            },
            scales: baseScales(yTickCallback),
        },
    });
}

// ── Public API ───────────────────────────────────────────────────────────────

export function initAnalyticsCharts({ labels, schoolsData, studentsData, revenueData, creditsData }) {
    const nairaFmt = (v) => `  ₦${Number(v).toLocaleString()}`;

    // Destroy any existing chart instances on these canvases before re-creating
    ['chart-schools', 'chart-students', 'chart-revenue', 'chart-credits'].forEach((id) => {
        const existing = Chart.getChart(id);
        if (existing) existing.destroy();
    });

    makeBarChart('chart-schools', labels, schoolsData, {
        color: '#4338ca',
        tooltipLabel: (v) => `  ${v} school${v !== 1 ? 's' : ''}`,
    });

    makeLineChart('chart-students', labels, studentsData, {
        color: '#10b981',
        tooltipLabel: (v) => `  ${v} student${v !== 1 ? 's' : ''}`,
    });

    makeBarChart('chart-revenue', labels, revenueData, {
        color: '#f59e0b',
        yTickCallback: (v) => (v >= 1000 ? `₦${(v / 1000).toFixed(0)}k` : `₦${v}`),
        tooltipLabel: nairaFmt,
    });

    makeLineChart('chart-credits', labels, creditsData, {
        color: '#8b5cf6',
        tooltipLabel: (v) => `  ${v} credit${v !== 1 ? 's' : ''}`,
    });
}

// ── Auto-init ────────────────────────────────────────────────────────────────
// Data is written to window.__analyticsData by a regular <script> in the blade.
// This module then picks it up on load and on each Livewire SPA navigation.

function tryInit() {
    if (window.__analyticsData && document.getElementById('chart-schools')) {
        initAnalyticsCharts(window.__analyticsData);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', tryInit);
} else {
    tryInit();
}

document.addEventListener('livewire:navigated', tryInit);
