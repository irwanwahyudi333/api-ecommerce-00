<?php

declare(strict_types=1);

namespace App\Modules\Order\Policies;

use App\Enums\Permission;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value)
            || $user->hasPermissionTo(Permission::STORE_OWNER->value)
            || $user->hasPermissionTo(Permission::STAFF->value);
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            /** @var Shop|null $shop */
            $shop = $order->shop;

            return $shop && $shop->owner_id === $user->id;
        }

        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            /** @var Shop|null $shop */
            $shop = $order->shop;

            return $shop && $shop->staffs()->where('id', $user->id)->exists();
        }

        return $order->customer_id === $user->id;
    }

    public function create(User $user): bool
    {
        // Setiap user yang login bisa membuat order (checkout)
        return true;
    }

    public function update(User $user, Order $order): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            /** @var Shop|null $shop */
            $shop = $order->shop;

            return $shop && $shop->owner_id === $user->id;
        }

        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            /** @var Shop|null $shop */
            $shop = $order->shop;

            return $shop && $shop->staffs()->where('id', $user->id)->exists();
        }

        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return $this->update($user, $order);
    }

    public function export(User $user, ?int $shopId = null): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            if ($shopId) {
                return $user->shops()->where('id', $shopId)->exists();
            }

            return true;
        }

        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $shopId && $user->shop_id === $shopId;
        }

        return false;
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $this->update($user, $order);
    }

    public function cancel(User $user, Order $order): bool
    {
        // Customer can only cancel their own pending orders
        if ($order->customer_id === $user->id) {
            return in_array($order->order_status, ['order-pending', 'order-processing']);
        }

        // Admins can always cancel
        return $this->update($user, $order);
    }

    public function updatePaymentStatus(User $user, Order $order): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            /** @var Shop|null $shop */
            $shop = $order->shop;

            return $shop && $shop->owner_id === $user->id;
        }

        return false;
    }

    public function viewStats(User $user): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value)
            || $user->hasPermissionTo(Permission::STORE_OWNER->value)
            || $user->hasPermissionTo(Permission::STAFF->value);
    }
}
