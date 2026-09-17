<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Enums\OrderStatus;
use App\Enums\Permission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OrderAnalyticsQueryService
{
    /**
     * Mendapatkan total jumlah order (dengan cache).
     */
    public function getTotalOrders(?User $user, int $cacheTtl = 300): int
    {
        $isSuperAdmin = $user && $user->hasPermissionTo(Permission::SUPER_ADMIN->value);
        $shopIds = $this->getShopIdsForUser($user);

        $cacheKey = 'analytics_orders_total_'
            .($isSuperAdmin ? 'admin' : implode('_', $shopIds ?? []));

        return Cache::remember($cacheKey, $cacheTtl, function () use ($shopIds, $isSuperAdmin) {
            return $this->calculateTotalOrders($shopIds, $isSuperAdmin);
        });
    }

    /**
     * Menghitung jumlah order per status dalam X hari terakhir (dengan cache).
     *
     * @return array{ pending: int, processing: int, complete: int, cancelled: int, refunded: int, failed: int, localFacility: int, outForDelivery: int }
     */
    public function getOrderStatusCounts(?User $user, int $days = 30, int $cacheTtl = 60): array
    {
        $cacheKey = 'analytics_order_status_'.($user->id ?? 'guest').'_'.$days;

        /** @var array{pending: int, processing: int, complete: int, cancelled: int, refunded: int, failed: int, localFacility: int, outForDelivery: int} $result */
        $result = Cache::remember($cacheKey, $cacheTtl, function () use ($user, $days) {
            return $this->orderCountingByStatus($user, $days);
        });

        return $result;
    }

    /**
     * Internal: hitung total order.
     *
     * @param  array<int>|null  $shopIds
     */
    private function calculateTotalOrders(?array $shopIds, bool $isSuperAdmin): int
    {
        $query = DB::table('orders')->whereDate('created_at', '<=', Carbon::now());

        if ($isSuperAdmin) {
            return $query->whereNull('parent_id')->count();
        }

        return $query->whereIn('shop_id', $shopIds ?? [])->count();
    }

    /**
     * Internal: hitung order per status.
     *
     * @return array{pending: int, processing: int, complete: int, cancelled: int, refunded: int, failed: int, localFacility: int, outForDelivery: int}
     */
    private function orderCountingByStatus(?User $user, int $days): array
    {
        $isSuperAdmin = $user && $user->hasPermissionTo(Permission::SUPER_ADMIN->value);
        $isStoreOwner = $user && $user->hasPermissionTo(Permission::STORE_OWNER->value);
        $isStaff = $user && $user->hasPermissionTo(Permission::STAFF->value);

        $query = DB::table('orders as A')
            ->whereDate('A.created_at', '>', Carbon::now()->subDays($days));

        if ($isSuperAdmin) {
            $query->whereNull('A.parent_id');
        } else {
            $query->whereNotNull('A.parent_id');
        }

        if ($isStoreOwner) {
            /** @var array<int|string> $ids */
            $ids = $user->shops->pluck('id')->toArray();
            $shopIds = array_map('intval', $ids);
            $query->whereIn('A.shop_id', $shopIds);
        } elseif ($isStaff) {
            $shopId = $user->shop_id;
            if ($shopId) {
                $query->where('A.shop_id', $shopId);
            } else {
                return $this->emptyOrderStatusCount();
            }
        } else {
            return $this->emptyOrderStatusCount();
        }

        /** @var array<string, int|string|null> $results */
        $results = $query->select('A.order_status', DB::raw('count(*) as order_count'))
            ->groupBy('A.order_status')
            ->pluck('order_count', 'order_status')
            ->toArray();

        return [
            'pending' => (int) ($results[OrderStatus::PENDING->value] ?? 0),
            'processing' => (int) ($results[OrderStatus::PROCESSING->value] ?? 0),
            'complete' => (int) ($results[OrderStatus::COMPLETED->value] ?? 0),
            'cancelled' => (int) ($results[OrderStatus::CANCELLED->value] ?? 0),
            'refunded' => (int) ($results[OrderStatus::REFUNDED->value] ?? 0),
            'failed' => (int) ($results[OrderStatus::FAILED->value] ?? 0),
            'localFacility' => (int) ($results[OrderStatus::AT_LOCAL_FACILITY->value] ?? 0),
            'outForDelivery' => (int) ($results[OrderStatus::OUT_FOR_DELIVERY->value] ?? 0),
        ];
    }

    /**
     * Helper untuk data kosong.
     *
     * @return array{pending: int, processing: int, complete: int, cancelled: int, refunded: int, failed: int, localFacility: int, outForDelivery: int}
     */
    private function emptyOrderStatusCount(): array
    {
        return [
            'pending' => 0, 'processing' => 0, 'complete' => 0, 'cancelled' => 0,
            'refunded' => 0, 'failed' => 0, 'localFacility' => 0, 'outForDelivery' => 0,
        ];
    }

    /**
     * Mendapatkan daftar shop_id berdasarkan role user.
     *
     * @return int[]|null (null untuk super admin, array kosong jika tidak punya akses)
     */
    private function getShopIdsForUser(?User $user): ?array
    {
        if (! $user) {
            return [];
        }

        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return null; // semua toko
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
}
