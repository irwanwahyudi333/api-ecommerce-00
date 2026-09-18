<?php

declare(strict_types=1);

namespace App\Modules\DeliveryTime\Http\Resources;

use App\Models\DeliveryTime;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliveryTime
 */
class DeliveryTimeResource extends JsonResource
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
            'language' => $this->language,
            'description' => $this->description,
            'icon' => $this->icon,
            'translated_languages' => $this->translated_languages,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
