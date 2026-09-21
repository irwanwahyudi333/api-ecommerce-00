<?php

declare(strict_types=1);

namespace App\Modules\Tax\DTO;

final class TaxData
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $country,
        public readonly ?string $state,
        public readonly ?string $zip,
        public readonly ?string $city,
        public readonly ?float $rate,
        public readonly ?bool $is_global,
        public readonly ?int $priority,
        public readonly ?bool $on_shipping,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            name: is_string($data['name'] ?? null) ? $data['name'] : null,
            country: is_string($data['country'] ?? null) ? $data['country'] : null,
            state: is_string($data['state'] ?? null) ? $data['state'] : null,
            zip: is_string($data['zip'] ?? null) ? $data['zip'] : null,
            city: is_string($data['city'] ?? null) ? $data['city'] : null,
            rate: is_numeric($data['rate'] ?? null) ? (float) $data['rate'] : null,
            is_global: isset($data['is_global']) ? (bool) $data['is_global'] : false,
            priority: is_numeric($data['priority'] ?? null) ? (int) $data['priority'] : null,
            on_shipping: isset($data['on_shipping']) ? (bool) $data['on_shipping'] : false,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'country' => $this->country,
            'state' => $this->state,
            'zip' => $this->zip,
            'city' => $this->city,
            'rate' => $this->rate,
            'is_global' => $this->is_global,
            'priority' => $this->priority,
            'on_shipping' => $this->on_shipping,
        ], fn ($v) => ! is_null($v));
    }
}
