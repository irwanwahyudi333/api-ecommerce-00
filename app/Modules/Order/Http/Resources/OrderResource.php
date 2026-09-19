<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Resources;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tracking_number' => $this->tracking_number,
            'customer_id' => $this->customer_id,
            'shop_id' => $this->shop_id,
            'order_status' => $this->order_status,
            'payment_status' => $this->payment_status,
            'amount' => $this->amount,
            'sales_tax' => $this->sales_tax,
            'paid_total' => $this->paid_total,
            'total' => $this->total,
            'delivery_time' => $this->delivery_time,
            'payment_gateway' => $this->payment_gateway,
            'altered_payment_gateway' => $this->altered_payment_gateway,
            'discount' => $this->discount,
            'coupon_id' => $this->coupon_id,
            'logistics_provider' => $this->logistics_provider,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'delivery_fee' => $this->delivery_fee,
            'customer_contact' => $this->customer_contact,
            'customer_name' => $this->customer_name,
            'note' => $this->note,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'products' => $this->whenLoaded('products', function () {
                /** @var Collection<int, Product> $products */
                $products = $this->products;

                return $products->map(function (Product $product) {
                    /** @var Pivot|null $pivot */
                    $pivot = $product->getAttribute('pivot');

                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'pivot' => [
                            'order_quantity' => $pivot ? $pivot->getAttribute('order_quantity') : null,
                            'unit_price' => $pivot ? $pivot->getAttribute('unit_price') : null,
                            'subtotal' => $pivot ? $pivot->getAttribute('subtotal') : null,
                            'variation_option_id' => $pivot ? $pivot->getAttribute('variation_option_id') : null,
                        ],
                    ];
                });
            }),
            'children' => OrderResource::collection($this->whenLoaded('children')),
            'shop' => $this->whenLoaded('shop', fn () => ['id' => $this->shop?->getAttribute('id'), 'name' => $this->shop?->getAttribute('name')]),
            'customer' => $this->whenLoaded('customer', fn () => ['id' => $this->customer?->getAttribute('id'), 'name' => $this->customer?->getAttribute('name')]),
            'wallet_point' => $this->whenLoaded('wallet_point'),
            'payment_intent' => $this->whenLoaded('payment_intent'),
        ];
    }
}
