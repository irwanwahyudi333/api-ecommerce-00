<?php

declare(strict_types=1);

namespace App\Modules\User\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => (bool) $this->email_verified,
            'is_active' => (bool) $this->is_active,
            // shop_id sengaja hanya ditampilkan jika requester berhak melihatnya,
            // mencegah kebocoran data internal (information disclosure) ke publik.
            'shop_id' => $this->when(
                $request->user()?->can('viewShopAssignment', $this->resource) ?? false,
                $this->shop_id
            ),
            'profile' => $this->whenLoaded('profile'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
