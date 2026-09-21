<?php

namespace App\Modules\Wishlist\DTO;

class WishlistData
{
    public function __construct(
        public readonly int $product_id,
        public readonly int $user_id,
        public readonly ?int $variation_option_id,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            product_id: is_numeric($data['product_id']) ? (int) $data['product_id'] : 0,
            user_id: $userId,
            variation_option_id: isset($data['variation_option_id']) && is_numeric($data['variation_option_id']) ? (int) $data['variation_option_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'product_id' => $this->product_id,
            'user_id' => $this->user_id,
            'variation_option_id' => $this->variation_option_id,
        ], fn ($v) => ! is_null($v));
    }
}
