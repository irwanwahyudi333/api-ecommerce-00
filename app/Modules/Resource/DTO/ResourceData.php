<?php

namespace App\Modules\Resource\DTO;

class ResourceData
{
    /**
     * @param  array<string, mixed>|null  $image
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?string $type,
        public readonly ?float $price,
        public readonly ?array $image,
        public readonly ?string $icon,
        public readonly ?string $details,
        public readonly ?string $language,
        public readonly ?bool $is_approved,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        /** @var array<string, mixed>|null $image */
        $image = isset($data['image']) && is_array($data['image']) ? $data['image'] : null;

        return new self(
            name: isset($data['name']) && is_string($data['name']) ? $data['name'] : null,
            slug: isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null,
            type: isset($data['type']) && is_string($data['type']) ? $data['type'] : null,
            price: isset($data['price']) && is_numeric($data['price']) ? (float) $data['price'] : null,
            image: $image,
            icon: isset($data['icon']) && is_string($data['icon']) ? $data['icon'] : null,
            details: isset($data['details']) && is_string($data['details']) ? $data['details'] : null,
            language: isset($data['language']) && is_string($data['language']) ? $data['language'] : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id'),
            is_approved: isset($data['is_approved']) ? (bool) $data['is_approved'] : null,
        );
    }
}
