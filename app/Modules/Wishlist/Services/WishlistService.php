<?php

namespace App\Modules\Wishlist\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Modules\Wishlist\DTO\WishlistData;
use Illuminate\Pagination\LengthAwarePaginator;

class WishlistService
{
    /**
     * Ambil semua produk dalam wishlist user (untuk pagination)
     *
     * @return LengthAwarePaginator<int, Product>
     */
    public function getUserWishlistProducts(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $productIds = Wishlist::where('user_id', $user->id)->pluck('product_id');

        return Product::whereIn('id', $productIds)->paginate($perPage);
    }

    /**
     * Cek apakah user sudah menambahkan product ke wishlist
     */
    public function isInWishlist(User $user, int $productId): bool
    {
        return Wishlist::where('user_id', $user->id)->where('product_id', $productId)->exists();
    }

    /**
     * Tambah product ke wishlist (hanya jika belum ada)
     */
    public function addToWishlist(WishlistData $data): ?Wishlist
    {
        $exists = Wishlist::where('user_id', $data->user_id)
            ->where('product_id', $data->product_id)
            ->exists();
        if ($exists) {
            return null;
        }

        return Wishlist::create($data->toArray());
    }

    /**
     * Hapus product dari wishlist
     *
     * @return bool true jika berhasil dihapus
     */
    public function removeFromWishlist(User $user, int $productId): bool
    {
        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();
        if ($wishlist) {
            return (bool) $wishlist->delete();
        }

        return false;
    }

    /**
     * Hapus wishlist berdasarkan id wishlist (bukan product_id)
     * Digunakan di endpoint destroy dengan parameter id wishlist
     */
    public function deleteWishlistById(User $user, int $wishlistId): bool
    {
        $wishlist = Wishlist::where('id', $wishlistId)
            ->where('user_id', $user->id)
            ->first();
        if ($wishlist) {
            return (bool) $wishlist->delete();
        }

        return false;
    }

    /**
     * Toggle wishlist: tambah jika belum ada, hapus jika sudah ada
     *
     * @return bool true jika ditambahkan, false jika dihapus
     */
    public function toggleWishlist(WishlistData $data): bool
    {
        $exists = Wishlist::where('user_id', $data->user_id)
            ->where('product_id', $data->product_id)
            ->exists();
        if ($exists) {
            $this->removeFromWishlistById($data->user_id, $data->product_id);

            return false;
        } else {
            Wishlist::create($data->toArray());

            return true;
        }
    }

    /**
     * Hapus wishlist berdasarkan user_id dan product_id (helper)
     */
    protected function removeFromWishlistById(int $userId, int $productId): void
    {
        Wishlist::where('user_id', $userId)->where('product_id', $productId)->delete();
    }
}
