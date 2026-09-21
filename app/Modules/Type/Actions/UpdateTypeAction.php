<?php

declare(strict_types=1);

namespace App\Modules\Type\Actions;

use App\Models\Type;
use App\Modules\Type\DTO\TypeData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class UpdateTypeAction
{
    private const CACHE_KEY_PREFIX = 'types_';

    public function execute(Type $type, TypeData $data): Type
    {
        $attributes = array_filter([
            'name' => $data->name,
            'slug' => ($data->slug && $data->slug !== $type->slug) ? $data->slug : ($data->name ? Str::slug($data->name) : null),
            'icon' => $data->icon,
            'settings' => $data->settings,
            'promotional_sliders' => $data->promotional_sliders,
            'images' => $data->images,
            'language' => $data->language,
        ], fn ($v) => ! is_null($v));

        $type->update($attributes);

        if (! is_null($data->banners)) {
            $type->banners()->delete();
            $type->banners()->createMany($data->banners);
        }

        Cache::forget(self::CACHE_KEY_PREFIX.$type->language.'_*'); // Invalidate cache

        /** @var Type $freshType */
        $freshType = $type->fresh('banners');

        return $freshType;
    }
}
