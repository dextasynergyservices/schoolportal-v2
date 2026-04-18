<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdjustCreditsRequest extends FormRequest
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
        return [
            'free_delta' => ['nullable', 'integer', 'between:-10000,10000'],
            'purchased_delta' => ['nullable', 'integer', 'between:-10000,10000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $free = (int) $this->input('free_delta', 0);
            $purchased = (int) $this->input('purchased_delta', 0);

            if ($free === 0 && $purchased === 0) {
                $validator->errors()->add('free_delta', __('Enter a non-zero adjustment for free or purchased credits.'));
            }
        });
    }
}
