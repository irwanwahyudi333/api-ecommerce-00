<?php

declare(strict_types=1);

namespace App\Modules\Refund\Http\Resources;

use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Refund
 */
class RefundResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'refund_reason' => ['name' => $this->refundReason->name ?? null],
            'amount' => $this->amount,
            'status' => $this->status,
            'customer' => ['email' => $this->customer->email ?? null],
            'order' => [
                'id' => $this->order->id ?? null,
                'tracking_number' => $this->order->tracking_number ?? null,
                'created_at' => $this->created_at,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
