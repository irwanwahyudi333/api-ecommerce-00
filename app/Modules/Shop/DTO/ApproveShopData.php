<?php

declare(strict_types=1);

namespace App\Modules\Shop\DTO;

/**
 * DTO khusus untuk ApproveShopAction. Dipisah dari ShopData agar field
 * finansial (admin_commission_rate) tidak pernah bisa "menumpang" lewat
 * endpoint create/update toko biasa — hanya bisa lewat endpoint approve
 * yang dilindungi Policy::approve() (SUPER_ADMIN only).
 */
final class ApproveShopData
{
    public function __construct(
        public readonly ?float $admin_commission_rate,
        public readonly bool $is_custom_commission,
    ) {}

    /**
     * @param  array<string, mixed>  $data  hasil dari FormRequest::validated()
     */
    public static function fromValidated(array $data): self
    {
        return new self(
            admin_commission_rate: isset($data['admin_commission_rate']) && is_numeric($data['admin_commission_rate'])
                ? (float) $data['admin_commission_rate']
                : null,
            is_custom_commission: (bool) ($data['is_custom_commission'] ?? false),
        );
    }
}
