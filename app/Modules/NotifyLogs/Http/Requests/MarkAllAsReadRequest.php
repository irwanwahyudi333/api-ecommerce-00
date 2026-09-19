<?php

declare(strict_types=1);

namespace App\Modules\NotifyLogs\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkAllAsReadRequest extends FormRequest
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
            'receiver' => ['required', 'exists:users,id'],
            'notify_type' => ['nullable', 'string'],
            'set_all_read' => ['required', 'boolean'],
        ];
    }
}
