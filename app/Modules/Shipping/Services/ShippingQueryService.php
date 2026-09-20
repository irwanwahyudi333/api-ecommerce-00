<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Services;

use App\Models\Shipping;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class ShippingQueryService
{
    private const CACHE_KEY_ALL = 'shippings_all';

    /**
     * @return Collection<int, Shipping>
     */
    public function getAll(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY_ALL, function () {
            return Shipping::all();
        });
    }

    public function findOrFail(int $id): Shipping
    {
        return Shipping::findOrFail($id);
    }
}
