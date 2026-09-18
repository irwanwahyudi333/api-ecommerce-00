<?php

declare(strict_types=1);

namespace App\Modules\Faqs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FaqsUpdateRequest extends FormRequest
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
            'faq_title' => ['required', 'string'],
            'faq_description' => ['required', 'string'],
            'slug' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
            'shop_id' => ['nullable', 'exists:shops,id'],
        ];
    }
}
