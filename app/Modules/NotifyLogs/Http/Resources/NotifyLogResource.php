<?php

declare(strict_types=1);

namespace App\Modules\NotifyLogs\Http\Resources;

use App\Models\NotifyLogs;
use App\Modules\User\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin NotifyLogs
 */
class NotifyLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'receiver' => $this->receiver,
            'sender' => $this->sender,
            'notify_type' => $this->notify_type,
            'notify_receiver_type' => $this->notify_receiver_type,
            'is_read' => (bool) $this->is_read,
            'notify_text' => $this->notify_text,
            'notify_tracker' => $this->notify_tracker,
            'created_at' => $this->created_at?->toISOString(),
            'sender_user' => new UserResource($this->whenLoaded('senderUser')),
        ];
    }
}
