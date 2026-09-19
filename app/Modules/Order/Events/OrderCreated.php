<?php

namespace App\Modules\Order\Events;

use App\Models\Order;
use App\Models\User;

class OrderCreated
{
    public Order $order;

    /**
     * @var array<string, mixed>
     */
    public array $invoiceData;

    public ?User $user;

    /**
     * @param  array<string, mixed>  $invoiceData
     */
    public function __construct(Order $order, array $invoiceData, ?User $user = null)
    {
        $this->order = $order;
        $this->invoiceData = $invoiceData;
        $this->user = $user;
    }
}
