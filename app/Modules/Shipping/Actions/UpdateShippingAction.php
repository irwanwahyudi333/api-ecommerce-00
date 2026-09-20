<?php

declare(strict_types=1);

namespace App\Modules\Shipping\Actions;

use App\Models\Shipping;
use App\Modules\Shipping\DTO\ShippingData;
use Illuminate\Support\Facades\Cache;

final class UpdateShippingAction
{
    private const CACHE_KEY_ALL = 'shippings_all';

    public function execute(Shipping $shipping, ShippingData $data): Shipping
    {
        $attributes = array_filter([
            'name' => $data->name,
            'amount' => $data->amount,
            'is_global' => $data->is_global,
            'type' => $data->type,
        ], fn ($v) => ! is_null($v));

        $shipping->update($attributes);

        Cache::forget(self::CACHE_KEY_ALL); // Invalidate cache

        return $shipping->refresh();
    }
}
