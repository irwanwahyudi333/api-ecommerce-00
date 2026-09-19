<?php

declare(strict_types=1);

namespace App\Modules\PaymentMethod\Http\Requests;

use App\Modules\Payment\Factory\PaymentProviderFactory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodCreateRequest extends FormRequest
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
            'method_key' => ['required', 'string'],
            'default_card' => ['nullable', 'boolean'],
            'payment_gateway' => ['required', Rule::in(PaymentProviderFactory::getAvailableGateways())],
        ];
    }
}
