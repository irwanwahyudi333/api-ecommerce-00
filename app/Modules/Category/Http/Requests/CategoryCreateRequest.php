<?php

declare(strict_types=1);

namespace App\Modules\Category\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy di controller
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'slug' => 'nullable|string',
            'type_id' => 'nullable|exists:types,id',
            'icon' => 'nullable|string',
            'image' => 'nullable|array',
            'banner_image' => 'nullable|array',
            'details' => 'nullable|string',
            'language' => 'nullable|string',
            'parent' => 'nullable|integer|exists:categories,id',
        ];
    }
}
