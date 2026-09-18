<?php

declare(strict_types=1);

namespace App\Modules\Coupon\DTO;

/**
 * @phpstan-type CouponImage array<mixed, mixed>
 */
class CouponData
{
    /**
     * @param  CouponImage|null  $image
     */
    public function __construct(
        public readonly ?string $code,
        public readonly ?string $language,
        public readonly ?string $description,
        public readonly ?array $image,
        public readonly ?string $type,
        public readonly ?float $amount,
        public readonly ?float $minimum_cart_amount,
        public readonly ?string $active_from,
        public readonly ?string $expire_at,
        public readonly ?string $target,
        public readonly ?bool $is_approve,
        public readonly ?int $user_id,
        public readonly ?int $shop_id,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, ?int $userId = null): self
    {
        return new self(
            code: isset($data['code']) && is_string($data['code']) ? $data['code'] : null,
            language: isset($data['language']) && is_string($data['language']) ? $data['language'] : (is_string(config('shop.default_language', 'id')) ? (string) config('shop.default_language', 'id') : 'id'),
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
            image: isset($data['image']) && is_array($data['image']) ? $data['image'] : null,
            type: isset($data['type']) && is_string($data['type']) ? $data['type'] : null,
            amount: isset($data['amount']) && is_numeric($data['amount']) ? (float) $data['amount'] : null,
            minimum_cart_amount: isset($data['minimum_cart_amount']) && is_numeric($data['minimum_cart_amount']) ? (float) $data['minimum_cart_amount'] : 0.0,
            active_from: isset($data['active_from']) && is_string($data['active_from']) ? $data['active_from'] : null,
            expire_at: isset($data['expire_at']) && is_string($data['expire_at']) ? $data['expire_at'] : null,
            target: isset($data['target']) && is_string($data['target']) ? $data['target'] : null,
            is_approve: isset($data['is_approve']) ? (bool) $data['is_approve'] : null,
            user_id: $userId,
            shop_id: isset($data['shop_id']) && is_numeric($data['shop_id']) ? (int) $data['shop_id'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'code' => $this->code,
            'language' => $this->language,
            'description' => $this->description,
            'image' => $this->image,
            'type' => $this->type,
            'amount' => $this->amount,
            'minimum_cart_amount' => $this->minimum_cart_amount,
            'active_from' => $this->active_from,
            'expire_at' => $this->expire_at,
            'target' => $this->target,
            'is_approve' => $this->is_approve,
            'user_id' => $this->user_id,
            'shop_id' => $this->shop_id,
        ], fn ($v) => ! is_null($v));
    }
}
