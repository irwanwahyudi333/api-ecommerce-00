<?php

declare(strict_types=1);

namespace App\Modules\Manufacturer\Actions;

use App\Models\Manufacturer;
use App\Modules\Manufacturer\DTO\ManufacturerData;
use Illuminate\Support\Str;

class CreateManufacturerAction
{
    public function execute(ManufacturerData $data): Manufacturer
    {
        $slug = $data->slug ?? Str::slug((string) $data->name);

        // Proteksi duplikasi slug manual pada bahasa yang sama
        $count = Manufacturer::where('slug', 'like', "{$slug}%")
            ->where('language', $data->language)
            ->count();

        $finalSlug = $count > 0 ? "{$slug}-{$count}" : $slug;

        $attributes = array_filter([
            'name' => $data->name,
            'slug' => $finalSlug,
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

        return Manufacturer::create($attributes);
    }
}
