<?php

declare(strict_types=1);

namespace App\Modules\Attribute\DTO;

class AttributeData
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?int $shop_id,
        public readonly ?string $language,
        /** @var array<int, array<string, mixed>>|null */
        public readonly ?array $values,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $defaultLang = config('shop.default_language', 'id');
        $defaultLangStr = is_scalar($defaultLang) ? (string) $defaultLang : 'id';

        /** @var array<int, array<string, mixed>>|null $values */
        $values = $data['values'] ?? null;

        return new self(
            name: isset($data['name']) && is_string($data['name']) ? $data['name'] : null,
            slug: isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null,
            shop_id: isset($data['shop_id']) && is_numeric($data['shop_id']) ? (int) $data['shop_id'] : null,
            language: isset($data['language']) && is_string($data['language']) ? $data['language'] : $defaultLangStr,
            values: $values,
        );
    }
}
