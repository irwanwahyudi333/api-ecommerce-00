<?php

declare(strict_types=1);

namespace App\Modules\RefundReason\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RefundReasonCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'slug' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
        ];
    }
}
