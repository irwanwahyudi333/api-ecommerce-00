<?php

declare(strict_types=1);

namespace App\Modules\Shop\DTO;

final class StaffData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly int $shop_id,
    ) {}

    /**
     * @param  array<string, mixed>  $data  hasil dari FormRequest::validated()
     */
    public static function fromValidated(array $data, int $shopId): self
    {
        return new self(
            name: is_string($data['name']) ? $data['name'] : '',
            email: is_string($data['email']) ? $data['email'] : '',
            password: is_string($data['password']) ? $data['password'] : '',
            shop_id: $shopId,
        );
    }
}
