<?php

declare(strict_types=1);

namespace App\Modules\DeliveryTime\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryTimeRequest extends FormRequest
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
            'title' => ['required', 'string'],
            'slug' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:10000'],
            'icon' => ['nullable', 'string'],
        ];
    }
}
