<?php

namespace App\Modules\Download\Events;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DigitalProductUpdateEvent
{
    use Dispatchable, SerializesModels;

    public Product $product;

    public User $user;

    /** @var array<string, mixed>|null */
    public ?array $optionalData;

    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>|null  $optionalData
     */
    public function __construct(Product $product, User $user, ?array $optionalData = null)
    {
        $this->product = $product;
        $this->user = $user;
        $this->optionalData = $optionalData;
    }
}
