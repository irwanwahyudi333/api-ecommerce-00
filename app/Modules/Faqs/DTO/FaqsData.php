<?php

declare(strict_types=1);

namespace App\Modules\Faqs\DTO;

final class FaqsData
{
    public function __construct(
        public readonly ?string $faq_title,
        public readonly ?string $faq_description,
        public readonly ?string $language,
        public readonly ?string $slug,
        public readonly ?int $user_id,
        public readonly ?int $shop_id,
        public readonly ?string $faq_type,
        public readonly ?string $issued_by,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, ?int $userId = null): self
    {
        $defaultLanguage = config('shop.default_language', 'id');

        return new self(
            faq_title: isset($data['faq_title']) && is_string($data['faq_title']) ? $data['faq_title'] : null,
            faq_description: isset($data['faq_description']) && is_string($data['faq_description']) ? $data['faq_description'] : null,
            language: isset($data['language']) && is_string($data['language']) ? $data['language'] : (is_string($defaultLanguage) ? $defaultLanguage : 'id'),
            slug: isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null,
            user_id: $userId,
            shop_id: isset($data['shop_id']) && is_numeric($data['shop_id']) ? (int) $data['shop_id'] : null,
            faq_type: isset($data['faq_type']) && is_string($data['faq_type']) ? $data['faq_type'] : null,
            issued_by: isset($data['issued_by']) && is_string($data['issued_by']) ? $data['issued_by'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'faq_title' => $this->faq_title,
            'faq_description' => $this->faq_description,
            'language' => $this->language,
            'slug' => $this->slug,
            'user_id' => $this->user_id,
            'shop_id' => $this->shop_id,
            'faq_type' => $this->faq_type,
            'issued_by' => $this->issued_by,
        ], fn ($v) => ! is_null($v));
    }
}
