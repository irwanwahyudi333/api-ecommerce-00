<?php

declare(strict_types=1);

namespace App\Modules\Conversation\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                ];
            }),
            'shop' => $this->whenLoaded('shop', function () {
                return [
                    'id' => $this->shop?->id,
                    'name' => $this->shop?->name,
                ];
            }),
            'latest_message' => $this->latest_message, // dari append
            'unseen' => is_numeric($this->unseen) ? (int) $this->unseen : 0, // dari append
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
