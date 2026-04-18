<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, string|ValidationRule>|string>
     */
    public function rules(): array
    {
        $schoolId = $this->route('school')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
                Rule::unique('schools', 'custom_domain')->ignore($schoolId),
            ],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'motto' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'custom_domain.regex' => __('Please enter a valid domain (e.g., pearschool.com).'),
            'primary_color.regex' => __('Primary color must be a valid hex code (e.g., #4F46E5).'),
            'secondary_color.regex' => __('Secondary color must be a valid hex code (e.g., #F59E0B).'),
            'accent_color.regex' => __('Accent color must be a valid hex code (e.g., #10B981).'),
        ];
    }
}
