<?php

declare(strict_types=1);

namespace App\Modules\Question\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuestionUpdateRequest extends FormRequest
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
            'answer' => ['required', 'string'],
            'shop_id' => ['nullable', 'exists:shops,id'],
        ];
    }
}
