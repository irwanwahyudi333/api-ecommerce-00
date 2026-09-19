<?php

namespace App\Modules\Product\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 *
 * @property Product $resource
 */
class RelatedProductResource extends JsonResource
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
            'translated_languages' => $this->resource->getAttribute('translated_languages'),
            'product_type' => $this->resource->product_type,
            'sale_price' => $this->resource->sale_price,
            'max_price' => $this->resource->max_price,
            'min_price' => $this->resource->min_price,
            'image' => $this->resource->image,
            'video' => $this->resource->video,
            'price' => $this->resource->price,
            'unit' => $this->resource->unit,
        ];
    }
}
