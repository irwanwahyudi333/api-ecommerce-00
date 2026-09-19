<?php

declare(strict_types=1);

namespace App\Modules\FlashSale\DTO;

final class FlashSaleData
{
    /**
     * @param  array<mixed>|null  $image
     * @param  array<mixed>|null  $cover_image
     * @param  array<mixed>|null  $sale_builder
     */
    public function __construct(
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $start_date,
        public readonly ?string $end_date,
        public readonly ?string $language,
        public readonly ?string $slug,
        public readonly ?array $image,
        public readonly ?array $cover_image,
        public readonly ?float $rate,
        public readonly ?string $type,
        public readonly ?string $sale_status,
        public readonly ?array $sale_builder,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            title: isset($data['title']) && is_string($data['title']) ? $data['title'] : null,
            description: isset($data['description']) && is_string($data['description']) ? $data['description'] : null,
            start_date: isset($data['start_date']) && is_string($data['start_date']) ? $data['start_date'] : null,
            end_date: isset($data['end_date']) && is_string($data['end_date']) ? $data['end_date'] : null,
            language: isset($data['language']) && is_string($data['language']) ? $data['language'] : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id'),
            slug: isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null,
            image: isset($data['image']) && is_array($data['image']) ? $data['image'] : null,
            cover_image: isset($data['cover_image']) && is_array($data['cover_image']) ? $data['cover_image'] : null,
            rate: isset($data['rate']) && is_numeric($data['rate']) ? (float) $data['rate'] : null,
            type: isset($data['type']) && is_string($data['type']) ? $data['type'] : null,
            sale_status: isset($data['sale_status']) && is_string($data['sale_status']) ? $data['sale_status'] : null,
            sale_builder: isset($data['sale_builder']) && is_array($data['sale_builder']) ? $data['sale_builder'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'language' => $this->language,
            'slug' => $this->slug,
            'image' => $this->image,
            'cover_image' => $this->cover_image,
            'rate' => $this->rate,
            'type' => $this->type,
            'sale_status' => $this->sale_status,
            'sale_builder' => $this->sale_builder,
        ], fn ($v) => ! is_null($v));
    }
}
