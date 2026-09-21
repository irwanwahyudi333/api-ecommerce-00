<?php

declare(strict_types=1);

namespace App\Modules\Terms\Policies;

use App\Enums\Permission;
use App\Models\Shop;
use App\Models\TermsAndConditions;
use App\Models\User;

class TermsPolicy
{
    public function viewAny(?User $user): bool
    {
        return true; // Terms are generally public, but can be filtered by permissions
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, TermsAndConditions $term): bool
    {
        // Public terms are always viewable
        if ($term->is_approved && $term->shop_id === null) {
            return true;
        }

        if (! $user) { // If not public and no user, deny
            return false;
        }

        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value) && $term->shop_id) {
            $shop = Shop::find($term->shop_id);

            return $shop && $shop->owner_id === $user->id;
        }

        if ($user->hasPermissionTo(Permission::STAFF->value) && $term->shop_id) {
            $shop = Shop::find($term->shop_id);

            return $shop && $shop->staffs->contains($user->id);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value)
            || $user->hasPermissionTo(Permission::STORE_OWNER->value);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TermsAndConditions $term): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value) && $term->shop_id) {
            $shop = Shop::find($term->shop_id);

            return $shop && $shop->owner_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TermsAndConditions $term): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value) && $term->shop_id) {
            $shop = Shop::find($term->shop_id);

            return $shop && $shop->owner_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, TermsAndConditions $term): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }

    /**
     * Determine whether the user can disapprove the model.
     */
    public function disapprove(User $user, TermsAndConditions $term): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value);
    }
}
