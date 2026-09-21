<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Enums\Permission;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Berisi HANYA query baca (read), dipisah dari UserCommandService (CQRS ringan)
 * supaya operasi yang mengubah state tidak bercampur dengan query laporan/listing.
 */
final class UserQueryService
{
    /**
     * @return Collection<int, User>
     */
    public function getAdminUsers(): Collection
    {
        // Cache 15 menit -> data admin jarang berubah, mengurangi beban DB.
        return Cache::remember('cached_admin', 900, function (): Collection {
            return User::with('profile')
                ->where('is_active', true)
                ->whereHas('permissions', fn (Builder $q) => $q->where('name', Permission::SUPER_ADMIN->value))
                ->get();
        });
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsersByPermission(Permission $permission): Collection
    {
        return User::permission($permission->value)->get();
    }

    public function hasShopAuthority(?Authenticatable $user, ?int $shopId = null): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }

        if (! $shopId) {
            return false;
        }

        $shop = Shop::find($shopId);
        if (! $shop || ! $shop->is_active) {
            return false;
        }

        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            return $shop->owner_id === $user->id;
        }

        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $shop->staffs()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Eager loading wajib (profile, address) -> mencegah N+1 saat di-paginate + di-resource-kan.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginatedVendors(?int $shopId, ?int $exclude, bool $isActive, int $limit): LengthAwarePaginator
    {
        $adminIds = User::whereHas('permissions', fn ($q) => $q->where('name', Permission::SUPER_ADMIN->value))
            ->pluck('id');

        return User::with(['profile', 'address'])
            ->whereHas('permissions', fn ($q) => $q->where('name', Permission::STORE_OWNER->value))
            ->where('is_active', $isActive)
            ->whereNotIn('id', $adminIds)
            ->when($exclude, fn ($q) => $q->where('id', '!=', $exclude))
            ->paginate($limit);
    }
}
