<?php

declare(strict_types=1);

namespace App\Modules\PaymentMethod\DTO;

use App\Enums\PaymentMethodType;

final class PaymentMethodData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $method_key,
        public readonly string $method_type,
        public readonly bool $default_payment,
        public readonly string $payment_gateway,
        public readonly ?string $brand = null,
        public readonly ?string $last4 = null,
        public readonly ?string $exp_month = null,
        public readonly ?string $exp_year = null,
        public readonly ?string $va_number = null,
        public readonly ?string $bank_code = null,
        public readonly ?string $qris_url = null,
        public readonly ?string $ewallet_type = null,
        public readonly ?string $direct_debit_type = null,
        public readonly ?string $account_name = null,
        public readonly ?string $account_number = null,
        public readonly array $metadata = [],
    ) {}

    private static function getString(mixed $value): ?string
    {
        return (is_string($value) || is_numeric($value)) ? (string) $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function getArray(mixed $value): array
    {
        /** @var array<string, mixed> $arr */
        $arr = is_array($value) ? $value : [];

        return $arr;
    }

    private static function getBool(mixed $value): bool
    {
        return is_bool($value) ? $value : (bool) $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            method_key: self::getString($data['method_key'] ?? null) ?? '',
            method_type: self::getString($data['method_type'] ?? null) ?? PaymentMethodType::CARD->value,
            default_payment: self::getBool($data['default_payment'] ?? false),
            payment_gateway: self::getString($data['payment_gateway'] ?? null) ?? '',
            brand: self::getString($data['brand'] ?? null),
            last4: self::getString($data['last4'] ?? null),
            exp_month: self::getString($data['exp_month'] ?? null),
            exp_year: self::getString($data['exp_year'] ?? null),
            va_number: self::getString($data['va_number'] ?? null),
            bank_code: self::getString($data['bank_code'] ?? null),
            qris_url: self::getString($data['qris_url'] ?? null),
            ewallet_type: self::getString($data['ewallet_type'] ?? null),
            direct_debit_type: self::getString($data['direct_debit_type'] ?? null),
            account_name: self::getString($data['account_name'] ?? null),
            account_number: self::getString($data['account_number'] ?? null),
            metadata: self::getArray($data['metadata'] ?? []),
        );
    }

    /**
     * @param  array<string, mixed>  $cardData
     */
    public static function fromCard(array $cardData, string $gateway): self
    {
        $card = self::getArray($cardData['card'] ?? []);

        return new self(
            method_key: self::getString($cardData['id'] ?? $cardData['method_key'] ?? null) ?? '',
            method_type: PaymentMethodType::CARD->value,
            default_payment: false,
            payment_gateway: $gateway,
            brand: self::getString($cardData['brand'] ?? $card['brand'] ?? null),
            last4: self::getString($cardData['last4'] ?? $card['last4'] ?? null),
            exp_month: self::getString($cardData['exp_month'] ?? $card['exp_month'] ?? null),
            exp_year: self::getString($cardData['exp_year'] ?? $card['exp_year'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $vaData
     */
    public static function fromVirtualAccount(array $vaData, string $gateway): self
    {
        return new self(
            method_key: self::getString($vaData['id'] ?? $vaData['external_id'] ?? null) ?? uniqid('va_'),
            method_type: PaymentMethodType::VIRTUAL_ACCOUNT->value,
            default_payment: false,
            payment_gateway: $gateway,
            va_number: self::getString($vaData['account_number'] ?? $vaData['va_number'] ?? null),
            bank_code: self::getString($vaData['bank_code'] ?? null),
            metadata: self::getArray($vaData['metadata'] ?? []),
        );
    }

    /**
     * @param  array<string, mixed>  $qrisData
     */
    public static function fromQRIS(array $qrisData, string $gateway): self
    {
        return new self(
            method_key: self::getString($qrisData['id'] ?? $qrisData['external_id'] ?? null) ?? uniqid('qris_'),
            method_type: PaymentMethodType::QRIS->value,
            default_payment: false,
            payment_gateway: $gateway,
            qris_url: self::getString($qrisData['qr_code_url'] ?? $qrisData['qr_string'] ?? null),
            metadata: self::getArray($qrisData['metadata'] ?? []),
        );
    }

    /**
     * @param  array<string, mixed>  $ewalletData
     */
    public static function fromEWallet(array $ewalletData, string $gateway): self
    {
        return new self(
            method_key: self::getString($ewalletData['id'] ?? $ewalletData['reference_id'] ?? null) ?? uniqid('ewallet_'),
            method_type: PaymentMethodType::E_WALLET->value,
            default_payment: false,
            payment_gateway: $gateway,
            ewallet_type: self::getString($ewalletData['ewallet_type'] ?? $ewalletData['channel_code'] ?? null),
            metadata: self::getArray($ewalletData['metadata'] ?? []),
        );
    }
}
