<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 *
 * @property Product $resource
 */
class ProductResource extends JsonResource
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
            'type' => $this->whenLoaded('type', fn () => [
                'id' => $this->resource->type ? $this->resource->type->getAttribute('id') : null,
                'name' => $this->resource->type ? $this->resource->type->getAttribute('name') : null,
            ]),
            'language' => $this->resource->language,
            'translated_languages' => $this->when($this->resource->relationLoaded('translatedLanguages'), fn () => $this->resource->getAttribute('translated_languages')),
            'product_type' => $this->resource->product_type,
            'shop' => $this->whenLoaded('shop', fn () => [
                'id' => $this->resource->shop ? $this->resource->shop->getAttribute('id') : null,
                'name' => $this->resource->shop ? $this->resource->shop->getAttribute('name') : null,
            ]),
            'sale_price' => $this->resource->sale_price,
            'max_price' => $this->resource->max_price,
            'min_price' => $this->resource->min_price,
            'image' => $this->resource->image,
            'status' => $this->resource->status,
            'price' => $this->resource->price,
            'quantity' => $this->resource->quantity,
            'unit' => $this->resource->unit,
            'sku' => $this->resource->sku,
            'sold_quantity' => $this->resource->sold_quantity,
            'in_flash_sale' => $this->resource->in_flash_sale,
            'visibility' => $this->resource->visibility,
        ];
    }
}
