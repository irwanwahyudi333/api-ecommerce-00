<?php

declare(strict_types=1);

namespace App\Modules\FlashSale\Http\Resources;

use App\Models\FlashSale;
use App\Modules\Product\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FlashSale
 */
class FlashSaleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'start_date' => $this->start_date->toISOString(),
            'end_date' => $this->end_date->toISOString(),
            'sale_status' => $this->sale_status,
            'type' => $this->type,
            'rate' => $this->rate,
            'sale_builder' => $this->sale_builder,
            'image' => $this->image,
            'cover_image' => $this->cover_image,
            'language' => $this->language,
            'translated_languages' => $this->translated_languages,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
