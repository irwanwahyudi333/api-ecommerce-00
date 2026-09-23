<?php

declare(strict_types=1);

namespace App\Modules\Shop\Services;

use App\Enums\Permission;
use App\Models\Product;
use App\Models\Settings;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class ShopQueryService
{
    /**
     * @return Builder<Shop>
     */
    public function listQuery(): Builder
    {
        return Shop::query()
            ->withCount(['orders', 'products'])
            ->with(['owner.profile', 'ownershipHistory']);
    }

    public function findByIdOrSlug(string $identifier, ?User $user = null): Shop
    {
        $query = Shop::query()
            ->with(['categories', 'owner', 'ownershipHistory'])
            ->withCount(['orders', 'products']);

        // PERFORMANCE + SECURITY: relasi 'balance' hanya di-eager-load kalau
        // user berpotensi berhak melihatnya (dicek ulang & final di ShopResource
        // via Policy::viewBalance -- ini hanya optimasi query, bukan satu-satunya
        // lapis proteksi).
        $shop = is_numeric($identifier)
            ? $query->where('id', $identifier)->firstOrFail()
            : $query->where('slug', $identifier)->firstOrFail();

        if ($user && ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)
                || $user->shops()->whereKey($shop->id)->exists())) {
            $shop->load('balance');
        }

        return $shop;
    }

    /**
     * @return LengthAwarePaginator<int, Shop>
     */
    public function findNewOrInactive(bool $isActive, int $perPage): LengthAwarePaginator
    {
        return Shop::query()
            ->withCount(['orders', 'products'])
            ->with(['owner.profile'])
            ->where('is_active', $isActive)
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Shop>
     */
    public function findNearby(float $lat, float $lng, ?float $maxDistanceKm = null): Collection
    {
        $options = (array) Settings::getData()->options;
        $maxDistanceVal = Arr::get($options, 'maxShopDistance', 1000);
        $maxDistance = $maxDistanceKm ?? (is_numeric($maxDistanceVal) ? (float) $maxDistanceVal : 1000.0);

        if (DB::getDriverName() === 'pgsql') {
            $latCol = "(settings->'location'->>'lat')::numeric";
            $lngCol = "(settings->'location'->>'lng')::numeric";
        } else {
            $latCol = 'json_unquote(json_extract(settings, "$.location.lat"))';
            $lngCol = 'json_unquote(json_extract(settings, "$.location.lng"))';
        }

        $baseQuery = Shop::query()
            ->where('is_active', true)
            ->whereNotNull('settings->location->lat')
            ->whereNotNull('settings->location->lng')
            ->select('shops.*')
            ->selectRaw(
                "6371 * acos(cos(radians(?)) * cos(radians({$latCol})) ".
                "* cos(radians({$lngCol}) - radians(?)) ".
                "+ sin(radians(?)) * sin(radians({$latCol}))) AS distance",
                [$lat, $lng, $lat]
            );

        return Shop::query()
            ->fromSub($baseQuery, 'shops')
            ->where('distance', '<', $maxDistance)
            ->orderBy('distance')
            ->get();
    }

    public function isFollowing(User $user, int $shopId): bool
    {
        return $user->follow_shops()->where('shops.id', $shopId)->exists();
    }

    /**
     * @return LengthAwarePaginator<int, Model&object{pivot: Pivot}>
     */
    public function followedShops(User $user, int $perPage): LengthAwarePaginator
    {
        return $user->follow_shops()->paginate($perPage);
    }

    /**
     * @return Collection<int, Product>
     */
    public function followedShopsPopularProducts(User $user, int $limit): Collection
    {
        $followedIds = $user->follow_shops()->pluck('shops.id');

        return Product::query()
            ->withCount('orders')
            ->with('shop')
            ->whereIn('shop_id', $followedIds)
            ->orderByDesc('orders_count')
            ->take($limit)
            ->get();
    }

    /**
     * @param  array<mixed, mixed>  $filters
     * @return LengthAwarePaginator<int, Shop>
     */
    public function search(string $query, int $limit, array $filters = []): LengthAwarePaginator
    {
        return Shop::search($query)
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(isset($filters['owner_id']), fn ($q) => $q->where('owner_id', $filters['owner_id']))
            ->paginate($limit);
    }
}
