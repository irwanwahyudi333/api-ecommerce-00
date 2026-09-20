<?php

declare(strict_types=1);

namespace App\Modules\Refund\Http\Resources;

use App\Models\Refund;
use App\Modules\Order\Http\Resources\OrderResource;
use App\Modules\RefundReason\Http\Resources\RefundReasonResource;
use App\Modules\User\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Refund
 */
class GetSingleRefundResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'refund_reason' => RefundReasonResource::make($this->whenLoaded('refundReason')),
            'description' => $this->description,
            'amount' => $this->amount,
            'status' => $this->status,
            'images' => $this->images,
            'customer' => UserResource::make($this->whenLoaded('customer')),
            'order' => OrderResource::make($this->whenLoaded('order')),
            'created_at' => $this->created_at,
        ];
    }
}
