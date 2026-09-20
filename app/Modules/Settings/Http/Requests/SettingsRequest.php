<?php

declare(strict_types=1);

namespace App\Modules\Settings\Http\Requests;

use App\Models\Settings;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        // Only super_admin can create or update settings
        return $user && $user->can('create', Settings::class);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'options' => ['required', 'array'],
            'language' => ['nullable', 'string'],
        ];
    }
}
