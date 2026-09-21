<?php

namespace App\Modules\Type\DTO;

class TypeData
{
    /**
     * @param  array<int, array<string, mixed>>|null  $banners
     * @param  array<string, mixed>|null  $settings
     * @param  array<int, array<string, mixed>>|null  $promotional_sliders
     * @param  array<int, string>|null  $images
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?string $icon,
        public readonly ?array $banners,
        public readonly ?array $settings,
        public readonly ?array $promotional_sliders,
        public readonly ?array $images,
        public readonly ?string $language,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        /** @var string $defaultLanguage */
        $defaultLanguage = config('shop.default_language', 'id');

        /** @var array<int, array<string, mixed>>|null $banners */
        $banners = isset($data['banners']) && is_array($data['banners']) ? $data['banners'] : null;
        /** @var array<string, mixed>|null $settings */
        $settings = isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : null;
        /** @var array<int, array<string, mixed>>|null $promotional_sliders */
        $promotional_sliders = isset($data['promotional_sliders']) && is_array($data['promotional_sliders']) ? $data['promotional_sliders'] : null;
        /** @var array<int, string>|null $images */
        $images = isset($data['images']) && is_array($data['images']) ? $data['images'] : null;

        return new self(
            name: isset($data['name']) && is_string($data['name']) ? $data['name'] : null,
            slug: isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null,
            icon: isset($data['icon']) && is_string($data['icon']) ? $data['icon'] : null,
            banners: $banners,
            settings: $settings,
            promotional_sliders: $promotional_sliders,
            images: $images,
            language: isset($data['language']) && is_string($data['language']) ? $data['language'] : $defaultLanguage,
        );
    }
}
