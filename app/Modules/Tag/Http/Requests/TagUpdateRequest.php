<?php

declare(strict_types=1);

namespace App\Modules\Tag\Http\Requests;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class TagUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();
        /** @var Tag $tag */
        $tag = $this->route('tag');

        return $user !== null && $user->can('update', $tag);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'type_id' => ['required', 'integer'],
            'icon' => ['nullable', 'string'],
            'image' => ['nullable', 'array'],
            'details' => ['nullable', 'string'],
            'language' => ['nullable', 'string'],
        ];
    }
}
