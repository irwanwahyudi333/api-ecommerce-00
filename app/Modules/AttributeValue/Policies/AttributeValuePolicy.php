<?php

declare(strict_types=1);

namespace App\Modules\AttributeValue\Policies;

use App\Enums\Permission;
use App\Models\AttributeValue;
use App\Models\Shop;
use App\Models\User;

class AttributeValuePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Attribute values are generally public
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AttributeValue $attributeValue): bool
    {
        return true; // Attribute values are generally public
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?int $shopId = null): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if (! $shopId) {
            return false; // Shop ID is required for store owners/staff
        }

        $shop = Shop::find($shopId);
        if (! $shop || ! $shop->is_active) {
            return false;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            return $shop->owner_id === $user->id;
        }

        if ($user->hasPermissionTo(Permission::STAFF->value) && $user->shop_id === $shopId) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AttributeValue $attributeValue): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value) && $attributeValue->attribute?->shop_id) {
            /** @var Shop|null $shop */
            $shop = Shop::find((int) $attributeValue->attribute->shop_id);

            return $shop && $shop->owner_id === $user->id;
        }

        // Staff permission to update attribute values for their shop
        if ($user->hasPermissionTo(Permission::STAFF->value) && $user->shop_id === $attributeValue->attribute?->shop_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AttributeValue $attributeValue): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value) && $attributeValue->attribute?->shop_id) {
            /** @var Shop|null $shop */
            $shop = Shop::find((int) $attributeValue->attribute->shop_id);

            return $shop && $shop->owner_id === $user->id;
        }

        // Staff permission to delete attribute values for their shop
        if ($user->hasPermissionTo(Permission::STAFF->value) && $user->shop_id === $attributeValue->attribute?->shop_id) {
            return true;
        }

        return false;
    }
}
