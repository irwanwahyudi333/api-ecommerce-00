<?php

declare(strict_types=1);

namespace App\Modules\Manufacturer\Http\Resources;

use App\Models\Manufacturer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Manufacturer
 *
 * @property Manufacturer $resource
 */
class ManufacturerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'language' => $this->resource->language,
            'translated_languages' => $this->resource->translated_languages,
            'products_count' => $this->resource->products_count,
            'is_approved' => $this->resource->is_approved,
            'description' => $this->resource->description,
            'website' => $this->resource->website,
            'socials' => $this->resource->socials,
            'image' => $this->resource->image,
            'cover_image' => $this->resource->cover_image,
            'type' => $this->whenLoaded('type', function () {
                return [
                    'id' => $this->resource->type?->id,
                    'name' => $this->resource->type?->name,
                    'slug' => $this->resource->type?->slug,
                ];
            }),
        ];
    }
}
