<?php

namespace App\Modules\NotifyLogs\Events;

use App\Models\StoreNotice;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StoreNoticeEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public StoreNotice $storeNotice;

    public string $action;

    public User $actor;

    public function __construct(StoreNotice $storeNotice, string $action, User $actor)
    {
        $this->storeNotice = $storeNotice;
        $this->action = $action;
        $this->actor = $actor;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('store-notice.'.$this->storeNotice->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'store-notice.event';
    }
}
