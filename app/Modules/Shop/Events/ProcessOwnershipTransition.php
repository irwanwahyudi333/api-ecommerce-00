<?php

namespace App\Modules\Shop\Events;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcessOwnershipTransition
{
    use Dispatchable, SerializesModels;

    public Shop $shop;

    public User $previousOwner;

    public User $newOwner;

    /** @var array<string, mixed>|null */
    public ?array $optional;

    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>|null  $optional
     */
    public function __construct(Shop $shop, User $previousOwner, User $newOwner, ?array $optional = null)
    {
        $this->shop = $shop;
        $this->previousOwner = $previousOwner;
        $this->newOwner = $newOwner;
        $this->optional = $optional;
    }
}
