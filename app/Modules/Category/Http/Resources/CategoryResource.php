<?php

declare(strict_types=1);

namespace App\Modules\Category\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Category $resource
 */
class CategoryResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'language' => $this->resource->language,
            'parent' => $this->resource->parentCategory
                ? new CategoryResource($this->resource->parentCategory)
                : null,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'products_count' => $this->resource->products_count,
            'details' => $this->resource->details,
            'image' => $this->resource->image,
            'icon' => $this->resource->icon,
            'type_id' => $this->resource->getAttribute('type_id'),
            'banner_image' => $this->resource->banner_image,
            'type' => $this->whenLoaded(
                'type',
                fn () => [
                    'id' => $this->resource->type?->getAttribute('id'),
                    'name' => $this->resource->type?->getAttribute('name'),
                ]
            ),
        ];
    }
}
