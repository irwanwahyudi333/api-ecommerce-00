<?php

declare(strict_types=1);

namespace App\Modules\FlashSaleRequest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FlashSaleRequestUpdateRequest extends FormRequest
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
            'title' => ['required', 'string'],
            'note' => ['nullable', 'string'],
            'flash_sale_id' => ['required', 'exists:flash_sales,id'],
            'language' => ['nullable', 'string'],
            'requested_product_ids' => ['nullable', 'array'],
            'requested_product_ids.*' => ['exists:products,id'],
        ];
    }
}
