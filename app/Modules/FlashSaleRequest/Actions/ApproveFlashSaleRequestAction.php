<?php

declare(strict_types=1);

namespace App\Modules\FlashSaleRequest\Actions;

use App\Models\FlashSale;
use App\Models\FlashSaleRequest;
use App\Models\Product;
use App\Modules\FlashSale\Events\FlashSaleProcessed;

final class ApproveFlashSaleRequestAction
{
    public function execute(int $id): void
    {
        $request = FlashSaleRequest::with(['products', 'flashSale'])->findOrFail($id);
        $request->request_status = true;

        $flashSale = FlashSale::with('products')->find($request->flash_sale_id);
        $attachedProducts = [];

        /** @var Product $product */
        foreach ($request->products as $product) {
            if ($flashSale && ! $flashSale->products->contains((int) $product->id)) {
                $flashSale->products()->attach($flashSale->id, ['product_id' => $product->id]);
                $attachedProducts[] = $product->id;
            }
        }
        $request->save();

        $eventData = [
            'attached_product_ids' => $attachedProducts,
            'requested_flash_sale' => $flashSale,
        ];
        $language = config('shop.default_language', 'id');
        event(new FlashSaleProcessed('append_attached_products', is_string($language) ? $language : 'id', $eventData));
    }
}
