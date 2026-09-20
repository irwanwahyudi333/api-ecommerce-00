<?php

declare(strict_types=1);

namespace App\Modules\Refund\Policies;

use App\Enums\Permission;
use App\Models\Refund;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RefundPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value)
            || $user->hasPermissionTo(Permission::STORE_OWNER->value)
            || $user->hasPermissionTo(Permission::STAFF->value);
    }

    public function view(User $user, Refund $refund): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            $shop = $user->shops()->first();

            return $shop instanceof Shop && $refund->shop_id === $shop->id;
        }

        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $refund->shop_id === $user->shop_id;
        }

        return $refund->customer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Refund $refund): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            $shop = $user->shops()->first();

            return $shop instanceof Shop && $refund->shop_id === $shop->id;
        }

        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $refund->shop_id === $user->shop_id;
        }

        return false;
    }

    public function delete(User $user, Refund $refund): bool
    {
        return $this->update($user, $refund);
    }
}
