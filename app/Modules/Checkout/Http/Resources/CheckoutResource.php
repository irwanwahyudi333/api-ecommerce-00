<?php

declare(strict_types=1);

namespace App\Modules\Checkout\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CheckoutResource extends JsonResource
{
    /**
     * @param array{
     *     total_tax: float,
     *     shipping_charge: float,
     *     unavailable_products: array<int, int>,
     *     wallet_amount: float|int,
     *     wallet_currency: float|string,
     * } $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_tax' => $this['total_tax'],
            'shipping_charge' => $this['shipping_charge'],
            'unavailable_products' => $this['unavailable_products'],
            'wallet_amount' => $this['wallet_amount'],
            'wallet_currency' => $this['wallet_currency'],
        ];
    }
}
