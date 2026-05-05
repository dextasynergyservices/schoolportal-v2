import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/landing.js'],
            refresh: true,
        }),
        tailwindcss(),
        VitePWA({
            // We serve a dynamic manifest from Laravel (per-school name/color)
            // so we skip the auto-generated one here.
            manifest: false,
            registerType: 'autoUpdate',
            injectRegister: null, // We register manually in app.js so we can scope to /portal/*
            workbox: {
                // Service worker file name — must output to public/ so Laravel serves it from web root
                swDest: 'public/sw.js',
                // Only cache student-facing portal pages (not admin/teacher write-heavy pages)
                navigateFallback: null,
                runtimeCaching: [
                    // ── Static assets (CSS, JS, fonts, images) ──────────────
                    {
                        urlPattern: /\.(css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|webp|ico)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'dx-static-assets-v1',
                            expiration: { maxEntries: 120, maxAgeSeconds: 60 * 60 * 24 * 30 },
                        },
                    },
                    // ── Cloudinary images ────────────────────────────────────
                    {
                        urlPattern: /^https:\/\/res\.cloudinary\.com\/.*/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'dx-cloudinary-v1',
                            expiration: { maxEntries: 80, maxAgeSeconds: 60 * 60 * 24 * 7 },
                        },
                    },
                    // ── Student read-only portal pages ───────────────────────
                    // Cache-first (serve stale, update in background) for these navigations.
                    // Regex matches: /portal/student/dashboard, /portal/student/assignments,
                    //                /portal/student/results, /portal/student/notices,
                    //                /portal/student/profile, /portal/student/achievements
                    {
                        urlPattern: ({ url }) => {
                            const path = url.pathname;
                            return (
                                path.includes('/portal/student/dashboard') ||
                                path.includes('/portal/student/assignments') ||
                                path.includes('/portal/student/results') ||
                                path.includes('/portal/student/notices') ||
                                path.includes('/portal/student/profile') ||
                                path.includes('/portal/student/achievements') ||
                                path.includes('/portal/student/report-cards')
                            );
                        },
                        handler: 'StaleWhileRevalidate',
                        options: {
                            cacheName: 'dx-student-pages-v1',
                            expiration: { maxEntries: 30, maxAgeSeconds: 60 * 60 * 24 * 3 },
                            cacheableResponse: { statuses: [0, 200] },
                        },
                    },
                    // ── Dynamic web app manifest ─────────────────────────────
                    {
                        urlPattern: /\/portal\/manifest\.json$/,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'dx-manifest-v1',
                            expiration: { maxEntries: 5, maxAgeSeconds: 60 * 60 * 24 },
                        },
                    },
                ],
                // Don't precache anything — runtime caching only
                globPatterns: [],
            },
        }),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
