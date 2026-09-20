<?php

namespace App\Modules\Shipping\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShippingCreateRequest extends FormRequest
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
            'amount' => ['nullable', 'numeric'],
            'is_global' => ['nullable', 'boolean'],
            'type' => ['required', 'string'],
        ];
    }
}
