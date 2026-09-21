<?php

declare(strict_types=1);

namespace App\Modules\StoreNotice\Services;

use App\Enums\Permission;
use App\Enums\StoreNoticeType;
use App\Models\Shop;
use App\Models\StoreNotice;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class StoreNoticeQueryService
{
    /**
     * @return Builder<StoreNotice>
     */
    public function getStoreNoticesQuery(Request $request, ?Authenticatable $authUser): Builder
    {
        /** @var User|null $user */
        $user = $authUser;

        $query = StoreNotice::query()->whereDate('expired_at', '>=', Carbon::now());

        if (! $user) {
            // guest, for shop
            $shopId = $request->shop_id ?? 0;
            if ($shopId) {
                $shop = Shop::where('id', $shopId)->orWhere('slug', $shopId)->first();
                if ($shop) {
                    $query->where('created_by', $shop->owner_id)
                        ->whereHas('shops', fn ($q) => $q->where('id', $shop->id));
                }
            }

            return $query;
        }

        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return $query;
        }

        // authenticated non-admin
        if ($request->shop_id) {
            $shopId = is_numeric($request->shop_id) ? (int) $request->shop_id : 0;
            $shop = Shop::find($shopId);
            if ($shop instanceof Shop) {
                $query->where('created_by', $shop->owner_id)->whereHas('shops', fn ($q) => $q->where('id', $shop->id));
            }
        } elseif ($user->managed_shop) {
            /** @var Shop $managedShop */
            $managedShop = $user->managed_shop;
            $shopId = $managedShop->id;
            $query->where('created_by', $managedShop->owner_id)
                ->whereHas('shops', fn ($q) => $q->where('id', $shopId));
        } else {
            $query->where('created_by', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('id', $user->id));
        }

        return $query;
    }

    public function findOrFail(int $id): StoreNotice
    {
        return StoreNotice::with(['creator', 'users', 'shops', 'read_status'])->findOrFail($id);
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getStoreNoticeTypes(?Authenticatable $authUser): array
    {
        /** @var User|null $user */
        $user = $authUser;

        if ($user && $user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return [
                ['name' => 'ALL VENDOR', 'value' => StoreNoticeType::ALL_VENDOR->value],
                ['name' => 'SPECIFIC VENDOR', 'value' => StoreNoticeType::SPECIFIC_VENDOR->value],
            ];
        }

        return [
            ['name' => 'ALL SHOP', 'value' => StoreNoticeType::ALL_SHOP->value],
            ['name' => 'SPECIFIC SHOP', 'value' => StoreNoticeType::SPECIFIC_SHOP->value],
        ];
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getUsersToNotify(Request $request, ?Authenticatable $authUser): Collection
    {
        /** @var User|null $user */
        $user = $authUser;

        if (! $user) {
            return collect();
        }

        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            /** @var Collection<int, mixed> $result */
            $result = User::permission(Permission::STORE_OWNER->value)->orderBy('name')->get();

            return $result;
        }

        /** @var Collection<int, mixed> $resultShop */
        $resultShop = $user->shops()->where('is_active', true)->get();

        return $resultShop;
    }
}
