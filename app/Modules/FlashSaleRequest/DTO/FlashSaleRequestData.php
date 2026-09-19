<?php

declare(strict_types=1);

namespace App\Modules\FlashSaleRequest\DTO;

final class FlashSaleRequestData
{
    /**
     * @param  array<int>|null  $requested_product_ids
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $note,
        public readonly int $flash_sale_id,
        public readonly string $language,
        public readonly ?array $requested_product_ids,
        public readonly ?bool $request_status,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, ?string $language = null): self
    {
        return new self(
            title: is_string($data['title']) ? $data['title'] : (is_scalar($data['title']) ? (string) $data['title'] : ''),
            note: isset($data['note']) && is_string($data['note']) ? $data['note'] : null,
            flash_sale_id: is_numeric($data['flash_sale_id']) ? (int) $data['flash_sale_id'] : 0,
            language: is_string($language) ? $language : (is_string($data['language'] ?? null) ? $data['language'] : 'id'),
            requested_product_ids: isset($data['requested_product_ids']) && is_array($data['requested_product_ids']) ? array_map(fn ($id) => is_numeric($id) ? (int) $id : 0, $data['requested_product_ids']) : null,
            request_status: isset($data['request_status']) ? (bool) $data['request_status'] : false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'note' => $this->note,
            'flash_sale_id' => $this->flash_sale_id,
            'language' => $this->language,
            'request_status' => $this->request_status,
        ], fn ($v) => ! is_null($v));
    }
}
