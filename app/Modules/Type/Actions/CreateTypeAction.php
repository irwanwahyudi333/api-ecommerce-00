<?php

declare(strict_types=1);

namespace App\Modules\Type\Actions;

use App\Models\Type;
use App\Modules\Type\DTO\TypeData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class CreateTypeAction
{
    private const CACHE_KEY_PREFIX = 'types_';

    public function execute(TypeData $data): Type
    {
        $slug = $data->slug ?? Str::slug($data->name ?? '');

        // Proteksi duplikasi slug manual di bahasa yang sama
        $count = Type::where('slug', 'like', "{$slug}%")->where('language', $data->language)->count();
        $finalSlug = $count > 0 ? "{$slug}-{$count}" : $slug;

        $attributes = array_filter([
            'name' => $data->name,
            'slug' => $finalSlug,
            'icon' => $data->icon,
            'settings' => $data->settings,
            'promotional_sliders' => $data->promotional_sliders,
            'images' => $data->images,
            'language' => $data->language,
        ], fn ($v) => ! is_null($v));

        $type = Type::create($attributes);

        // Jika ada input banners, simpan relasinya
        if ($data->banners) {
            $type->banners()->createMany($data->banners);
        }

        Cache::forget(self::CACHE_KEY_PREFIX.$type->language.'_*'); // Invalidate cache

        return $type;
    }
}
