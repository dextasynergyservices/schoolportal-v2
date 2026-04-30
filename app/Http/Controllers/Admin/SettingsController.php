<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $school = app('current.school');

        return view('admin.settings.index', compact('school'));
    }

    public function update(Request $request): RedirectResponse
    {
        $school = app('current.school');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'motto' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'timezone:all'],
        ]);

        $school->update($validated);

        return redirect()->route('admin.settings.index')
            ->with('success', __('School settings updated.'));
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $school = app('current.school');

        $validated = $request->validate([
            'primary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $settings = $school->settings ?? [];
        $settings['branding'] = array_merge($settings['branding'] ?? [], $validated);

        $school->update(['settings' => $settings]);

        return redirect()->route('admin.settings.index')
            ->with('success', __('Branding updated.'));
    }

    public function updatePortal(Request $request): RedirectResponse
    {
        $school = app('current.school');

        $validated = $request->validate([
            'enable_parent_portal' => ['boolean'],
            'enable_quiz_generator' => ['boolean'],
            'enable_game_generator' => ['boolean'],
            'enable_teacher_approval' => ['boolean'],
            'session_timeout_minutes' => ['required', 'integer', 'min:5', 'max:120'],
            'max_file_upload_mb' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $settings = $school->settings ?? [];
        $settings['portal'] = array_merge($settings['portal'] ?? [], $validated);

        $school->update(['settings' => $settings]);

        return redirect()->route('admin.settings.index')
            ->with('success', __('Portal settings updated.'));
    }

    public function uploadLogo(Request $request, FileUploadService $uploader): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $school = app('current.school');

        try {
            if ($school->logo_public_id) {
                $uploader->delete($school->logo_public_id);
            }

            $result = $uploader->uploadSchoolLogo($request->file('logo'), $school->id);

            $school->logo_url = $result['url'];
            $school->logo_public_id = $result['public_id'];
            $school->save();

            \Log::info('Logo uploaded successfully', [
                'school_id' => $school->id,
                'logo_url' => $school->logo_url,
                'logo_public_id' => $school->logo_public_id,
            ]);

            return redirect()->route('admin.settings.index')
                ->with('success', __('School logo updated.'));
        } catch (\Throwable $e) {
            \Log::error('Logo upload failed', [
                'school_id' => $school->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.settings.index')
                ->with('error', __('Logo upload failed: ').$e->getMessage());
        }
    }

    public function removeLogo(FileUploadService $uploader): RedirectResponse
    {
        $school = app('current.school');

        if ($school->logo_public_id) {
            $uploader->delete($school->logo_public_id);
        }

        $school->update([
            'logo_url' => null,
            'logo_public_id' => null,
        ]);

        return redirect()->route('admin.settings.index')
            ->with('success', __('School logo removed.'));
    }
}
