<?php

declare(strict_types=1);

namespace App\Modules\Tag\Http\Requests;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class TagCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user !== null && $user->can('create', Tag::class);
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
            'details' => 'nullable|string',
            'language' => 'nullable|string',
        ];
    }
}
