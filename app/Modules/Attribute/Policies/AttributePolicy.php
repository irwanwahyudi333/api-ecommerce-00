<?php

declare(strict_types=1);

namespace App\Modules\Attribute\Policies;

use App\Enums\Permission;
use App\Models\Attribute;
use App\Models\Shop;
use App\Models\User;

class AttributePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // semua user bisa melihat attribute
    }

    public function view(User $user, Attribute $attribute): bool
    {
        return true; // semua user bisa melihat detail
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::STORE_OWNER->value)
            || $user->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    public function update(User $user, Attribute $attribute): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            /** @var Shop|null $shop */
            $shop = $attribute->shop;

            return $shop && $shop->owner_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Attribute $attribute): bool
    {
        return $this->update($user, $attribute);
    }

    public function export(User $user, int $shopId): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            return $user->shops()->where('id', $shopId)->exists();
        }

        return false;
    }

    public function import(User $user, int $shopId): bool
    {
        return $this->export($user, $shopId);
    }
}
