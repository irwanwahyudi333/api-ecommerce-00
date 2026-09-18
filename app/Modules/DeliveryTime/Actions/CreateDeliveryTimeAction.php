<?php

declare(strict_types=1);

namespace App\Modules\DeliveryTime\Actions;

use App\Models\DeliveryTime;
use App\Modules\DeliveryTime\DTO\DeliveryTimeData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class CreateDeliveryTimeAction
{
    public function execute(DeliveryTimeData $data): DeliveryTime
    {
        $attributes = array_filter([
            'title' => $data->title,
            'slug' => $data->slug ?? ($data->title !== null ? Str::slug($data->title) : null),
            'language' => $data->language,
            'description' => $data->description,
            'icon' => $data->icon,
        ], fn ($v) => ! is_null($v));

        $deliveryTime = DeliveryTime::create($attributes);

        Cache::forget("delivery_times_{$data->language}"); // Invalidate cache

        return $deliveryTime;
    }
}
