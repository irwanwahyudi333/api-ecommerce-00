<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use App\Modules\Product\Http\Resources\GetSingleProductResource;
use App\Modules\Product\Http\Resources\ProductResource;
use App\Modules\Product\Services\ProductCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductQueryController extends BaseController
{
    public function __construct(
        private ProductCacheService $cacheService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $limitParam = $request->get('limit', 15);
        $perPage = is_numeric($limitParam) ? (int) $limitParam : 15;
        $products = $this->cacheService->getCachedProducts($request, $perPage);

        return $this->sendPaginated(
            $products,
            ProductResource::collection($products->getCollection()),
            'Products retrieved successfully'
        );
    }

    public function show(Request $request, string $identifier): JsonResponse
    {
        $product = $this->cacheService->getCachedProduct($identifier, $request);

        $this->authorize('view', $product);

        return $this->sendSuccess(
            new GetSingleProductResource($product),
            'Product retrieved successfully'
        );
    }

    public function showByShop(Request $request, int $shopId): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->cacheService->getCachedShopProducts($shopId, $request);

        return $this->sendPaginated(
            $products,
            ProductResource::collection($products->getCollection()),
            'Shop products retrieved successfully'
        );
    }

    public function popular(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $products = $this->cacheService->getCachedPopularProducts($request);

        return $this->sendSuccess(
            ProductResource::collection($products),
            'Popular products retrieved successfully'
        );
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $qParam = $request->get('q', '');
        $query = is_string($qParam) ? $qParam : '';
        $limitParam = $request->get('limit', 15);
        $perPage = is_numeric($limitParam) ? (int) $limitParam : 15;
        $sort = $request->get('sort', 'created_at');
        $sortColumn = is_string($sort) ? $sort : 'created_at';
        $order = $request->get('order', 'desc');
        $sortOrder = is_string($order) ? $order : 'desc';

        /** @var LengthAwarePaginator<int, Product> $products */
        $products = Product::search($query)
            ->when($request->filled('category'), fn ($q) => $q->where('categories', $request->category))
            ->when($request->filled('price_min'), fn ($q) => $q->where('price', '>=', is_numeric($request->price_min) ? (float) $request->price_min : 0.0))
            ->when($request->filled('price_max'), fn ($q) => $q->where('price', '<=', is_numeric($request->price_max) ? (float) $request->price_max : 0.0))
            ->when($request->filled('in_stock'), fn ($q) => $q->where('in_stock', $request->boolean('in_stock')))
            ->orderBy($sortColumn, $sortOrder)
            ->paginate($perPage);

        return $this->sendPaginated(
            $products,
            ProductResource::collection($products->getCollection()),
            'Search results retrieved successfully'
        );
    }
}
