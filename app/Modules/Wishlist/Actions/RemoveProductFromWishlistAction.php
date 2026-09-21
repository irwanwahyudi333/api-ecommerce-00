<?php

declare(strict_types=1);

namespace App\Modules\Wishlist\Actions;

use App\Models\User;
use App\Models\Wishlist;

final class RemoveProductFromWishlistAction
{
    /**
     * @return bool true if successfully deleted, false otherwise
     */
    public function execute(User $user, int $productId): bool
    {
        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();
        if ($wishlist) {
            return (bool) $wishlist->delete();
        }

        return false;
    }
}
