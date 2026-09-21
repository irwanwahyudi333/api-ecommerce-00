<?php

namespace App\Modules\Shop\Events;

use App\Models\Balance;
use App\Models\Shop;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable; // Asumsi model Shop ada di App\Models
use Illuminate\Queue\SerializesModels; // Asumsi model Balance ada di App\Models

class CommissionRateUpdateEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Shop $shop;

    public Balance $balance;

    public function __construct(Shop $shop, Balance $balance)
    {
        $this->shop = $shop;
        $this->balance = $balance;
    }
}
