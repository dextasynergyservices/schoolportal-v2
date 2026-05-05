<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;

class PwaManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $school = app()->bound('current.school') ? app('current.school') : null;

        $name = $school?->name ?? config('app.name', 'DX-SchoolPortal');
        $shortName = str($name)->limit(12, '')->toString();
        $color = $school?->setting('branding.primary_color') ?? '#4F46E5';
        $bgColor = $school?->setting('branding.primary_color') ?? '#4F46E5';
        $logo = $school?->setting('branding.logo_url') ?? null;

        $startUrl = URL::to('/portal/student/dashboard');

        $icons = [];

        if ($logo) {
            // Use the school's Cloudinary logo, transformed to the required sizes
            $icons = [
                ['src' => $logo, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => $logo, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ];
        } else {
            // Fall back to the bundled generic icons
            $icons = [
                ['src' => '/icons/pwa-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any maskable'],
                ['src' => '/icons/pwa-512x512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ];
        }

        $manifest = [
            'name' => $name.' Portal',
            'short_name' => $shortName,
            'description' => 'Access your results, assignments and notices — even offline.',
            'start_url' => $startUrl,
            'scope' => URL::to('/portal/student'),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#ffffff',
            'theme_color' => $color,
            'icons' => $icons,
            'categories' => ['education'],
        ];

        return response()
            ->json($manifest)
            ->header('Content-Type', 'application/manifest+json')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
