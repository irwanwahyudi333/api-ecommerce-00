<?php

declare(strict_types=1);

namespace App\Modules\FlashSaleRequest\Actions;

use App\Models\FlashSale;
use App\Models\FlashSaleRequest;
use App\Models\Product;
use App\Modules\FlashSale\Events\FlashSaleProcessed;

final class DisapproveFlashSaleRequestAction
{
    public function execute(int $id): void
    {
        $request = FlashSaleRequest::with(['products', 'flashSale'])->findOrFail($id);
        $request->request_status = false;

        $flashSale = FlashSale::with('products')->find($request->flash_sale_id);
        $detachedProducts = [];

        /** @var Product $product */
        foreach ($request->products as $product) {
            if ($flashSale && $flashSale->products->contains((int) $product->id)) {
                $flashSale->products()->detach($product->id);
                $detachedProducts[] = $product->id;
            }
        }
        if ($flashSale) {
            $flashSale->save();
        }
        $request->save();

        $eventData = [
            'detached_product_ids' => $detachedProducts,
            'requested_flash_sale' => $flashSale,
        ];
        $language = config('shop.default_language', 'id');
        event(new FlashSaleProcessed('remove_attached_products', is_string($language) ? $language : 'id', $eventData));
    }
}
