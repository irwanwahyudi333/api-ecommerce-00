<?php

declare(strict_types=1);

namespace App\Modules\Product\Policies;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(?User $user): bool
    {
        // Siapa pun bisa melihat daftar produk (tampilan publik)
        return true;
    }

    public function view(?User $user, Product $product): bool
    {
        // Produk publik bisa dilihat semua, tapi produk draft/private hanya pemilik atau admin
        if ($product->status === 'publish' || $product->visibility === 'visibility_public') {
            return true;
        }
        if ($user === null) {
            return false;
        }

        return $this->update($user, $product);
    }

    public function create(User $user, ?int $shopId = null): bool
    {
        // Super admin, store owner, atau staff (dengan akses ke toko) bisa membuat
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }
        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            if ($shopId) {
                return $user->shops()->where('id', $shopId)->exists();
            }

            return $user->shops()->exists(); // punya minimal satu toko
        }
        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $shopId && $user->shop_id === $shopId;
        }

        return false;
    }

    public function update(User $user, Product $product): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }
        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            return $product->shop && $product->shop->owner_id === $user->id;
        }
        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $product->shop && $product->shop->staffs->contains($user->id);
        }

        return false;
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }

    public function export(User $user, ?int $shopId = null): bool
    {
        return $this->create($user, $shopId);
    }

    public function import(User $user, ?int $shopId = null): bool
    {
        return $this->create($user, $shopId);
    }

    public function viewMetrics(User $user, Product $product): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        return $this->update($user, $product);
    }

    public function manageRental(User $user, Product $product): bool
    {
        if (! $product->is_rental) {
            return false;
        }

        return $this->update($user, $product);
    }

    public function updateStock(User $user, Product $product): bool
    {
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            return $product->shop && $product->shop->owner_id === $user->id;
        }

        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $product->shop && $product->shop->staffs->contains($user->id);
        }

        return false;
    }

    public function viewWishlist(User $user, Product $product): bool
    {
        return $user->id === $product->author_id || $this->view($user, $product);
    }

    public function viewDrafted(User $user): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value) ||
               $user->hasPermissionTo(Permission::STORE_OWNER->value) ||
               $user->hasPermissionTo(Permission::STAFF->value);
    }

    public function viewStock(User $user): bool
    {
        return $user->hasPermissionTo(Permission::SUPER_ADMIN->value) ||
               $user->hasPermissionTo(Permission::STORE_OWNER->value) ||
               $user->hasPermissionTo(Permission::STAFF->value);
    }
}
