<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Models\Product;
use App\Models\Type;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class ProductMetricService
{
    /**
     * @return Collection<int, Product>
     */
    public function getBestSellingProducts(Request $request): Collection
    {
        $limit = is_numeric($request->limit) ? (int) $request->limit : 10;
        $language = is_string($request->language) ? $request->language : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id');
        $range = is_numeric($request->range) ? (int) $request->range : 0;
        $typeId = $this->resolveTypeId($request, $language);

        $query = Product::select('products.*')
            ->join('order_product', 'order_product.product_id', 'products.id')
            ->join('orders', 'order_product.order_id', '=', 'orders.id')
            ->selectRaw('products.*, sum(order_product.order_quantity) as total_sales')
            ->whereNull('orders.parent_id')
            ->where('orders.order_status', 'order-completed')
            ->where('orders.language', $language)
            ->groupBy('products.id')
            ->orderBy('total_sales', 'desc');

        if ($request->filled('shop_id')) {
            $query->where('products.shop_id', $request->shop_id);
        }
        if ($range > 0) {
            $query->whereDate('products.created_at', '>', Carbon::now()->subDays($range));
        }
        if ($typeId) {
            $query->where('products.type_id', $typeId);
        }

        return $query->take($limit)->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getPopularProducts(Request $request): Collection
    {
        $limit = is_numeric($request->limit) ? (int) $request->limit : 10;
        $language = is_string($request->language) ? $request->language : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id');
        $range = is_numeric($request->range) ? (int) $request->range : 0;
        $typeId = $this->resolveTypeId($request, $language);

        $query = Product::withCount('orders')
            ->with(['type', 'shop'])
            ->orderBy('orders_count', 'desc')
            ->where('language', $language);

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }
        if ($range > 0) {
            $query->whereDate('created_at', '>', Carbon::now()->subDays($range));
        }
        if ($typeId) {
            $query->where('type_id', $typeId);
        }

        return $query->take($limit)->get();
    }

    private function resolveTypeId(Request $request, string $language): ?int
    {
        if ($request->filled('type_id')) {
            return is_numeric($request->type_id) ? (int) $request->type_id : null;
        }
        if ($request->filled('type_slug')) {
            $type = Type::where('slug', $request->type_slug)
                ->where('language', $language)
                ->first();

            return $type?->id;
        }

        return null;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getLowStockProducts(Request $request, int $threshold): Collection
    {
        $limit = is_numeric($request->limit) ? (int) $request->limit : 10;

        return Product::whereRaw('quantity - reserved_quantity <= ?', [$threshold])
            ->take($limit)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function getSalesMetrics(int $shopId, string $period): array
    {
        return [
            'total_sales' => 0,
            'revenue' => 0,
            'period' => $period,
            'shop_id' => $shopId,
        ];
    }
}
