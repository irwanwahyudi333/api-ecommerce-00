<?php

declare(strict_types=1);

namespace App\Modules\Coupon\Http\Requests;

use App\Enums\CouponType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponUpdateRequest extends FormRequest
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
        $couponId = $this->route('id'); // ambil id dari route

        $amountRules = ($this->type === 'percentage')
            ? ['required', 'numeric', 'min:0', 'max:100']
            : ['required', 'numeric', 'min:0'];

        $rules = [
            'description' => ['nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'array'],
            'language' => ['nullable', 'string'],
            'amount' => $amountRules,
            'minimum_cart_amount' => ['nullable', 'numeric', 'min:0'],
        ];

        if ($language === config('shop.default_language', 'id')) {
            $rules['code'] = [
                'nullable',
                'string',
                Rule::unique('coupons')->where('language', $language)->ignore($couponId),
            ];
            $rules['type'] = ['nullable', Rule::in(CouponType::getValues())];
            $rules['active_from'] = ['nullable', 'date'];
            $rules['expire_at'] = ['nullable', 'date'];
        }

        return $rules;
    }
}
