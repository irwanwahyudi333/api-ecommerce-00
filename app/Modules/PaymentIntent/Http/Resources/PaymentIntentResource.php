<?php

declare(strict_types=1);

namespace App\Modules\PaymentIntent\Http\Resources;

use App\Models\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PaymentIntent
 */
class PaymentIntentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'tracking_number' => $this->tracking_number,
            'payment_gateway' => $this->payment_gateway,
            'payment_intent_info' => $this->payment_intent_info,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
