<?php

namespace App\Modules\Resource\Services;

use App\Models\Resource;
use App\Modules\Resource\DTO\ResourceData;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ResourceService
{
    /**
     * @return LengthAwarePaginator<int, resource>
     */
    public function getResources(string $language, int $perPage = 15): LengthAwarePaginator
    {
        return Resource::where('language', $language)->paginate($perPage);
    }

    public function find(string $params, string $language): Resource
    {
        if (is_numeric($params)) {
            return Resource::where('id', (int) $params)->firstOrFail();
        }

        return Resource::where('slug', $params)->where('language', $language)->firstOrFail();
    }

    public function create(ResourceData $data): Resource
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

        return Resource::create($attributes);
    }

    public function update(Resource $resource, ResourceData $data): Resource
    {
        $attributes = array_filter([
            'name' => $data->name,
            'slug' => ($data->slug && $data->slug !== $resource->slug) ? $data->slug : ($data->name ? Str::slug((string) $data->name) : null),
            'type' => $data->type,
            'price' => $data->price,
            'image' => $data->image,
            'icon' => $data->icon,
            'details' => $data->details,
            'language' => $data->language,
            'is_approved' => $data->is_approved,
        ], fn ($v) => ! is_null($v));

        $resource->update($attributes);

        return $resource->refresh();
    }

    public function delete(Resource $resource): void
    {
        $resource->delete();
    }
}
