<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ProductCacheService
{
    private const CACHE_TTL = 300; // 5 minutes

    private const LONG_CACHE_TTL = 3600; // 1 hour for less dynamic data

    public function __construct(
        private ProductQueryService $queryService
    ) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getCachedProducts(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey('products', $request);

        return Cache::tags(['products', 'product-list'])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($request, $perPage) {
                return $this->queryService->getPaginatedProducts($request, $perPage);
            });
    }

    public function getCachedProduct(string $identifier, Request $request): Product
    {
        $cacheKey = $this->generateCacheKey("product:{$identifier}", $request);

        return Cache::tags(['products', "product:{$identifier}"])
            ->remember($cacheKey, self::LONG_CACHE_TTL, function () use ($identifier, $request) {
                return $this->queryService->getSingleProduct($identifier, $request);
            });
    }

    /**
     * @return Collection<int, Product>
     */
    public function getCachedPopularProducts(Request $request): Collection
    {
        $cacheKey = $this->generateCacheKey('popular-products', $request);

        return Cache::tags(['products', 'metrics'])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($request) {
                return $this->queryService->getPopularProducts($request);
            });
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getCachedShopProducts(int $shopId, Request $request): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey("shop:{$shopId}:products", $request);

        return Cache::tags(['products', "shop:{$shopId}"])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($shopId, $request) {
                return $this->queryService->getProductsByShop($shopId, $request);
            });
    }

    public function invalidateProductCache(int $productId): void
    {
        Cache::tags(["product:{$productId}"])->flush();
        Cache::tags(['products'])->flush();
    }

    public function invalidateShopCache(int $shopId): void
    {
        Cache::tags(["shop:{$shopId}"])->flush();
        Cache::tags(['products'])->flush();
    }

    public function invalidateAllProductCache(): void
    {
        Cache::tags(['products'])->flush();
    }

    private function generateCacheKey(string $prefix, Request $request): string
    {
        $queryParams = $this->getCacheableQueryParams($request);
        $paramsHash = md5(serialize($queryParams));

        return "{$prefix}:{$paramsHash}";
    }

    /**
     * @return array<string, mixed>
     */
    private function getCacheableQueryParams(Request $request): array
    {
        // Only include cacheable parameters
        return [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
            'category_id' => $request->get('category_id'),
            'type_id' => $request->get('type_id'),
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'is_rental' => $request->get('is_rental'),
            'is_digital' => $request->get('is_digital'),
            'sort_by' => $request->get('sort_by'),
            'sort_order' => $request->get('sort_order'),
            'limit' => $request->get('limit', 15),
            'page' => $request->get('page', 1),
            'language' => $request->get('language', 'id'),
        ];
    }
}
