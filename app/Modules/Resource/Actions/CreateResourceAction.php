<?php

declare(strict_types=1);

namespace App\Modules\Resource\Actions;

use App\Models\Resource;
use App\Modules\Resource\DTO\ResourceData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class CreateResourceAction
{
    public function execute(ResourceData $data): Resource
    {
        $attributes = array_filter([
            'name' => $data->name,
            'slug' => $data->slug ?? Str::slug((string) $data->name),
            'type' => $data->type,
            'price' => $data->price,
            'image' => $data->image,
            'icon' => $data->icon,
            'details' => $data->details,
            'language' => $data->language,
            'is_approved' => $data->is_approved ?? false,
        ], fn ($v) => ! is_null($v));

        $resource = Resource::create($attributes);

        Cache::forget("resources_{$resource->language}_*"); // Invalidate cache

        return $resource;
    }
}
