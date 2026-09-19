<?php

namespace App\Modules\Product\DTO;

class VariationOptionData
{
    /**
     * @param  array<array-key, mixed>|null  $options
     * @param  array<array-key, mixed>|null  $digital_file
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $sku,
        public readonly ?float $price,
        public readonly ?float $sale_price,
        public readonly ?int $quantity,
        public readonly ?array $options,
        public readonly ?bool $is_digital,
        public readonly ?array $digital_file,
        public readonly ?bool $inform_purchased_customer,
        public readonly ?string $product_update_message,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) && is_numeric($data['id']) ? (int) $data['id'] : null,
            sku: isset($data['sku']) && is_string($data['sku']) ? $data['sku'] : null,
            price: isset($data['price']) && is_numeric($data['price']) ? (float) $data['price'] : null,
            sale_price: isset($data['sale_price']) && is_numeric($data['sale_price']) ? (float) $data['sale_price'] : null,
            quantity: isset($data['quantity']) && is_numeric($data['quantity']) ? (int) $data['quantity'] : null,
            options: isset($data['options']) && is_array($data['options']) ? $data['options'] : null,
            is_digital: isset($data['is_digital']) ? (bool) $data['is_digital'] : false,
            digital_file: isset($data['digital_file']) && is_array($data['digital_file']) ? $data['digital_file'] : null,
            inform_purchased_customer: isset($data['inform_purchased_customer']) ? (bool) $data['inform_purchased_customer'] : false,
            product_update_message: isset($data['product_update_message']) && is_string($data['product_update_message']) ? $data['product_update_message'] : null,
        );
    }
}
