<?php

declare(strict_types=1);

namespace App\Modules\FlashSale\Services;

use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class FlashSaleQueryService
{
    private const CACHE_TTL_SECONDS = 3600; // 1 hour

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, FlashSale>
     */
    public function getFlashSales(array $filters, int $limit = 15): LengthAwarePaginator
    {
        $lang = is_string($filters['language'] ?? null) ? $filters['language'] : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id');
        $language = (string) $lang;
        $cacheKey = 'flash_sales_'.$language.'_'.md5(json_encode($filters) ?: '').'_'.$limit;

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($filters, $language, $limit) {
                $query = FlashSale::where('language', $language);

                if (isset($filters['request_from']) && $filters['request_from'] === 'vendor') {
                    $query->whereDate('start_date', '>', now()->toDateString());
                }

                return $query->paginate($limit);
            }
        );
    }

    /**
     * @return Builder<FlashSale>
     */
    public function getFlashSalesQuery(Request $request): Builder
    {
        $language = $request->language ?? config('shop.default_language', 'id');
        $query = FlashSale::where('language', $language);

        if ($request->request_from === 'vendor') {
            $query->whereDate('start_date', '>', now()->toDateString());
        }

        return $query;
    }

    public function findFlashSaleBySlug(string $slug, string $language): ?FlashSale
    {
        $cacheKey = 'flash_sale_slug_'.$slug.'_'.$language;

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($slug, $language) {
                return FlashSale::where('slug', $slug)
                    ->where('language', $language)
                    ->with('products')
                    ->first();
            }
        );
    }

    public function findOrFail(int $id): FlashSale
    {
        return FlashSale::findOrFail($id);
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getProductsByFlashSaleSlug(string $slug, string $language, int $perPage = 10): LengthAwarePaginator
    {
        $cacheKey = 'flash_sale_products_'.$slug.'_'.$language.'_'.$perPage;

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($slug, $language, $perPage) {
                $productIds = FlashSale::where('slug', $slug)
                    ->where('language', $language)
                    ->join('flash_sale_products', 'flash_sales.id', '=', 'flash_sale_products.flash_sale_id')
                    ->join('products', 'flash_sale_products.product_id', '=', 'products.id')
                    ->select('products.id')
                    ->pluck('id');

                return Product::whereIn('id', $productIds)->paginate($perPage);
            }
        );
    }

    /**
     * @return array<mixed>
     */
    public function getFlashSaleInfoByProductId(int $productId): array
    {
        $cacheKey = 'flash_sale_info_product_'.$productId;

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($productId) {
                $product = Product::with('flashSales')->find($productId);

                return $product ? $product->flashSales->toArray() : [];
            }
        );
    }
}
