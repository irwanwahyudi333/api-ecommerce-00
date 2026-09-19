<?php

declare(strict_types=1);

namespace App\Modules\OwnershipTransfer\DTO;

final class OwnershipTransferData
{
    public function __construct(
        public readonly int $shop_id,
        public readonly int $from,
        public readonly int $to,
        public readonly ?string $message,
        public readonly ?int $created_by,
        public readonly ?string $status,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data, int $fromUserId): self
    {
        /** @var int|string|float $shopIdRaw */
        $shopIdRaw = $data['shop_id'];
        /** @var int|string|float $vendorIdRaw */
        $vendorIdRaw = $data['vendor_id'];
        /** @var string|null $message */
        $message = $data['message'] ?? null;

        return new self(
            shop_id: (int) $shopIdRaw,
            from: $fromUserId,
            to: (int) $vendorIdRaw,
            message: $message,
            created_by: $fromUserId,
            status: 'pending',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'shop_id' => $this->shop_id,
            'from' => $this->from,
            'to' => $this->to,
            'message' => $this->message,
            'created_by' => $this->created_by,
            'status' => $this->status,
        ], fn ($v) => ! is_null($v));
    }
}
