<?php

declare(strict_types=1);

namespace App\Modules\Address\DTO;

final class AddressData
{
    /**
     * @param  array<string, mixed>  $address
     * @param  array<string, mixed>|null  $location
     */
    public function __construct(
        public readonly string $title,
        public readonly string $type,
        public readonly bool $default,
        public readonly array $address,
        public readonly ?array $location = null,
    ) {}

    /**
     * @param  array{title:string, type:string, default?:bool, address:array<string, mixed>, location?:array<string, mixed>|null}  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            title: $data['title'],
            type: $data['type'],
            default: $data['default'] ?? false,
            address: $data['address'],
            location: $data['location'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'type' => $this->type,
            'default' => $this->default,
            'address' => $this->address,
            'location' => $this->location,
        ], static fn ($value) => $value !== null);
    }
}
