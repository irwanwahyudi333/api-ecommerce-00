<?php

declare(strict_types=1);

namespace App\Modules\Manufacturer\Services;

use App\Enums\Permission;
use App\Models\Manufacturer;
use App\Models\Shop;
use App\Models\User;
use App\Modules\Manufacturer\Actions\CreateManufacturerAction;
use App\Modules\Manufacturer\Actions\UpdateManufacturerAction;
use App\Modules\Manufacturer\DTO\ManufacturerData;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ManufacturerService
{
    public function __construct(
        private CreateManufacturerAction $createManufacturer,
        private UpdateManufacturerAction $updateManufacturer,
    ) {}

    public function hasPermission(?Authenticatable $user, ?int $shopId): bool
    {
        if (! $user) {
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
            $notice = config('notice.SHOP_NOT_APPROVED');
            throw new \Exception(is_string($notice) ? $notice : 'Shop not approved');
        }

        /** @var User $user */
        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            return $shop->owner_id === $user->id;
        }

        return false;
    }

    /**
     * @return LengthAwarePaginator<int, Manufacturer>
     */
    public function getManufacturersByLanguage(string $language, int $perPage = 15): LengthAwarePaginator
    {
        return Manufacturer::where('language', $language)
            ->with('type')
            ->paginate($perPage);
    }

    public function getManufacturerByIdOrSlug(int|string $identifier, string $language): Manufacturer
    {
        if (is_numeric($identifier)) {
            return Manufacturer::with('type')->where('id', $identifier)->firstOrFail();
        }

        return Manufacturer::with('type')
            ->where('slug', $identifier)
            ->where('language', $language)
            ->firstOrFail();
    }

    public function createManufacturer(ManufacturerData $data): Manufacturer
    {
        return $this->createManufacturer->execute($data);
    }

    public function updateManufacturer(Manufacturer $manufacturer, ManufacturerData $data): Manufacturer
    {
        return $this->updateManufacturer->execute($manufacturer, $data);
    }

    public function deleteManufacturer(Manufacturer $manufacturer): void
    {
        $manufacturer->delete();
    }

    /**
     * @return Collection<int, Manufacturer>
     */
    public function getTopManufacturers(string $language, int $limit = 10): Collection
    {
        return Manufacturer::where('language', $language)
            ->withCount('products')
            ->orderBy('products_count', 'desc')
            ->take($limit)
            ->get();
    }
}
