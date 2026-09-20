<?php

declare(strict_types=1);

namespace App\Modules\Refund\DTO;

use App\Models\User;
use Illuminate\Http\Request;

class RefundData
{
    /**
     * @param  array<mixed>|null  $images
     */
    public function __construct(
        public readonly int $orderId,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?array $images,
        public readonly ?int $refundReasonId,
        public readonly ?int $customerId,
        public readonly ?int $shopId,
        public readonly ?float $amount,
        public readonly ?string $status,
    ) {}

    public static function fromRequest(Request $request, ?int $customerId = null, ?int $shopId = null): self
    {
        $rawOrderId = $request->input('order_id');
        $orderId = is_numeric($rawOrderId) ? (int) $rawOrderId : 0;
        $title = is_string($request->input('title')) ? $request->input('title') : null;
        $description = is_string($request->input('description')) ? $request->input('description') : null;
        $images = is_array($request->input('images')) ? $request->input('images') : null;
        $refundReasonId = is_numeric($request->input('refund_reason_id')) ? (int) $request->input('refund_reason_id') : null;

        $user = $request->user();
        $userCustomerId = $user instanceof User ? (int) $user->id : null;
        $finalCustomerId = $customerId ?? $userCustomerId;

        $rawShopId = $request->input('shop_id');
        $shopIdInput = is_numeric($rawShopId) ? (int) $rawShopId : null;
        $finalShopId = $shopId ?? $shopIdInput;

        $rawAmount = $request->input('amount');
        $amount = is_numeric($rawAmount) ? (float) $rawAmount : null;
        $status = is_string($request->input('status')) ? $request->input('status') : 'pending';

        return new self(
            orderId: $orderId,
            title: $title,
            description: $description,
            images: $images,
            refundReasonId: $refundReasonId,
            customerId: $finalCustomerId,
            shopId: $finalShopId,
            amount: $amount,
            status: $status,
        );
    }

    public function withCustomerId(int $customerId): self
    {
        return new self(
            orderId: $this->orderId,
            title: $this->title,
            description: $this->description,
            images: $this->images,
            refundReasonId: $this->refundReasonId,
            customerId: $customerId,
            shopId: $this->shopId,
            amount: $this->amount,
            status: $this->status,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'title' => $this->title,
            'description' => $this->description,
            'images' => $this->images,
            'refund_reason_id' => $this->refundReasonId,
            'customer_id' => $this->customerId,
            'shop_id' => $this->shopId,
            'amount' => $this->amount,
            'status' => $this->status,
        ];
    }
}
