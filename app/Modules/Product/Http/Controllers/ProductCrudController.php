<?php

declare(strict_types=1);

namespace App\Modules\Product\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Product;
use App\Models\User;
use App\Modules\Product\DTO\ProductData;
use App\Modules\Product\Http\Requests\ProductCreateRequest;
use App\Modules\Product\Http\Requests\ProductUpdateRequest;
use App\Modules\Product\Http\Resources\GetSingleProductResource;
use App\Modules\Product\Services\ProductCrudService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCrudController extends BaseController
{
    public function __construct(
        private ProductCrudService $crudService
    ) {}

    public function store(ProductCreateRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $data = ProductData::fromRequest($validated);

        /** @var User $user */
        $user = $request->user();
        $product = $this->crudService->createProduct($data, $user);

        return $this->sendSuccess(
            new GetSingleProductResource($product),
            'Product created successfully',
            201
        );
    }

    public function update(ProductUpdateRequest $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $data = ProductData::fromRequest($validated);

        /** @var User $user */
        $user = $request->user();
        $updatedProduct = $this->crudService->updateProduct($id, $data, $user);

        return $this->sendSuccess(
            new GetSingleProductResource($updatedProduct),
            'Product updated successfully'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('delete', $product);

        /** @var User $user */
        $user = $request->user();
        $this->crudService->deleteProduct($id, $user);

        return $this->sendSuccess(
            null,
            'Product deleted successfully'
        );
    }

    public function updateStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $product = Product::findOrFail($id);
        $this->authorize('updateStock', $product);

        $quantityParam = $request->get('quantity');
        $quantity = is_numeric($quantityParam) ? (int) $quantityParam : 0;

        /** @var User $user */
        $user = $request->user();
        $updatedProduct = $this->crudService->updateProductStock($id, $quantity, $user);

        return $this->sendSuccess(
            new GetSingleProductResource($updatedProduct),
            'Product stock updated successfully'
        );
    }

    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:publish,draft,private',
        ]);

        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $status = $request->get('status');
        $product->status = is_string($status) ? $status : 'draft';
        $product->save();

        return $this->sendSuccess(
            new GetSingleProductResource($product),
            'Product status updated successfully'
        );
    }
}
