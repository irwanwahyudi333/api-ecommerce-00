<?php

namespace App\Modules\Shipping\DTO;

class ShippingData
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?float $amount,
        public readonly ?bool $is_global,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: is_string($data['name']) ? $data['name'] : '',
            type: is_string($data['type']) ? $data['type'] : '',
            amount: isset($data['amount']) && is_numeric($data['amount']) ? (float) $data['amount'] : null,
            is_global: isset($data['is_global']) ? (bool) $data['is_global'] : false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'amount' => $this->amount,
            'is_global' => $this->is_global,
        ], fn ($v) => ! is_null($v));
    }
}
