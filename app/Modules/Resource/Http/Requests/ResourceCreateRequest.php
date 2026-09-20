<?php

namespace App\Modules\Resource\Http\Requests;

use App\Enums\ResourceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResourceCreateRequest extends FormRequest
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
            'type' => ['required', Rule::in(ResourceType::getValues())],
            'price' => ['nullable', 'numeric'],
            'image' => ['nullable', 'array'],
            'icon' => ['nullable', 'string'],
            'details' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
        ];
    }
}
