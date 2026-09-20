<?php

namespace App\Modules\Shipping\Http\Resources;

use App\Models\Shipping;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shipping
 */
class ShippingResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'is_global' => $this->is_global,
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
