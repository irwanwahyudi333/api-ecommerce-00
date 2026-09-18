<?php

declare(strict_types=1);

namespace App\Modules\Checkout\DTO;

class CheckoutVerifyData
{
    /**
     * @param  array<int, array{product_id: int, order_quantity: int, variation_option_id?: int|null, unit_price?: numeric, subtotal?: numeric}>  $products
     * @param  array<string, mixed>|null  $billing_address
     * @param  array<string, mixed>|null  $shipping_address
     */
    public function __construct(
        public readonly ?int $customer_id,
        public readonly array $products,
        public readonly ?array $billing_address,
        public readonly ?array $shipping_address,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        /** @var array<int, array{product_id: int, order_quantity: int, variation_option_id?: int|null, unit_price?: numeric, subtotal?: numeric}> $products */
        $products = $data['products'] ?? [];
        /** @var array<string, mixed>|null $billingAddress */
        $billingAddress = $data['billing_address'] ?? null;
        /** @var array<string, mixed>|null $shippingAddress */
        $shippingAddress = $data['shipping_address'] ?? null;

        return new self(
            // amount: $data['amount'],
            customer_id: isset($data['customer_id']) && is_scalar($data['customer_id']) ? (int) $data['customer_id'] : null,
            products: $products,
            billing_address: $billingAddress,
            shipping_address: $shippingAddress,
        );
    }
}
