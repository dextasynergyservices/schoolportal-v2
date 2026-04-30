<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlatformEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:200000'],
            'school_ids' => ['required', 'array', 'min:1'],
            'school_ids.*' => ['required', 'integer', 'exists:schools,id'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif,webp,txt'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.max' => __('You can attach a maximum of 5 files.'),
            'attachments.*.max' => __('Each attachment must be smaller than 5 MB.'),
            'attachments.*.mimes' => __('Only PDF, Word, Excel, image, and text files are allowed.'),
        ];
    }
}
