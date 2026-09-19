<?php

declare(strict_types=1);

namespace App\Modules\Product\Services;

use App\Models\Product;
use App\Models\User;
use App\Modules\Product\Actions\CreateProductAction;
use App\Modules\Product\Actions\DeleteProductAction;
use App\Modules\Product\Actions\UpdateProductAction;
use App\Modules\Product\DTO\ProductData;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductCrudService
{
    public function __construct(
        private CreateProductAction $createProduct,
        private UpdateProductAction $updateProduct,
        private DeleteProductAction $deleteProduct,
        private ProductCacheService $cacheService
    ) {}

    public function createProduct(ProductData $data, User $user): Product
    {
        $this->validateShopOwnership($data->shop_id, $user);

        return DB::transaction(function () use ($data, $user) {
            try {
                $product = $this->createProduct->execute($data, (object) []);

                // Invalidate relevant caches
                if ($product->shop_id) {
                    $this->cacheService->invalidateShopCache($product->shop_id);
                }
                $this->cacheService->invalidateAllProductCache();

                // Log creation for audit trail
                Log::info('Product created', [
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'shop_id' => $product->shop_id,
                    'action' => 'create',
                ]);

                return $product;
            } catch (\Exception $e) {
                Log::error('Product creation failed', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'data' => $data->toArray(),
                ]);
                throw $e;
            }
        });
    }

    public function updateProduct(int $productId, ProductData $data, User $user): Product
    {
        $product = Product::findOrFail($productId);

        // Check if shop ownership changed
        if ($data->shop_id && $data->shop_id !== $product->shop_id) {
            $this->validateShopOwnership($data->shop_id, $user);
        }

        return DB::transaction(function () use ($product, $data, $user) {
            try {
                $updatedProduct = $this->updateProduct->execute($product, $data, (object) []);

                // Invalidate caches
                $this->cacheService->invalidateProductCache($product->id);
                if ($product->shop_id) {
                    $this->cacheService->invalidateShopCache($product->shop_id);
                }

                // Log update for audit trail
                Log::info('Product updated', [
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'old_shop_id' => $product->shop_id,
                    'new_shop_id' => $data->shop_id,
                    'action' => 'update',
                ]);

                return $updatedProduct;
            } catch (\Exception $e) {
                Log::error('Product update failed', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
                throw $e;
            }
        });
    }

    public function deleteProduct(int $productId, User $user): void
    {
        $product = Product::findOrFail($productId);

        DB::transaction(function () use ($product, $user) {
            try {
                $this->deleteProduct->execute($product);

                // Invalidate caches
                $this->cacheService->invalidateProductCache($product->id);
                if ($product->shop_id) {
                    $this->cacheService->invalidateShopCache($product->shop_id);
                }

                // Log deletion for audit trail
                Log::info('Product deleted', [
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'shop_id' => $product->shop_id,
                    'action' => 'delete',
                ]);
            } catch (\Exception $e) {
                Log::error('Product deletion failed', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
                throw $e;
            }
        });
    }

    public function updateProductStock(int $productId, int $quantity, User $user): Product
    {
        $product = Product::findOrFail($productId);

        /** @var Product $result */
        $result = DB::transaction(function () use ($product, $quantity, $user) {
            try {
                $product->quantity = $quantity;
                $product->save();

                // Invalidate cache
                $this->cacheService->invalidateProductCache($product->id);

                // Log stock update
                Log::info('Product stock updated', [
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'new_quantity' => $quantity,
                    'action' => 'update_stock',
                ]);

                return $product->fresh() ?? $product;
            } catch (\Exception $e) {
                Log::error('Product stock update failed', [
                    'product_id' => $product->id,
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                ]);
                throw $e;
            }
        });

        return $result;
    }

    private function validateShopOwnership(?int $shopId, User $user): void
    {
        if (! $shopId) {
            return;
        }

        // Super admin bypasses validation
        if ($user->hasPermissionTo('super_admin')) {
            return;
        }

        // Store owner must own the shop
        if ($user->hasPermissionTo('store_owner')) {
            $ownsShop = $user->shops()->where('id', $shopId)->exists();
            if (! $ownsShop) {
                throw new AuthorizationException(
                    'You do not have permission to create products for this shop.'
                );
            }

            return;
        }

        // Staff must be assigned to the shop
        if ($user->hasPermissionTo('staff')) {
            if ($user->shop_id !== $shopId) {
                throw new AuthorizationException(
                    'You are not assigned to this shop.'
                );
            }

            return;
        }

        throw new AuthorizationException(
            'You do not have permission to create products.'
        );
    }
}
