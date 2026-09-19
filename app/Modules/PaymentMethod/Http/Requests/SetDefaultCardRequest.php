<?php

declare(strict_types=1);

namespace App\Modules\PaymentMethod\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetDefaultCardRequest extends FormRequest
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
            'method_id' => ['required', 'exists:payment_methods,id'],
        ];
    }
}
