<?php

declare(strict_types=1);

namespace App\Modules\Tax\Http\Resources;

use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tax
 */
final class TaxResource extends JsonResource
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
            'country' => $this->country,
            'state' => $this->state,
            'zip' => $this->zip,
            'city' => $this->city,
            'rate' => $this->rate,
            'is_global' => $this->is_global,
            'priority' => $this->priority,
            'on_shipping' => $this->on_shipping,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
