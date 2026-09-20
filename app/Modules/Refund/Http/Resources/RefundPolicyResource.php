<?php

declare(strict_types=1);

namespace App\Modules\Refund\Http\Resources;

use App\Models\RefundPolicy;
use App\Modules\Shop\Http\Resources\ShopResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RefundPolicy
 */
class RefundPolicyResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'target' => $this->target,
            'status' => $this->status,
            'description' => $this->description,
            'language' => $this->language,
            'translated_languages' => $this->translated_languages,
            'shop' => $this->whenLoaded('shop', fn () => new ShopResource($this->shop)),
            'refunds' => $this->whenLoaded('refunds', fn () => RefundResource::collection($this->refunds)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
