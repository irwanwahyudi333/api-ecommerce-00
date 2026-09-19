<?php

declare(strict_types=1);

namespace App\Modules\OwnershipTransfer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OwnershipTransferRequest extends FormRequest
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
            'shop_id' => ['required', 'exists:shops,id'],
            'vendor_id' => ['required', 'exists:users,id'],
            'message' => ['nullable', 'string'],
            'vendorMessage' => ['nullable', 'string'],
        ];
    }
}
