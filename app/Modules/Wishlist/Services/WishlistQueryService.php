<?php

declare(strict_types=1);

namespace App\Modules\Wishlist\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Pagination\LengthAwarePaginator;

final class WishlistQueryService
{
    /**
     * Get all products in a user's wishlist (for pagination)
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function getUserWishlistProducts(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $productIds = Wishlist::where('user_id', $user->id)->pluck('product_id');

        return Product::whereIn('id', $productIds)->paginate($perPage);
    }

    /**
     * Check if a product is already in the user's wishlist
     */
    public function isInWishlist(User $user, int $productId): bool
    {
        return Wishlist::where('user_id', $user->id)->where('product_id', $productId)->exists();
    }
}
