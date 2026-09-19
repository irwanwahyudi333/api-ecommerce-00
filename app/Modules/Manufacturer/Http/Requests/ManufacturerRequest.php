<?php

declare(strict_types=1);

namespace App\Modules\Manufacturer\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ManufacturerRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'slug' => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:10000'],
            'type_id' => ['required', 'exists:types,id'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'image' => ['nullable', 'array'],
            'cover_image' => ['nullable', 'array'],
            'is_approved' => ['nullable', 'boolean'],
            'language' => ['nullable', 'string'],
            'website' => ['nullable', 'string', 'url'],
            'socials' => ['nullable', 'array'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
