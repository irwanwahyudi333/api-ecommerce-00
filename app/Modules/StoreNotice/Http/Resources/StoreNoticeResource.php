<?php

namespace App\Modules\StoreNotice\Http\Resources;

use App\Models\StoreNotice;
use App\Models\User;
use App\Modules\Shop\Http\Resources\ShopResource;
use App\Modules\User\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @property StoreNotice $resource
 */
class StoreNoticeResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var User|null $creator */
        $creator = $this->resource->creator;

        return [
            'id' => $this->resource->id,
            'type' => $this->resource->type,
            'priority' => $this->resource->priority,
            'notice' => $this->resource->notice,
            'description' => $this->resource->description,
            'effective_from' => $this->resource->effective_from,
            'expired_at' => $this->resource->expired_at,
            'creator_role' => $this->resource->creator_role,
            'is_read' => $this->resource->is_read,
            'creator' => $creator ? [
                'id' => $creator->id,
                'name' => $creator->name,
                'email' => $creator->email,
            ] : null,
            'users' => UserResource::collection($this->whenLoaded('users')),
            'shops' => ShopResource::collection($this->whenLoaded('shops')),
            'read_status' => $this->readStatusCollection(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function readStatusCollection(): Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, User> $readStatus */
        $readStatus = $this->resource->read_status;

        /** @var Collection<int, array<string, mixed>> $mapped */
        $mapped = $readStatus->map(function ($user) {
            /** @var User $user */
            /** @var mixed $pivot */
            $pivot = $user->pivot ?? null;
            $isRead = is_object($pivot) && isset($pivot->is_read) ? $pivot->is_read : false;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_read' => $isRead,
                'pivot' => $pivot,
            ];
        });

        return $mapped;
    }
}
