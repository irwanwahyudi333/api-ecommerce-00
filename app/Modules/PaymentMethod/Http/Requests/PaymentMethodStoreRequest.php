<?php

namespace App\Modules\PaymentMethod\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentMethodStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'method_key' => ['required', 'string'],
            'method_type' => ['required', 'string'],
            'payment_gateway' => ['required', 'string'],
            'customer_id' => ['nullable', 'string'],
            'default_payment' => ['boolean'],
            'fingerprint' => ['nullable', 'string'],
            'brand' => ['nullable', 'string'],
            'last4' => ['nullable', 'string'],
            'exp_month' => ['nullable', 'integer'],
            'exp_year' => ['nullable', 'integer'],
            'va_number' => ['nullable', 'string'],
            'bank_code' => ['nullable', 'string'],
            'qris_url' => ['nullable', 'string'],
            'ewallet_type' => ['nullable', 'string'],
            'account_name' => ['nullable', 'string'],
            'account_number' => ['nullable', 'string'],
            'account_last4' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
