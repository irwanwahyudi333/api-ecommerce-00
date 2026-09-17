<?php

declare(strict_types=1);

namespace App\Modules\AttributeValue\Http\Resources;

use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property AttributeValue $resource
 *
 * @mixin AttributeValue
 */
class AttributeValueResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'value' => $this->resource->value,
            'attribute_id' => $this->resource->attribute_id,
            'slug' => $this->resource->slug,
            'meta' => $this->resource->meta,
            'language' => $this->resource->language,
            'translated_languages' => $this->resource->translated_languages,
        ];
    }
}
