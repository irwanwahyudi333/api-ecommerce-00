<?php

declare(strict_types=1);

namespace App\Modules\PaymentMethod\Http\Resources;

use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PaymentMethod
 *
 * @property-read PaymentGateway $paymentGateway
 * @property string $brand
 * @property string $exp_month
 * @property string $exp_year
 */
class PaymentMethodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'method_key' => $this->method_key,
            'method_type' => $this->method_type,
            'default_payment' => (bool) $this->default_payment,
            'fingerprint' => $this->fingerprint,
            'brand' => $this->brand,
            'last4' => $this->last4,
            'exp_month' => $this->exp_month,
            'exp_year' => $this->exp_year,
            'payment_gateway_id' => $this->payment_gateway_id,
            'va_number' => $this->va_number,
            'bank_code' => $this->bank_code,
            'qris_url' => $this->qris_url,
            'ewallet_type' => $this->ewallet_type,
            'account_name' => $this->account_name,
            'account_last4' => $this->account_last4,
            'expiry_date' => $this->expiry_date?->toISOString(),
            'payment_gateway' => $this->whenLoaded('paymentGateway', function () {
                return [
                    'id' => $this->paymentGateway->id,
                    'gateway_name' => $this->paymentGateway->gateway_name,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
