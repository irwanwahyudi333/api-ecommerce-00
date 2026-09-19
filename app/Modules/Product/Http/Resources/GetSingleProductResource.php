<?php

namespace App\Modules\Product\Http\Resources;

use App\Models\AttributeValue;
use App\Models\Product;
use App\Modules\Category\Http\Resources\CategoryResource;
use App\Modules\Tag\Http\Resources\TagResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 *
 * @property Product $resource
 */
class GetSingleProductResource extends JsonResource
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
            'type' => $this->resource->type ? ['id' => $this->resource->type->id, 'name' => $this->resource->type->name] : null,
            'language' => $this->resource->language,
            'translated_languages' => $this->resource->translated_languages,
            'product_type' => $this->resource->product_type,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'metas' => $this->resource->metas,
            'digital_file' => $this->resource->digital_file,
            'variations' => $this->whenLoaded('variations', function () {
                return $this->resource->variations->map(function ($v) {
                    /** @var AttributeValue $v */
                    return [
                        'id' => $v->id,
                        'slug' => $v->slug,
                        'attribute_id' => $v->attribute_id,
                        'value' => $v->value,
                        'language' => $v->language,
                        'meta' => $v->meta,
                        'translated_languages' => $v->translated_languages,
                        'attribute' => $v->attribute,
                    ];
                });
            }),
            'variation_options' => $this->resource->variation_options,
            'shop_id' => $this->resource->shop_id,
            'shop' => $this->resource->shop ? ['id' => $this->resource->shop->id, 'name' => $this->resource->shop->name] : null,
            'author' => $this->resource->author ? ['id' => $this->resource->author->id, 'name' => $this->resource->author->name] : null,
            'manufacturer' => $this->resource->manufacturer ? ['id' => $this->resource->manufacturer->id, 'name' => $this->resource->manufacturer->name] : null,
            'related_products' => RelatedProductResource::collection($this->resource->related_products),
            'description' => $this->resource->description,
            'in_stock' => $this->resource->in_stock,
            'is_taxable' => $this->resource->is_taxable,
            'is_digital' => $this->resource->is_digital,
            'is_external' => $this->resource->is_external,
            'external_product_url' => $this->resource->external_product_url,
            'external_product_button_text' => $this->resource->external_product_button_text,
            'sale_price' => $this->resource->sale_price,
            'max_price' => $this->resource->max_price,
            'min_price' => $this->resource->min_price,
            'ratings' => $this->resource->ratings,
            'total_reviews' => $this->resource->total_reviews,
            'rating_count' => $this->resource->rating_count,
            'my_review' => $this->resource->my_review,
            'in_wishlist' => $this->resource->in_wishlist,
            'sku' => $this->resource->sku,
            'gallery' => $this->resource->gallery,
            'image' => $this->resource->image,
            'video' => $this->resource->video,
            'status' => $this->resource->status,
            'height' => $this->resource->height,
            'length' => $this->resource->length,
            'width' => $this->resource->width,
            'price' => $this->resource->price,
            'quantity' => $this->resource->quantity,
            'unit' => $this->resource->unit,
            'in_flash_sale' => $this->resource->in_flash_sale,
        ];
    }
}
