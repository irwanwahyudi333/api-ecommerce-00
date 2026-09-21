<?php

declare(strict_types=1);

namespace App\Modules\Tax\Actions;

use App\Models\Tax;
use App\Modules\Tax\DTO\TaxData;
use Illuminate\Support\Facades\Cache;

final class UpdateTaxAction
{
    private const CACHE_KEY_ALL = 'taxes_all';

    public function execute(Tax $tax, TaxData $data): Tax
    {
        $attributes = array_filter([
            'name' => $data->name,
            'country' => $data->country,
            'state' => $data->state,
            'zip' => $data->zip,
            'city' => $data->city,
            'rate' => $data->rate,
            'is_global' => $data->is_global,
            'priority' => $data->priority,
            'on_shipping' => $data->on_shipping,
        ], fn ($v) => ! is_null($v));

        $tax->update($attributes);

        Cache::forget(self::CACHE_KEY_ALL); // Invalidate cache

        return $tax->refresh();
    }
}
