<?php

declare(strict_types=1);

namespace App\Modules\Attribute\Http\Resources;

use App\Modules\AttributeValue\Http\Resources\AttributeValueResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \App\Models\Attribute $resource
 */
class AttributeResource extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'shop_id' => $this->resource->shop_id,
            'language' => $this->resource->language,
            'translated_languages' => $this->resource->translated_languages,
            'slug' => $this->resource->slug,
            'values' => AttributeValueResource::collection($this->whenLoaded('values')),
        ];
    }
}
