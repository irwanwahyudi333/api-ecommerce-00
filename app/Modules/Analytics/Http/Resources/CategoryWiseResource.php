<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{ category_id: int, category_name: string, shop_name?: string, product_count?: int, total_sales?: float } $resource
 */
class CategoryWiseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'category_id' => $this->resource['category_id'],
            'category_name' => $this->resource['category_name'],
            'shop_name' => $this->resource['shop_name'] ?? null,
            'product_count' => $this->resource['product_count'] ?? null,
            'total_sales' => $this->resource['total_sales'] ?? null,
        ];
    }
}
