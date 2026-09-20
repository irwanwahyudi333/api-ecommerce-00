<?php

declare(strict_types=1);

namespace App\Modules\Refund\Policies;

use App\Enums\Permission;
use App\Models\RefundPolicy;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RefundPolicyPolicy
{
    use HandlesAuthorization;

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, RefundPolicy $policy): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value)
            || $user->hasPermissionTo(Permission::STORE_OWNER->value);
    }

    public function update(User $user, RefundPolicy $policy): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            $shop = $user->shops()->first();

            return $shop instanceof Shop && $policy->shop_id === $shop->id;
        }

        return false;
    }

    public function delete(User $user, RefundPolicy $policy): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }
}
