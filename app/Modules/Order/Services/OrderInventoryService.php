<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Enums\ProductType;
use App\Models\Order;
use App\Models\Product;
use App\Models\Variation;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;

class OrderInventoryService
{
    public function restoreProductInventoryBulk(Order $order): void
    {
        $productIncrements = [];
        $variationIncrements = [];

        // Kumpulkan data ke memori terlebih dahulu untuk optimasi query database
        foreach ($order->products as $product) {
            /** @var Product $product */
            /** @var Pivot|null $pivot */
            $pivot = $product->getAttribute('pivot');
            $orderQty = $pivot ? $pivot->getAttribute('order_quantity') : 1;
            $quantity = is_numeric($orderQty) ? (int) $orderQty : 1;

            $productId = $product->id;
            $productIncrements[$productId] = ($productIncrements[$productId] ?? 0) + $quantity;

            if ($product->product_type === ProductType::VARIABLE->value && $pivot) {
                $varOption = $pivot->getAttribute('variation_option_id');
                if ($varOption) {
                    $varId = is_numeric($varOption) ? (int) $varOption : 0;
                    $variationIncrements[$varId] = ($variationIncrements[$varId] ?? 0) + $quantity;
                }
            }
        }

        // Eksekusi Mass-Update sekaligus dalam satu transaksi database
        DB::transaction(function () use ($productIncrements, $variationIncrements) {
            foreach ($productIncrements as $productId => $qty) {
                Product::where('id', $productId)->increment('quantity', $qty);
            }

            foreach ($variationIncrements as $variationId => $qty) {
                Variation::where('id', $variationId)->increment('quantity', $qty);
            }
        });
    }
}
