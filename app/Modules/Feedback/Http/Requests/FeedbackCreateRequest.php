<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FeedbackCreateRequest extends FormRequest
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
            'model_id' => ['required', 'integer'],
            'model_type' => ['required', 'string'],
            'positive' => ['nullable', 'boolean'],
            'negative' => ['nullable', 'boolean'],
        ];
    }
}
