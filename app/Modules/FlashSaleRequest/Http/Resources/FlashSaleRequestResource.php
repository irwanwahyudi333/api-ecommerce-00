<?php

declare(strict_types=1);

namespace App\Modules\FlashSaleRequest\Http\Resources;

use App\Models\FlashSale;
use App\Models\FlashSaleRequest;
use App\Modules\Product\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FlashSaleRequest
 */
class FlashSaleRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'note' => $this->note,
            'flash_sale_id' => $this->flash_sale_id,
            'language' => $this->language,
            'request_status' => $this->request_status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'flash_sale' => $this->whenLoaded('flashSale', function () {
                /** @var FlashSale $flashSale */
                $flashSale = $this->flashSale;

                return [
                    'id' => $flashSale->id,
                    'title' => $flashSale->title,
                ];
            }),
        ];
    }
}
