<?php

declare(strict_types=1);

namespace App\Modules\Order\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Order;
use App\Models\User;
use App\Modules\Order\Http\Resources\OrderResource;
use App\Modules\Order\Services\OrderCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderQueryController extends BaseController
{
    public function __construct(
        private OrderCacheService $cacheService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $limit = $request->get('limit', 15);
        $perPage = is_numeric($limit) ? (int) $limit : 15;
        $user = $request->user();
        if (! $user instanceof User) {
            throw new \Exception('Unauthenticated');
        }
        $orders = $this->cacheService->getCachedOrders($request, $user, $perPage);

        if (! $orders instanceof LengthAwarePaginator) {
            throw new \Exception('Expected LengthAwarePaginator');
        }

        return $this->sendPaginated(
            $orders,
            OrderResource::collection($orders->getCollection()),
            'Orders retrieved successfully'
        );
    }

    public function show(Request $request, string $identifier): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            throw new \Exception('Unauthenticated');
        }
        $order = $this->cacheService->getCachedOrder($identifier, $request, $user);

        $this->authorize('view', $order);

        return $this->sendSuccess(
            new OrderResource($order),
            'Order retrieved successfully'
        );
    }

    public function showByShop(Request $request, int $shopId): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $limit = $request->get('limit', 15);
        $perPage = is_numeric($limit) ? (int) $limit : 15;
        $user = $request->user();
        if (! $user instanceof User) {
            throw new \Exception('Unauthenticated');
        }
        $orders = $this->cacheService->getCachedOrders($request, $user, $perPage);

        if (! $orders instanceof LengthAwarePaginator) {
            throw new \Exception('Expected LengthAwarePaginator');
        }

        return $this->sendPaginated(
            $orders,
            OrderResource::collection($orders->getCollection()),
            'Shop orders retrieved successfully'
        );
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $shopId = $request->get('shop_id');
        $user = $request->user();
        if (! $user instanceof User) {
            throw new \Exception('Unauthenticated');
        }
        $stats = $this->cacheService->getCachedOrderStats($user, is_numeric($shopId) ? (int) $shopId : null);

        return $this->sendSuccess($stats, 'Order statistics retrieved successfully');
    }

    public function myOrders(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            throw new \Exception('Unauthenticated');
        }
        $limit = $request->get('limit', 15);
        $perPage = is_numeric($limit) ? (int) $limit : 15;

        $orders = $this->cacheService->getCachedOrders($request, $user, $perPage);

        if (! $orders instanceof LengthAwarePaginator) {
            throw new \Exception('Expected LengthAwarePaginator');
        }

        return $this->sendPaginated(
            $orders,
            OrderResource::collection($orders->getCollection()),
            'Your orders retrieved successfully'
        );
    }
}
