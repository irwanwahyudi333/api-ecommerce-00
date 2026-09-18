<?php

declare(strict_types=1);

namespace App\Modules\Category\DTO;

class CategoryData
{
    /**
     * @param  array<mixed, mixed>|null  $image
     * @param  array<mixed, mixed>|null  $banner_image
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?int $type_id,
        public readonly ?string $icon,
        public readonly ?array $image,
        public readonly ?string $details,
        public readonly ?array $banner_image,
        public readonly ?string $language,
        public readonly ?int $parent,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: isset($data['name']) && is_scalar($data['name']) ? (string) $data['name'] : null,
            slug: isset($data['slug']) && is_scalar($data['slug']) ? (string) $data['slug'] : null,
            type_id: isset($data['type_id']) && is_numeric($data['type_id']) ? (int) $data['type_id'] : null,
            icon: isset($data['icon']) && is_scalar($data['icon']) ? (string) $data['icon'] : null,
            image: isset($data['image']) && is_array($data['image']) ? $data['image'] : null,
            details: isset($data['details']) && is_scalar($data['details']) ? (string) $data['details'] : null,
            banner_image: isset($data['banner_image']) && is_array($data['banner_image']) ? $data['banner_image'] : null,
            language: isset($data['language']) && is_scalar($data['language']) ? (string) $data['language'] : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id'),
            parent: isset($data['parent']) && is_numeric($data['parent']) ? (int) $data['parent'] : null,
        );
    }
}
