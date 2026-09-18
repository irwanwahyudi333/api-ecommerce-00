<?php

declare(strict_types=1);

namespace App\Modules\Coupon\Http\Requests;

use App\Enums\CouponType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponCreateRequest extends FormRequest
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
        $langInput = $this->input('language', config('shop.default_language', 'id'));
        $language = is_string($langInput) ? $langInput : 'id';
        $amountRules = ($this->type === 'percentage')
            ? ['required', 'numeric', 'min:0', 'max:100']
            : ['required', 'numeric', 'min:0'];

        return [
            'code' => ['required', Rule::unique('coupons')->where('language', $language)],
            'amount' => $amountRules,
            'minimum_cart_amount' => ['required', 'numeric', 'min:0'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'type' => ['required', Rule::in(CouponType::getValues())],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'array'],
            'language' => ['nullable', 'string'],
            'active_from' => ['required', 'date'],
            'expire_at' => ['required', 'date'],
        ];
    }
}
