<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSettingsController extends Controller
{
    public function index(): View
    {
        $settings = PlatformSetting::allValues();

        return view('super-admin.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:100'],
            'default_free_ai_credits' => ['required', 'integer', 'min:0', 'max:100'],
            'maintenance_mode' => ['nullable', 'boolean'],
            'maintenance_message' => ['nullable', 'string', 'max:500'],
            'allowed_file_types' => ['required', 'string', 'max:500'],
            'max_upload_size_mb' => ['required', 'integer', 'min:1', 'max:100'],
            'credit_price_per_5' => ['required', 'integer', 'min:100', 'max:50000'],
        ]);

        // Checkboxes: absent when unchecked
        $validated['maintenance_mode'] = $request->boolean('maintenance_mode');

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value ?? '');
        }

        return back()->with('success', __('Platform settings saved successfully.'));
    }
}
