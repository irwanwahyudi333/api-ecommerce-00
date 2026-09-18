<?php

declare(strict_types=1);

namespace App\Modules\BecameSeller\DTO;

class BecameSellerData
{
    /**
     * @param  array<string, mixed>  $page_options
     */
    public function __construct(
        public readonly array $page_options,
        public readonly ?string $language,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        $pageOptions = $data['page_options'] ?? [];
        $language = $data['language'] ?? config('shop.default_language', 'id');

        /** @var array<string, mixed> $options */
        $options = is_array($pageOptions) ? $pageOptions : [];

        return new self(
            page_options: $options,
            language: is_scalar($language) ? (string) $language : null,
        );
    }
}
