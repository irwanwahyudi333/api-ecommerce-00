<?php

declare(strict_types=1);

namespace App\Modules\Manufacturer\Actions;

use App\Models\Manufacturer;
use App\Modules\Manufacturer\DTO\ManufacturerData;

class UpdateManufacturerAction
{
    public function execute(Manufacturer $manufacturer, ManufacturerData $data): Manufacturer
    {
        $attributes = array_filter([
            'name' => $data->name,
            'slug' => $data->slug,
            'description' => $data->description,
            'type_id' => $data->type_id,
            'shop_id' => $data->shop_id,
            'image' => $data->image,
            'cover_image' => $data->cover_image,
            'is_approved' => $data->is_approved,
            'language' => $data->language,
            'website' => $data->website,
            'socials' => $data->socials,
        ], fn ($v) => ! is_null($v));

        $manufacturer->update($attributes);

        $manufacturer->refresh();

        return $manufacturer;
    }
}
