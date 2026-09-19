<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Enums\ProductStatus;
use App\Enums\ProductVisibilityStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductQueryService
{
    private const DEFAULT_RELATIONS = [
        'type',
        'shop',
        'categories',
        'tags',
        'variations',
        'variation_options',
        'variations.inventories',
        'author',
    ];

    private const PUBLIC_RELATIONS = [
        'type',
        'shop',
        'categories',
        'tags',
        'variations',
        'variation_options',
    ];

    /**
     * @return Builder<Product>
     */
    public function buildQuery(Request $request, ?User $user = null): Builder
    {
        $query = Product::query();

        // Authorization filter
        $this->applyAuthorizationFilter($query, $user);

        // Eager loading
        $this->applyEagerLoading($query, $user);

        // Filters
        $this->applyFilters($query, $request);

        // Sorting
        $this->applySorting($query, $request);

        return $query;
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getPaginatedProducts(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildQuery($request, $request->user());

        return $query->paginate($perPage);
    }

    public function getSingleProduct(string $identifier, Request $request): Product
    {
        $user = $request->user();
        $query = $this->buildQuery($request, $user);

        if (is_numeric($identifier)) {
            /** @var Product $product */
            $product = $query->findOrFail((int) $identifier);

            return $product;
        }

        /** @var Product $product */
        $product = $query->where('slug', $identifier)->firstOrFail();

        return $product;
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getProductsByShop(int $shopId, Request $request): LengthAwarePaginator
    {
        $query = $this->buildQuery($request, $request->user());
        $query->where('shop_id', $shopId);

        $limit = $request->get('limit', 15);
        $perPage = is_numeric($limit) ? (int) $limit : 15;

        return $query->paginate($perPage);
    }

    /**
     * @return Collection<int, Product>
     */
    public function getPopularProducts(Request $request): Collection
    {
        $query = $this->buildQuery($request, $request->user());

        return $query
            ->where('status', ProductStatus::PUBLISH->value)
            ->where('visibility', ProductVisibilityStatus::VISIBILITY_PUBLIC->value)
            ->orderBy('sold_quantity', 'desc')
            ->limit(is_numeric($request->get('limit', 10)) ? (int) $request->get('limit', 10) : 10)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function getLowStockProducts(Request $request): Collection
    {
        $user = $request->user();
        $limit = $request->get('threshold', 10);
        $threshold = is_numeric($limit) ? (int) $limit : 10;

        $query = $this->buildQuery($request, $user);

        return $query
            ->where('quantity', '<=', $threshold)
            ->whereHas('shop', function ($q) use ($user) {
                if ($user && $user->hasPermissionTo('store_owner')) {
                    $q->where('owner_id', $user->id);
                }
            })
            ->get();
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyAuthorizationFilter(Builder $query, ?User $user = null): void
    {
        // Super admin can see everything
        if ($user && $user->hasPermissionTo('super_admin')) {
            return;
        }

        // Public products visible to all
        $query->where(function ($q) {
            $q->where('status', ProductStatus::PUBLISH->value)
                ->where('visibility', ProductVisibilityStatus::VISIBILITY_PUBLIC->value);
        });

        // If user is logged in, show their own products
        if ($user) {
            $query->orWhere(function ($q) use ($user) {
                $q->where('author_id', $user->id);

                // Store owners can see their shop products
                if ($user->hasPermissionTo('store_owner')) {
                    $q->orWhereHas('shop', function ($shopQuery) use ($user) {
                        $shopQuery->where('owner_id', $user->id);
                    });
                }

                // Staff can see their assigned shop products
                if ($user->hasPermissionTo('staff')) {
                    $q->orWhereHas('shop.staffs', function ($staffQuery) use ($user) {
                        $staffQuery->where('user_id', $user->id);
                    });
                }
            });
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyEagerLoading(Builder $query, ?User $user = null): void
    {
        $relations = $user ? self::DEFAULT_RELATIONS : self::PUBLIC_RELATIONS;
        $query->with($relations);
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        // Search filter
        $search = $request->get('search');
        if (is_string($search) && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Category filter
        if ($categoryId = $request->get('category_id')) {
            $query->whereHas('categories', function ($q) use ($categoryId) {
                $q->where('id', $categoryId);
            });
        }

        // Type filter
        if ($typeId = $request->get('type_id')) {
            $query->where('type_id', $typeId);
        }

        // Price range filter
        if ($minPrice = $request->get('min_price')) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice = $request->get('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        // Rental filter
        if ($request->has('is_rental')) {
            $query->where('is_rental', (bool) $request->get('is_rental'));
        }

        // Digital filter
        if ($request->has('is_digital')) {
            $query->where('is_digital', (bool) $request->get('is_digital'));
        }
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applySorting(Builder $query, Request $request): void
    {
        $sortBy = is_string($request->get('sort_by')) ? $request->get('sort_by') : 'created_at';
        $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';

        $validSortColumns = [
            'name', 'price', 'created_at', 'updated_at',
            'sold_quantity', 'quantity', 'rating',
        ];

        if (in_array($sortBy, $validSortColumns, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }
    }
}
