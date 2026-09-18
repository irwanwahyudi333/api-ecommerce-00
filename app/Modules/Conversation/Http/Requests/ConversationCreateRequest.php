<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversationCreateRequest extends FormRequest
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
            'shop_id' => ['required', 'exists:shops,id'],
        ];
    }
}
