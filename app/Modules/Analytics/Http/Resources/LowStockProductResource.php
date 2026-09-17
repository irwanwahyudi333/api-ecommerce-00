<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Http\Resources;

use App\Models\Product;
use App\Models\Shop;
use App\Models\Type;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 *
 * @property-read int $stock
 * @property-read int $low_stock_threshold
 * @property-read Shop|null $shop
 * @property-read Type|null $type
 */
class LowStockProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'in_stock' => $this->in_stock,
            'low_stock_threshold' => $this->low_stock_threshold,
            'shop' => $this->shop ? [
                'id' => $this->shop->id,
                'name' => $this->shop->name,
            ] : null,
            'type' => $this->type ? [
                'id' => $this->type->id,
                'name' => $this->type->name,
            ] : null,
            'price' => $this->price,
        ];
    }
}
