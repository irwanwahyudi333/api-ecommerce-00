<?php

declare(strict_types=1);

namespace App\Modules\RefundReason\DTO;

final class RefundReasonData
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $slug,
        public readonly ?string $language,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $defaultLang = config('shop.default_language', 'id');
        $defaultLanguage = is_string($defaultLang) ? $defaultLang : 'id';

        return new self(
            name: isset($data['name']) && is_string($data['name']) ? $data['name'] : null,
            slug: isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null,
            language: isset($data['language']) && is_string($data['language']) ? $data['language'] : $defaultLanguage,
        );
    }
}
