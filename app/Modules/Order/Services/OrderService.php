<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Enums\Permission;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrderService
{
    public function hasPermission(?User $user, ?int $shopId): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }
        if (! $shopId) {
            return false;
        }

        $shop = Shop::find($shopId);
        if (! $shop) {
            return false;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            return $shop->owner_id === $user->id;
        }
        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $shop->staffs->contains($user->id);
        }

        return false;
    }

    /**
     * @return Builder<Order>
     */
    public function getOrdersQuery(Request $request, User $user): Builder
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return Order::with('children')->whereNull('parent_id');
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            if ($request->shop_id && $this->hasPermission($user, is_numeric($request->shop_id) ? (int) $request->shop_id : 0)) {
                return Order::with('children')->where('shop_id', $request->shop_id)->whereNotNull('parent_id');
            }
            $shopIds = $user->shops()->pluck('shops.id')->toArray();

            return Order::with('children')->whereNotNull('parent_id')->whereIn('shop_id', $shopIds);
        }

        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            if ($request->shop_id && $this->hasPermission($user, is_numeric($request->shop_id) ? (int) $request->shop_id : 0)) {
                return Order::with('children')->where('shop_id', $request->shop_id)->whereNotNull('parent_id');
            }

            return Order::with('children')->whereNotNull('parent_id')->where('shop_id', $user->shop_id);
        }

        return Order::with('children')->where('customer_id', $user->id)->whereNull('parent_id');
    }

    /**
     * @throws AuthorizationException
     */
    public function getOrderByTrackingOrId(string|int $param, string $language, ?User $user = null): Order
    {
        $order = Order::where('language', $language)
            ->with(['products', 'shop', 'children.shop', 'wallet_point'])
            ->where(function ($q) use ($param) {
                $q->where('id', $param)->orWhere('tracking_number', $param);
            })->firstOrFail();

        if ($order->customer_id && $user) {
            if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
                return $order;
            }
            if ($order->shop_id && $this->hasPermission($user, $order->shop_id)) {
                return $order;
            }
            if ($user->id == $order->customer_id) {
                return $order;
            }
            $notice = config('notice.NOT_AUTHORIZED');
            throw new AuthorizationException(is_string($notice) ? $notice : 'Not authorized');
        }

        if (! $order->customer_id) {
            return $order;
        }

        $notice = config('notice.NOT_AUTHORIZED');
        throw new AuthorizationException(is_string($notice) ? $notice : 'Not authorized');
    }
}
