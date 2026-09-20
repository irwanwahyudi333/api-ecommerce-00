<?php

declare(strict_types=1);

namespace App\Modules\Refund\Events;

use App\Models\Refund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public Refund $refund) {}
}
