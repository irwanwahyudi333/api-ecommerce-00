<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use App\Modules\Product\Http\Resources\ProductResource;
use App\Modules\Product\Services\ProductMetricService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProductMetricController extends BaseController
{
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private ProductMetricService $metricService
    ) {}

    public function bestSelling(Request $request): JsonResponse
    {
        $this->authorize('viewMetrics', Product::class);

        $cacheKey = 'metrics:best-selling:'.md5($request->fullUrl());

        $products = Cache::tags(['products', 'metrics'])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($request) {
                return $this->metricService->getBestSellingProducts($request);
            });

        return $this->sendSuccess(
            ProductResource::collection($products),
            'Best selling products retrieved successfully'
        );
    }

    public function popular(Request $request): JsonResponse
    {
        $this->authorize('viewMetrics', Product::class);

        $cacheKey = 'metrics:popular:'.md5($request->fullUrl());

        $products = Cache::tags(['products', 'metrics'])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($request) {
                return $this->metricService->getPopularProducts($request);
            });

        return $this->sendSuccess(
            ProductResource::collection($products),
            'Popular products retrieved successfully'
        );
    }

    public function lowStock(Request $request): JsonResponse
    {
        $this->authorize('viewMetrics', Product::class);

        $thresholdParam = $request->get('threshold', 10);
        $threshold = is_numeric($thresholdParam) ? (int) $thresholdParam : 10;
        $cacheKey = 'metrics:low-stock:'.$threshold;

        $products = Cache::tags(['products', 'metrics'])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($request, $threshold) {
                return $this->metricService->getLowStockProducts($request, $threshold);
            });

        return $this->sendSuccess(
            ProductResource::collection($products),
            'Low stock products retrieved successfully'
        );
    }

    public function sales(Request $request): JsonResponse
    {
        $this->authorize('viewMetrics', Product::class);

        $shopId = is_numeric($request->get('shop_id')) ? (int) $request->get('shop_id') : 0;
        $periodParam = $request->get('period', 'week');
        $period = is_string($periodParam) ? $periodParam : 'week';

        $cacheKey = "metrics:sales:{$shopId}:{$period}";

        $metrics = Cache::tags(['products', 'metrics', "shop:{$shopId}"])
            ->remember($cacheKey, self::CACHE_TTL, function () use ($shopId, $period) {
                return $this->metricService->getSalesMetrics($shopId, $period);
            });

        return $this->sendSuccess(
            $metrics,
            'Sales metrics retrieved successfully'
        );
    }
}
