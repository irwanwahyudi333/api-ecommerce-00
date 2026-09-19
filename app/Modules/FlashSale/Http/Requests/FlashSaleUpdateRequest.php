<?php

declare(strict_types=1);

namespace App\Modules\FlashSale\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FlashSaleUpdateRequest extends FormRequest
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
            'title' => ['required', 'string'],
            'description' => ['required', 'string', 'max:10000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'slug' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
            'image' => ['nullable', 'array'],
            'cover_image' => ['nullable', 'array'],
            'sale_builder' => ['required', 'array'],
            'rate' => ['nullable', 'numeric'],
            'type' => ['nullable', 'string'],
            'sale_status' => ['nullable', 'string'],
        ];
    }
}
