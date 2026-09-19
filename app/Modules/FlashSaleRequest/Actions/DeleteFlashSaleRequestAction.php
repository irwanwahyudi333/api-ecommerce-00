<?php

declare(strict_types=1);

namespace App\Modules\FlashSaleRequest\Actions;

use App\Models\FlashSale;
use App\Models\FlashSaleRequest;
use App\Models\Product;
use App\Modules\FlashSale\Events\FlashSaleProcessed;

final class DeleteFlashSaleRequestAction
{
    public function execute(FlashSaleRequest $request): void
    {
        // Detach products from main flash sale if already attached
        $flashSale = FlashSale::with('products')->find($request->flash_sale_id);
        $detachedProducts = [];

        if ($flashSale && $request->products->count()) {
            /** @var Product $product */
            foreach ($request->products as $product) {
                if ($flashSale->products->contains((int) $product->id)) {
                    $flashSale->products()->detach($product->id);
                    $attachedProducts[] = $product->id;
                }
            }
            $flashSale->save();
        }

        $eventData = [
            'requested_flash_sale' => $flashSale,
            'detached_products' => $detachedProducts,
        ];
        $language = config('shop.default_language', 'id');
        event(new FlashSaleProcessed('delete_vendor_request', is_string($language) ? $language : 'id', $eventData));

        $request->forceDelete();
    }
}
