<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class ProductInventoryController extends BaseController
{
    public function show(Product $product): JsonResponse
    {
        return $this->sendSuccess([
            'id' => $product->id,
            'name' => $product->name,
            'quantity' => $product->quantity,
            'reserved_quantity' => $product->reserved_quantity,
            'available_quantity' => $product->available_quantity,
            'low_stock_threshold' => $product->low_stock_threshold,
            'in_stock' => $product->in_stock,
            'is_low_stock' => $product->isLowStock(),
            'is_out_of_stock' => $product->isOutOfStock(),
        ], 'Stock data retrieved successfully');
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $request->validate([
            'quantity' => 'sometimes|integer|min:0',
            'low_stock_threshold' => 'sometimes|integer|min:0',
        ]);

        if (isset($data['quantity']) && is_numeric($data['quantity'])) {
            $product->quantity = (int) $data['quantity'];
        }
        if (isset($data['low_stock_threshold']) && is_numeric($data['low_stock_threshold'])) {
            $product->low_stock_threshold = (int) $data['low_stock_threshold'];
        }
        $product->save();

        return $this->sendSuccess($product->only(['id', 'name', 'quantity', 'reserved_quantity', 'available_quantity', 'low_stock_threshold']), 'Stock updated successfully');
    }

    public function lowStock(Request $request): JsonResponse
    {
        $limitParam = $request->get('limit', 15);
        $perPage = is_numeric($limitParam) ? (int) $limitParam : 15;

        /** @var LengthAwarePaginator<int, Product> $products */
        $products = Product::whereRaw('quantity - reserved_quantity <= low_stock_threshold')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->sendPaginated($products, $products->getCollection()->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'quantity' => $p->quantity,
            'reserved_quantity' => $p->reserved_quantity,
            'available_quantity' => $p->available_quantity,
            'low_stock_threshold' => $p->low_stock_threshold,
        ]), 'Low stock products retrieved successfully');
    }
}
