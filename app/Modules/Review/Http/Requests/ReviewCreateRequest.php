<?php

declare(strict_types=1);

namespace App\Modules\Review\Http\Requests;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ReviewCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user && $user->can('create', Review::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id'],
            'product_id' => ['required', 'exists:products,id'],
            'variation_option_id' => ['nullable', 'integer', 'exists:variations,id'],
            'comment' => ['required', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'shop_id' => ['required', 'exists:shops,id'],
            'photos' => ['nullable', 'array'],
        ];
    }
}
