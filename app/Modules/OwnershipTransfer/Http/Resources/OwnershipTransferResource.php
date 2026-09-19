<?php

declare(strict_types=1);

namespace App\Modules\OwnershipTransfer\Http\Resources;

use App\Models\OwnershipTransfer;
use App\Modules\Shop\Http\Resources\ShopResource;
use App\Modules\User\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OwnershipTransfer
 */
class OwnershipTransferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_identifier' => $this->transaction_identifier,
            'previous_owner' => new UserResource($this->whenLoaded('previousOwner')),
            'current_owner' => new UserResource($this->whenLoaded('currentOwner')),
            'message' => $this->message,
            'created_by' => $this->created_by,
            'status' => $this->status,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'order_info' => $this->order_info ?? null,
            'balance_info' => $this->balance_info ?? null,
            'refund_info' => $this->refund_info ?? null,
            'withdrawal_info' => $this->withdrawal_info ?? null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
