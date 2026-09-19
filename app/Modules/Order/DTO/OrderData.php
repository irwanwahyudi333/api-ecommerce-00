<?php

declare(strict_types=1);

namespace App\Modules\Order\DTO;

final class OrderData
{
    /**
     * @param  array<array-key, mixed>|null  $products
     * @param  array<array-key, mixed>|null  $billing_address
     * @param  array<array-key, mixed>|null  $shipping_address
     */
    public function __construct(
        public ?string $tracking_number = null,
        public ?int $customer_id = null,
        public ?int $shop_id = null,
        public ?string $language = null,
        public ?string $order_status = null,
        public ?string $payment_status = null,
        public ?float $amount = null,
        public ?float $sales_tax = 0.0,
        public ?float $paid_total = null,
        public ?float $total = null,
        public ?string $delivery_time = null,
        public ?string $payment_gateway = null,
        public ?string $altered_payment_gateway = null,
        public ?float $discount = 0.0,
        public ?int $coupon_id = null,
        public ?string $logistics_provider = null,
        public ?array $billing_address = null,
        public ?array $shipping_address = null,
        public ?float $delivery_fee = 0.0,
        public ?string $customer_contact = null,
        public ?string $customer_name = null,
        public ?string $note = null,
        public ?int $parent_id = null,
        public ?array $products = null,
        public ?bool $use_wallet_points = false,
        public ?bool $isFullWalletPayment = false,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        /** @var string $defaultLang */
        $defaultLang = is_string(config('shop.default_language', 'id')) ? config('shop.default_language', 'id') : 'id';

        return new self(
            tracking_number: is_scalar($data['tracking_number'] ?? null) ? (string) $data['tracking_number'] : null,
            customer_id: is_numeric($data['customer_id'] ?? null) ? (int) $data['customer_id'] : null,
            shop_id: is_numeric($data['shop_id'] ?? null) ? (int) $data['shop_id'] : null,
            language: is_scalar($data['language'] ?? null) ? (string) $data['language'] : $defaultLang,
            order_status: is_scalar($data['order_status'] ?? null) ? (string) $data['order_status'] : null,
            payment_status: is_scalar($data['payment_status'] ?? null) ? (string) $data['payment_status'] : null,
            amount: is_numeric($data['amount'] ?? null) ? (float) $data['amount'] : null,
            sales_tax: is_numeric($data['sales_tax'] ?? null) ? (float) $data['sales_tax'] : 0.0,
            paid_total: is_numeric($data['paid_total'] ?? null) ? (float) $data['paid_total'] : null,
            total: is_numeric($data['total'] ?? null) ? (float) $data['total'] : null,
            delivery_time: is_scalar($data['delivery_time'] ?? null) ? (string) $data['delivery_time'] : null,
            payment_gateway: is_scalar($data['payment_gateway'] ?? null) ? (string) $data['payment_gateway'] : null,
            altered_payment_gateway: is_scalar($data['altered_payment_gateway'] ?? null) ? (string) $data['altered_payment_gateway'] : null,
            discount: is_numeric($data['discount'] ?? null) ? (float) $data['discount'] : 0.0,
            coupon_id: is_numeric($data['coupon_id'] ?? null) ? (int) $data['coupon_id'] : null,
            logistics_provider: is_scalar($data['logistics_provider'] ?? null) ? (string) $data['logistics_provider'] : null,
            billing_address: is_array($data['billing_address'] ?? null) ? $data['billing_address'] : null,
            shipping_address: is_array($data['shipping_address'] ?? null) ? $data['shipping_address'] : null,
            delivery_fee: is_numeric($data['delivery_fee'] ?? null) ? (float) $data['delivery_fee'] : 0.0,
            customer_contact: is_scalar($data['customer_contact'] ?? null) ? (string) $data['customer_contact'] : null,
            customer_name: is_scalar($data['customer_name'] ?? null) ? (string) $data['customer_name'] : null,
            note: is_scalar($data['note'] ?? null) ? (string) $data['note'] : null,
            parent_id: is_numeric($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null,
            products: is_array($data['products'] ?? null) ? $data['products'] : null,
            use_wallet_points: is_scalar($data['use_wallet_points'] ?? null) ? (bool) $data['use_wallet_points'] : false,
            isFullWalletPayment: is_scalar($data['isFullWalletPayment'] ?? null) ? (bool) $data['isFullWalletPayment'] : false,
        );

    }
}
