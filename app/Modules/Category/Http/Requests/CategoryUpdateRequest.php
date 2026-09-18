<?php

declare(strict_types=1);

namespace App\Modules\Category\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string',
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
