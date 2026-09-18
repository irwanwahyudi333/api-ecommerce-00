<?php

declare(strict_types=1);

namespace App\Modules\DeliveryTime\Actions;

use App\Models\DeliveryTime;
use App\Modules\DeliveryTime\DTO\DeliveryTimeData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class UpdateDeliveryTimeAction
{
    public function execute(DeliveryTime $deliveryTime, DeliveryTimeData $data): DeliveryTime
    {
        $attributes = array_filter([
            'title' => $data->title,
            'slug' => ($data->slug && $data->slug !== $deliveryTime->slug)
                ? $data->slug
                : ($data->title ? Str::slug($data->title) : null),
            'language' => $data->language,
            'description' => $data->description,
            'icon' => $data->icon,
        ], fn ($v) => ! is_null($v));

        $deliveryTime->update($attributes);

        Cache::forget("delivery_times_{$deliveryTime->language}"); // Invalidate cache

        return $deliveryTime->refresh();
    }
}
