<?php

declare(strict_types=1);

namespace App\Modules\RefundReason\Http\Resources;

use App\Models\RefundReason;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RefundReason
 */
class RefundReasonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'language' => $this->language,
            'translated_languages' => $this->translated_languages,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
