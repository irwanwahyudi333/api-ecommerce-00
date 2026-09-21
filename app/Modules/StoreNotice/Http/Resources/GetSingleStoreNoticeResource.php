<?php

namespace App\Modules\StoreNotice\Http\Resources;

use App\Models\StoreNotice;
use App\Modules\Shop\Http\Resources\ShopResource;
use App\Modules\User\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property StoreNotice $resource
 */
class GetSingleStoreNoticeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'type' => $this->resource->type,
            'priority' => $this->resource->priority,
            'notice' => $this->resource->notice,
            'description' => $this->resource->description,
            'effective_from' => $this->resource->effective_from,
            'expired_at' => $this->resource->expired_at,
            'creator_role' => $this->resource->creator_role,
            'users' => UserResource::collection($this->whenLoaded('users')), // Use UserResource
            'shops' => ShopResource::collection($this->whenLoaded('shops')), // Use ShopResource
        ];
    }
}
