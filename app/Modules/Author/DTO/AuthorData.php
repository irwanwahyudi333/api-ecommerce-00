<?php

declare(strict_types=1);

namespace App\Modules\Author\DTO;

final readonly class AuthorData
{
    /**
     * @param  array<mixed>|null  $image
     * @param  array<mixed>|null  $cover_image
     */
    public function __construct(
        public ?string $name,
        public ?string $slug,
        public ?string $bio,
        public ?int $shop_id,
        public ?array $image,
        public ?array $cover_image,
        public ?bool $is_approved,
        public ?string $language,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: isset($data['name']) && is_string($data['name']) ? $data['name'] : null,
            slug: isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null,
            bio: isset($data['bio']) && is_string($data['bio']) ? $data['bio'] : null,
            shop_id: isset($data['shop_id']) && is_numeric($data['shop_id']) ? (int) $data['shop_id'] : null,
            image: isset($data['image']) && is_array($data['image']) ? $data['image'] : null,
            cover_image: isset($data['cover_image']) && is_array($data['cover_image']) ? $data['cover_image'] : null,
            is_approved: isset($data['is_approved']) && is_bool($data['is_approved']) ? $data['is_approved'] : null,
            language: (isset($data['language']) && is_string($data['language'])) ? $data['language'] : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id'),
        );
    }
}
