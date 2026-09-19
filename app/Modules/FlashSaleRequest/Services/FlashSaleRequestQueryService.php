<?php

declare(strict_types=1);

namespace App\Modules\FlashSaleRequest\Services;

use App\Models\FlashSaleRequest;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class FlashSaleRequestQueryService
{
    /**
     * @return Builder<FlashSaleRequest>
     */
    public function getRequestsQuery(Request $request): Builder
    {
        $language = $request->language ?? config('shop.default_language', 'id');

        return FlashSaleRequest::where('language', $language)->with(['products', 'flashSale']);
    }

    public function findOrFail(int $id): FlashSaleRequest
    {
        return FlashSaleRequest::with(['products', 'flashSale'])->findOrFail($id);
    }

    /**
     * @return Builder<Product>
     */
    public function getRequestedProductsQuery(Request $request, int $vendorRequestId): Builder
    {
        $language = $request->language ?? config('shop.default_language', 'id');
        $productIds = FlashSaleRequest::where('id', $vendorRequestId)
            ->where('language', $language)
            ->join('flash_sale_requests_products', 'flash_sale_requests.id', '=', 'flash_sale_requests_products.flash_sale_requests_id')
            ->join('products', 'flash_sale_requests_products.product_id', '=', 'products.id')
            ->select('products.id')
            ->pluck('id');

        return Product::whereIn('id', $productIds);
    }
}
