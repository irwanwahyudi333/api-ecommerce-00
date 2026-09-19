<?php

declare(strict_types=1);

namespace App\Modules\Manufacturer\Policies;

use App\Enums\Permission;
use App\Models\Manufacturer;
use App\Models\User;

class ManufacturerPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Manufacturer $manufacturer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value)
            || $user->hasPermissionTo(Permission::STORE_OWNER->value);
    }

    public function update(User $user, Manufacturer $manufacturer): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            /** @var int|null $shopId */
            $shopId = $manufacturer->getAttribute('shop_id');

            return $shopId && $user->shops()->where('id', $shopId)->exists();
        }

        return false;
    }

    public function delete(User $user, Manufacturer $manufacturer): bool
    {
        return $this->update($user, $manufacturer);
    }
}
