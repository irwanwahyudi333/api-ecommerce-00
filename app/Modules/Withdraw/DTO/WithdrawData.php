<?php

namespace App\Modules\Withdraw\DTO;

class WithdrawData
{
    public function __construct(
        public readonly int $shop_id,
        public readonly float $amount,
        public readonly ?string $payment_method,
        public readonly ?string $details,
        public readonly ?string $note,
        public readonly ?string $status,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, ?string $status = null): self
    {
        return new self(
            shop_id: is_numeric($data['shop_id']) ? (int) $data['shop_id'] : 0,
            amount: is_numeric($data['amount']) ? (float) $data['amount'] : 0.0,
            payment_method: isset($data['payment_method']) && is_scalar($data['payment_method']) ? (string) $data['payment_method'] : null,
            details: isset($data['details']) && is_scalar($data['details']) ? (string) $data['details'] : null,
            note: isset($data['note']) && is_scalar($data['note']) ? (string) $data['note'] : null,
            status: $status,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'shop_id' => $this->shop_id,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'details' => $this->details,
            'note' => $this->note,
            'status' => $this->status,
        ], fn ($v) => ! is_null($v));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            shop_id: is_numeric($data['shop_id']) ? (int) $data['shop_id'] : 0,
            amount: is_numeric($data['amount']) ? (float) $data['amount'] : 0.0,
            payment_method: isset($data['payment_method']) && is_scalar($data['payment_method']) ? (string) $data['payment_method'] : null,
            details: isset($data['details']) && is_scalar($data['details']) ? (string) $data['details'] : null,
            note: isset($data['note']) && is_scalar($data['note']) ? (string) $data['note'] : null,
            status: isset($data['status']) && is_scalar($data['status']) ? (string) $data['status'] : null,
        );
    }
}
