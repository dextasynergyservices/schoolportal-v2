<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\SchoolSetupService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        // Normalize slug so the unique rule catches collisions before the DB does.
        // If blank, derive from the name — mirrors SchoolSetupService::create().
        $slug = trim((string) $this->input('slug'));
        $name = trim((string) $this->input('name'));

        if ($slug === '' && $name !== '') {
            $slug = Str::slug($name);
        }

        $this->merge([
            'name' => $name,
            'slug' => $slug !== '' ? Str::slug($slug) : null,
        ]);
    }

    /**
     * @return array<string, array<int, string|ValidationRule>|string>
     */
    public function rules(): array
    {
        $levelKeys = array_keys(SchoolSetupService::LEVEL_NAMES);

        return [
            // Step 1 — School information
            'name' => ['required', 'string', 'max:255', Rule::unique('schools', 'name')],
            'slug' => ['required', 'string', 'alpha_dash', 'max:100', Rule::unique('schools', 'slug')],
            'custom_domain' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', Rule::unique('schools', 'custom_domain')],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'motto' => ['nullable', 'string', 'max:255'],

            // Step 2 — Levels
            'levels' => ['required', 'array', 'min:1'],
            'levels.*' => ['required', 'string', Rule::in($levelKeys)],

            // Step 3 — Classes per level (optional override of presets)
            'classes' => ['nullable', 'array'],
            'classes.*' => ['array'],
            'classes.*.*' => ['string', 'max:100'],

            // Step 4 — First admin account
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_username' => ['required', 'string', 'alpha_dash', 'max:100'],
            'admin_password' => ['required', 'string', 'confirmed', Password::defaults()],
            'admin_phone' => ['nullable', 'string', 'max:20'],

            // Step 5 — First academic session
            'session_name' => ['required', 'string', 'max:50'],
            'session_start_date' => ['required', 'date'],
            'session_end_date' => ['required', 'date', 'after:session_start_date'],

            // Step 6 — Optional logo (may be uploaded during the wizard or later via uploadLogo)
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => __('A school with this name already exists. Please choose a different name or edit the existing record.'),
            'slug.unique' => __('A school with this slug already exists. Edit the slug field to make it unique.'),
            'custom_domain.unique' => __('This domain is already registered to another school.'),
            'custom_domain.regex' => __('Please enter a valid domain (e.g., pearschool.com).'),
            'session_end_date.after' => __('Session end date must be after the start date.'),
        ];
    }
}
