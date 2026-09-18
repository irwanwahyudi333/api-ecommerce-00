<?php

declare(strict_types=1);

namespace App\Modules\Checkout\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutVerifyRequest extends FormRequest
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
            // 'amount' => ['required', 'numeric'],
            'customer_id' => ['nullable', 'exists:users,id'],
            'products' => ['required', 'array'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.order_quantity' => ['required', 'integer', 'min:1'],
            'products.*.variation_option_id' => ['nullable', 'integer', 'exists:variation_options,id'],
            'products.*.unit_price' => ['nullable', 'numeric', 'min:0'], // Akan diabaikan/dihitung ulang, tapi tetap divalidasi
            'products.*.subtotal' => ['nullable', 'numeric', 'min:0'], // Akan diabaikan/dihitung ulang, tapi tetap divalidasi
            'billing_address' => ['nullable', 'array'],
            'shipping_address' => ['nullable', 'array'],
        ];
    }
}
