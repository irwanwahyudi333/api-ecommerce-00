<?php

declare(strict_types=1);

namespace App\Modules\DeliveryTime\DTO;

class DeliveryTimeData
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $slug,
        public readonly ?string $language,
        public readonly ?string $description,
        public readonly ?string $icon,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $defaultLanguage = config('shop.default_language', 'id');

        return new self(
            title: isset($data['title']) && is_string($data['title']) ? $data['title'] : null,
            slug: isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null,
            language: isset($data['language']) && is_string($data['language']) ? $data['language'] : (is_string($defaultLanguage) ? $defaultLanguage : 'id'),
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
            icon: isset($data['icon']) && is_string($data['icon']) ? $data['icon'] : null,
        );
    }
}
