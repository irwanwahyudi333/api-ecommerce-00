<?php

namespace App\Modules\Type\Http\Resources;

use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Type
 */
class TypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Type $resource */
        $resource = $this->resource;

        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'slug' => $resource->slug,
            'icon' => $resource->icon,
            'language' => $resource->language,
            'translated_languages' => $resource->translated_languages,
            'settings' => $resource->settings,
            'promotional_sliders' => $resource->promotional_sliders,
            'images' => $resource->images,
            'banners' => $resource->relationLoaded('banners') ? $resource->banners : null,
        ];
    }
}
