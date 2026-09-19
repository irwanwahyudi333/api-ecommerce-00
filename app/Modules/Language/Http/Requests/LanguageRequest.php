<?php

declare(strict_types=1);

namespace App\Modules\Language\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'language_name' => ['required', 'string'],
            'language_code' => ['required', 'string'],
            'flag' => ['required', 'string'],
        ];
    }
}
