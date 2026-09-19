<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OrderCacheService
{
    private const CACHE_TTL = 300; // 5 minutes for list queries

    private const STATS_CACHE_TTL = 600; // 10 minutes for stats

    private const LONG_CACHE_TTL = 3600; // 1 hour for less dynamic data

    public function __construct(
        private OrderQueryService $queryService
    ) {}

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function getCachedOrders(Request $request, User $user, int $perPage = 15): LengthAwarePaginator
    {
        // Don't cache if user has specific filters (dynamic data)
        if ($this->shouldSkipCache($request)) {
            return $this->queryService->getPaginatedOrders($request, $user, $perPage);
        }

        $cacheKey = $this->generateCacheKey('orders', $request, $user);

        return Cache::tags(['orders', "user:{$user->id}"])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($request, $user, $perPage) {
                return $this->queryService->getPaginatedOrders($request, $user, $perPage);
            });
    }

    public function getCachedOrder(string $identifier, Request $request, User $user): Order
    {
        $cacheKey = $this->generateCacheKey("order:{$identifier}", $request, $user);

        return Cache::tags(['orders', "user:{$user->id}", "order:{$identifier}"])
            ->remember($cacheKey, self::LONG_CACHE_TTL, function () use ($identifier, $request, $user) {
                return $this->queryService->getSingleOrder($identifier, $request, $user);
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function getCachedOrderStats(User $user, ?int $shopId = null): array
    {
        $cacheKey = $shopId
            ? "order-stats:shop:{$shopId}"
            : "order-stats:user:{$user->id}";

        return Cache::tags(['order-stats', "user:{$user->id}"])
            ->remember($cacheKey, self::STATS_CACHE_TTL, function () use ($user, $shopId) {
                return $this->queryService->getOrderStats($user, $shopId);
            });
    }

    public function invalidateOrderCache(int $orderId, int $userId): void
    {
        Cache::tags(["order:{$orderId}"])->flush();
        Cache::tags(["user:{$userId}"])->flush();
    }

    public function invalidateShopOrderCache(int $shopId): void
    {
        Cache::tags(["shop:{$shopId}:orders"])->flush();
    }

    public function invalidateAllOrderCache(): void
    {
        Cache::tags(['orders'])->flush();
        Cache::tags(['order-stats'])->flush();
    }

    private function shouldSkipCache(Request $request): bool
    {
        // Skip cache for dynamic queries
        return $request->has('search') ||
               $request->has('start_date') ||
               $request->has('end_date') ||
               $request->has('min_amount') ||
               $request->has('max_amount');
    }

    private function generateCacheKey(string $prefix, Request $request, User $user): string
    {
        $queryParams = $this->getCacheableQueryParams($request);
        $userHash = md5($user->id.$user->email);
        $paramsHash = md5(serialize($queryParams));

        return "{$prefix}:{$userHash}:{$paramsHash}";
    }

    /**
     * @return array<string, mixed>
     */
    private function getCacheableQueryParams(Request $request): array
    {
        return [
            'status' => $request->get('status'),
            'payment_status' => $request->get('payment_status'),
            'shop_id' => $request->get('shop_id'),
            'sort_by' => $request->get('sort_by'),
            'sort_order' => $request->get('sort_order'),
            'limit' => $request->get('limit', 15),
            'page' => $request->get('page', 1),
            'language' => $request->get('language', 'id'),
        ];
    }
}
