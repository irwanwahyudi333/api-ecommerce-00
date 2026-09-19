<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Enums\Permission;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function __construct(
        private ProductRentalService $rentalService
    ) {}

    /**
     * @return Builder<Product>
     */
    public function getProductsQuery(Request $request): Builder
    {
        $language = is_string($request->language) ? $request->language : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id');
        $query = Product::where('language', $language);

        if ($request->filled('date_range')) {
            $dateRange = is_string($request->date_range) ? $request->date_range : '';
            $parts = explode('//', $dateRange);
            if (count($parts) !== 2) {
                throw ValidationException::withMessages([
                    'date_range' => ['Invalid date range format. Use FROM//TO'],
                ]);
            }
            [$from, $to] = $parts;
            $unavailableIds = $this->rentalService->getUnavailableProductIds($from, $to);
            $query->whereNotIn('id', $unavailableIds);
        }

        if ($request->has('flash_sale_builder')) {
            $query = $this->applyFlashSaleFilters($request, $query);
        }

        // Filter berdasarkan shop_id jika ada
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        // Filter berdasarkan type_id
        if ($request->filled('type_id')) {
            $query->where('type_id', $request->type_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getProducts(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getProductsQuery($request)->paginate($perPage);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Product>
     */
    public function getRelatedProducts(Product $product, int $limit = 10, ?string $language = null): \Illuminate\Database\Eloquent\Collection
    {
        $language = $language ?? (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id');
        $categoryIds = $product->categories()->pluck('categories.id');

        if ($categoryIds->isEmpty()) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        return Product::where('language', $language)
            ->where('id', '!=', $product->id)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
            ->with('type')
            ->limit($limit)
            ->get();
    }

    public function getProductByIdentifier(string $identifier, string $language): Product
    {
        $query = Product::where('language', $language);
        if (is_numeric($identifier)) {
            $query->where('id', $identifier);
        } else {
            $query->where('slug', $identifier);
        }

        return $query->firstOrFail();
    }

    public function getProductDetail(Request $request, string $slug): Product
    {
        $language = is_string($request->language) ? $request->language : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id');
        $user = $request->user();
        $limit = is_numeric($request->limit) ? (int) $request->limit : 10;

        $product = Product::where('language', $language)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)->orWhere('id', $slug);
            })->firstOrFail();

        // Otorisasi untuk akses file digital
        $withParam = is_string($request->with) ? $request->with : '';
        if ($request->has('with') && str_contains($withParam, 'digital_file')) {
            if (! $this->hasPermission($user, $product->shop_id)) {
                $notice = config('notice.NOT_AUTHORIZED');
                throw new \Exception(is_string($notice) ? $notice : 'Not authorized');
            }
        }

        $related = $this->getRelatedProducts($product, $limit, $language);
        $product->setRelation('related_products', $related);

        return $product;
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getDraftedProducts(Request $request): LengthAwarePaginator
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return Product::whereRaw('1 = 0')->paginate(1);
        }
        $language = is_string($request->language) ? $request->language : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id');
        $query = Product::with(['type', 'shop'])
            ->where('language', $language)
            ->where('status', ProductStatus::DRAFT->value);

        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            if ($request->filled('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            }
        } elseif ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            if ($request->filled('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            } else {
                $query->whereIn('shop_id', $user->shops()->pluck('shops.id'));
            }
        } elseif ($user->hasPermissionTo(Permission::STAFF->value)) {
            $query->where('shop_id', $user->shop_id);
        } else {
            return Product::whereRaw('1 = 0')->paginate(1);
        }

        $perPage = is_numeric($request->limit) ? (int) $request->limit : 15;

        return $query->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getLowStockProducts(Request $request): LengthAwarePaginator
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return Product::whereRaw('1 = 0')->paginate(1);
        }
        $language = is_string($request->language) ? $request->language : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id');
        $query = Product::with(['type', 'shop'])
            ->where('language', $language)
            ->where('quantity', '<', 10)
            ->where('quantity', '>', 0);

        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            if ($request->filled('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            }
        } elseif ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            if ($request->filled('shop_id')) {
                $query->where('shop_id', $request->shop_id);
            } else {
                $query->whereIn('shop_id', $user->shops()->pluck('shops.id'));
            }
        } elseif ($user->hasPermissionTo(Permission::STAFF->value)) {
            $query->where('shop_id', $user->shop_id);
        } else {
            return Product::whereRaw('1 = 0')->paginate(1);
        }

        $perPage = is_numeric($request->limit) ? (int) $request->limit : 15;

        return $query->paginate($perPage);
    }

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getMyWishlists(Request $request): LengthAwarePaginator
    {
        /** @var User $user */
        $user = $request->user();
        $productIds = Wishlist::where('user_id', $user->id)->pluck('product_id');

        $perPage = is_numeric($request->limit) ? (int) $request->limit : 10;

        return Product::whereIn('id', $productIds)->paginate($perPage);
    }

    /**
     * @param  User|null  $user
     */
    private function hasPermission(?Authenticatable $user, ?int $shopId): bool
    {
        if (! $user instanceof User) {
            return false;
        }
        if ($user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            return true;
        }
        if (! $shopId) {
            return false;
        }
        $shop = Shop::find($shopId);
        if (! $shop) {
            return false;
        }
        if ($user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            return $shop->owner_id === $user->id;
        }
        if ($user->hasPermissionTo(Permission::STAFF->value)) {
            return $shop->staffs->contains($user->id);
        }

        return false;
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    private function applyFlashSaleFilters(Request $request, Builder $query): Builder
    {
        $user = $request->user();

        if ($user && $user->hasPermissionTo(Permission::SUPER_ADMIN->value)) {
            if ($request->searchedByUser === 'super_admin_builder') {
                $query->where('in_flash_sale', false)
                    ->whereNull('sale_price')
                    ->whereNotIn('id', fn (\Illuminate\Database\Query\Builder $q) => $q->select('product_id')->from('flash_sale_requests_products'))
                    ->when($request->filled('shop_id'), fn ($q) => $q->where('shop_id', $request->shop_id))
                    ->when($request->filled('author'), fn ($q) => $q->where('author_id', $request->author))
                    ->when($request->filled('manufacturer'), fn ($q) => $q->where('manufacturer_id', $request->manufacturer));
            } else {
                $query->where('in_flash_sale', true);
                if ($request->filled('shop_id')) {
                    $query->where('shop_id', $request->shop_id);
                }
            }
        } elseif ($user && $user->hasPermissionTo(Permission::STORE_OWNER->value)) {
            if ($request->filled('shop_id')) {
                if ($request->searchedByUser === 'vendor') {
                    $query->where('in_flash_sale', false)
                        ->where('shop_id', $request->shop_id)
                        ->whereNull('sale_price');
                } else {
                    $query->where('in_flash_sale', true);
                }
            } else {
                $query->where('in_flash_sale', true)
                    ->whereIn('shop_id', $user->shops()->pluck('shops.id'));
            }
        } elseif ($user && $user->hasPermissionTo(Permission::STAFF->value)) {
            $query->where('in_flash_sale', true);
        } else {
            $query->where('in_flash_sale', true);
        }

        return $query;
    }
}
