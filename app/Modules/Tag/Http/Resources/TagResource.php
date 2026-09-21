<?php

declare(strict_types=1);

namespace App\Modules\Tag\Http\Resources;

use App\Models\Tag;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Tag $resource
 */
class TagResource extends JsonResource
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
            'language' => $this->resource->language,
            'translated_languages' => $this->resource->translated_languages,
            'slug' => $this->resource->slug,
            'details' => $this->resource->details,
            'image' => $this->resource->image,
            'icon' => $this->resource->icon,
            'type' => $this->whenLoaded('type', fn (?Type $type) => $type ? ['id' => $type->id, 'name' => $type->name] : null),
        ];
    }
}
