<?php

declare(strict_types=1);

namespace App\Modules\Manufacturer\DTO;

class ManufacturerData
{
    /**
     * @param  array<mixed, mixed>|null  $image
     * @param  array<mixed, mixed>|null  $cover_image
     * @param  array<mixed, mixed>|null  $socials
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?string $description,
        public readonly ?int $type_id,
        public readonly ?int $shop_id,
        public readonly ?array $image,
        public readonly ?array $cover_image,
        public readonly ?bool $is_approved,
        public readonly ?string $language,
        public readonly ?string $website,
        public readonly ?array $socials,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: is_string($data['name'] ?? null) ? $data['name'] : null,
            slug: is_string($data['slug'] ?? null) ? $data['slug'] : null,
            description: is_string($data['description'] ?? null) ? $data['description'] : null,
            type_id: is_numeric($data['type_id'] ?? null) ? (int) $data['type_id'] : null,
            shop_id: is_numeric($data['shop_id'] ?? null) ? (int) $data['shop_id'] : null,
            image: is_array($data['image'] ?? null) ? $data['image'] : null,
            cover_image: is_array($data['cover_image'] ?? null) ? $data['cover_image'] : null,
            is_approved: isset($data['is_approved']) ? (bool) $data['is_approved'] : null,
            language: is_string($data['language'] ?? null) ? $data['language'] : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id'),
            website: is_string($data['website'] ?? null) ? $data['website'] : null,
            socials: is_array($data['socials'] ?? null) ? $data['socials'] : null,
        );
    }
}
