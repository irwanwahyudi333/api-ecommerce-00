<?php

namespace App\Modules\Terms\DTO;

class TermsData
{
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $language,
        public readonly ?string $slug,
        public readonly ?int $shop_id,
        public readonly ?int $user_id,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, ?int $userId = null): self
    {
        $defaultLang = config('shop.default_language', 'id');
        $defaultLang = is_scalar($defaultLang) ? (string) $defaultLang : 'id';

        return new self(
            title: isset($data['title']) && is_scalar($data['title']) ? (string) $data['title'] : null,
            description: isset($data['description']) && is_scalar($data['description']) ? (string) $data['description'] : null,
            language: isset($data['language']) && is_scalar($data['language']) ? (string) $data['language'] : $defaultLang,
            slug: isset($data['slug']) && is_scalar($data['slug']) ? (string) $data['slug'] : null,
            shop_id: isset($data['shop_id']) && is_scalar($data['shop_id']) ? (int) $data['shop_id'] : null,
            user_id: $userId,
        );
    }
}
