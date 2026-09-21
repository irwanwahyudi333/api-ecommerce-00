<?php

namespace App\Modules\Wishlist\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WishlistCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'variation_option_id' => ['nullable', 'integer', 'exists:variations,id'],
        ];
    }
}
