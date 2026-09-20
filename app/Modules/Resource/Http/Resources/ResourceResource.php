<?php

namespace App\Modules\Resource\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Resource
 */
class ResourceResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'price' => $this->price,
            'image' => $this->image,
            'icon' => $this->icon,
            'details' => $this->details,
            'language' => $this->language,
            'translated_languages' => $this->translated_languages,
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
