<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;

final class AnalyticsQueryService
{
    public function __construct(
        private readonly OrderAnalyticsQueryService $orderService,
        private readonly ProductAnalyticsQueryService $productService,
        private readonly RevenueAnalyticsQueryService $revenueService,
    ) {}

    /**
     * Dashboard analytics (dengan cache per user).
     *
     * @return array<string, mixed>
     */
    public function getAnalytics(User $user, int $cacheTtl = 300): array
    {
        $cacheKey = 'analytics_dashboard_'.$user->id;

        return Cache::remember($cacheKey, $cacheTtl, function () use ($user) {
            return $this->buildAnalytics($user);
        });
    }

    /**
     * @return array{
     *     totalRevenue: float,
     *     totalRefunds: float,
     *     totalShops: int,
     *     totalVendors: int,
     *     todaysRevenue: float,
     *     totalOrders: int,
     *     newCustomers: int,
     *     totalYearSaleByMonth: array<int, array{month: string, total: float}>,
     *     todayTotalOrderByStatus: array<string, int>,
     *     weeklyTotalOrderByStatus: array<string, int>,
     *     monthlyTotalOrderByStatus: array<string, int>,
     *     yearlyTotalOrderByStatus: array<string, int>,
     * }
     */
    private function buildAnalytics(User $user): array
    {
        $shopIds = $this->getShopIdsForUser($user);
        $isSuperAdmin = $user->hasPermissionTo(Permission::SUPER_ADMIN->value);

        $totalRevenue = $this->revenueService->getTotalRevenue($shopIds, $isSuperAdmin);
        $todaysRevenue = $this->revenueService->getTodaysRevenue($shopIds, $isSuperAdmin);
        $totalRefunds = $this->revenueService->getTotalRefunds($shopIds, $isSuperAdmin);
        $monthlySales = $this->revenueService->getMonthlySalesData($shopIds, $isSuperAdmin);

        $totalOrders = $this->orderService->getTotalOrders($user);
        $orderStatusesToday = $this->orderService->getOrderStatusCounts($user, 1);
        $orderStatusesWeekly = $this->orderService->getOrderStatusCounts($user, 7);
        $orderStatusesMonthly = $this->orderService->getOrderStatusCounts($user, 30);
        $orderStatusesYearly = $this->orderService->getOrderStatusCounts($user, 365);

        if ($isSuperAdmin) {
            $totalVendors = User::whereHas('permissions', fn ($q) => $q->where('name', Permission::STORE_OWNER->value))->count();
            $totalShops = Shop::count();
        } else {
            $totalShops = Shop::where('owner_id', $user->id)->count();
            $totalVendors = 0;
        }

        $newCustomers = User::permission(Permission::CUSTOMER->value)
            ->where('created_at', '>', Carbon::now()->subDays(30))
            ->count();

        return [
            'totalRevenue' => $totalRevenue,
            'totalRefunds' => $totalRefunds,
            'totalShops' => $totalShops,
            'totalVendors' => $totalVendors,
            'todaysRevenue' => $todaysRevenue,
            'totalOrders' => $totalOrders,
            'newCustomers' => $newCustomers,
            'totalYearSaleByMonth' => $monthlySales,
            'todayTotalOrderByStatus' => $orderStatusesToday,
            'weeklyTotalOrderByStatus' => $orderStatusesWeekly,
            'monthlyTotalOrderByStatus' => $orderStatusesMonthly,
            'yearlyTotalOrderByStatus' => $orderStatusesYearly,
        ];
    }

    /**
     * @return int[]|null (null = semua toko, [] = tidak ada akses)
     */
    private function getShopIdsForUser(?User $user): ?array
    {
        if (! $user) {
            return [];
        }

        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return null;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            /** @var array<int|string> $ids */
            $ids = $user->shops()->pluck('shops.id')->toArray();

            return array_map('intval', $ids);
        }

        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $user->shop_id ? [$user->shop_id] : [];
        }

        return [];
    }

    public function resolveTypeId(?int $typeId, ?string $typeSlug, string $language): ?int
    {
        if ($typeId) {
            return $typeId;
        }

        if ($typeSlug) {
            $type = Type::where('slug', $typeSlug)
                ->where('language', $language)
                ->first();

            return $type?->id;
        }

        return null;
    }

    /**
     * @return EloquentCollection<int, Product>
     */
    public function getLowStockProducts(
        User $user,
        string $language,
        ?int $typeId = null,
        ?int $shopId = null,
        int $limit = 10
    ): EloquentCollection {
        return $this->productService->getLowStockProducts($user, $language, $typeId, $shopId, $limit);
    }

    /**
     * @return array<int, array{category_id: int, category_name: string, shop_name: string, product_count: int}>
     */
    public function categoryWiseProductCount(User $user, string $language, int $limit = 15): array
    {
        return $this->productService->getCategoryWiseProductCount($user, $language, $limit);
    }

    /**
     * @return array<int, array{category_id: int, category_name: string, total_sales: float}>
     */
    public function categoryWiseProductSales(User $user, string $language, int $limit = 15): array
    {
        return $this->productService->getCategoryWiseProductSales($user, $language, $limit);
    }

    /**
     * @return EloquentCollection<int, Product>
     */
    public function topRatedProducts(User $user, string $language, int $limit = 10): EloquentCollection
    {
        return $this->productService->getTopRatedProducts($user, $language, $limit);
    }
}
