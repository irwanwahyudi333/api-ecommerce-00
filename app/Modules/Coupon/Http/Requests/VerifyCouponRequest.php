<?php

declare(strict_types=1);

namespace App\Modules\Coupon\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class VerifyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'sub_total' => ['required', 'numeric'],
            'item' => ['nullable', 'array'],
        ];
    }
}
