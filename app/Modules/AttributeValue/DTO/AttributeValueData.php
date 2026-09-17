<?php

declare(strict_types=1);

namespace App\Modules\AttributeValue\DTO;

class AttributeValueData
{
    public function __construct(
        public readonly ?string $value,
        public readonly ?string $meta,
        public readonly ?float $price,
        public readonly ?int $shop_id,
        public readonly ?int $attribute_id,
        public readonly ?string $language,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromRequest(array $data): self
    {
        /** @var string $defaultLanguage */
        $defaultLanguage = config('shop.default_language', 'id');

        return new self(
            value: isset($data['value']) && is_string($data['value']) ? $data['value'] : null,
            meta: isset($data['meta']) && is_string($data['meta']) ? $data['meta'] : null,
            price: isset($data['price']) && is_numeric($data['price']) ? (float) $data['price'] : null,
            shop_id: isset($data['shop_id']) && is_numeric($data['shop_id']) ? (int) $data['shop_id'] : null,
            attribute_id: isset($data['attribute_id']) && is_numeric($data['attribute_id']) ? (int) $data['attribute_id'] : null,
            language: isset($data['language']) && is_string($data['language']) ? $data['language'] : $defaultLanguage,
        );
    }
}
