<?php

declare(strict_types=1);

namespace App\Modules\FlashSale\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\FlashSale;
use App\Models\Product; // Needed for ProductResource
use App\Modules\FlashSale\DTO\FlashSaleData;
use App\Modules\FlashSale\Http\Requests\FlashSaleCreateRequest;
use App\Modules\FlashSale\Http\Requests\FlashSaleUpdateRequest;
use App\Modules\FlashSale\Http\Resources\FlashSaleResource;
use App\Modules\FlashSale\Services\FlashSaleQueryService; // New Query Service
use App\Modules\FlashSale\Services\FlashSaleWriteService; // New Write Service
// New Delete Action
use App\Modules\Product\Http\Resources\ProductResource; // Assuming ProductResource is in Product module
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// To use HTTP_CREATED for store method
// For flash sale not found

class FlashSaleController extends BaseController
{
    public function __construct(
        private readonly FlashSaleQueryService $flashSaleQueryService,
        private readonly FlashSaleWriteService $flashSaleWriteService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = is_numeric($request->limit) ? (int) $request->limit : 10;
        $flashSales = $this->flashSaleQueryService->getFlashSalesQuery($request)->paginate($limit);

        return FlashSaleResource::collection($flashSales);
    }

    public function store(FlashSaleCreateRequest $request): FlashSaleResource
    {
        $this->authorize('create', FlashSale::class);
        $data = FlashSaleData::fromRequest($request->validated());
        $flashSale = $this->flashSaleWriteService->createFlashSale($data);

        return new FlashSaleResource($flashSale);
    }

    public function show(Request $request, string $slug): FlashSaleResource|JsonResponse
    {
        $language = is_string($request->language) ? $request->language : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id');
        $flashSale = $this->flashSaleQueryService->findFlashSaleBySlug($slug, (string) $language);
        if (! $flashSale) {
            return $this->sendError('Flash sale not found', 404);
        }

        return new FlashSaleResource($flashSale);
    }

    public function update(FlashSaleUpdateRequest $request, int $id): FlashSaleResource
    {
        $flashSale = FlashSale::findOrFail($id);
        $this->authorize('update', $flashSale);
        $data = FlashSaleData::fromRequest($request->validated());
        $updated = $this->flashSaleWriteService->updateFlashSale($flashSale, $data);

        return new FlashSaleResource($updated);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $flashSale = FlashSale::findOrFail($id);
        $this->authorize('delete', $flashSale);
        $this->flashSaleWriteService->deleteFlashSale($flashSale);

        return $this->sendSuccess(null, 'Flash sale deleted successfully');
    }

    public function getProductsByFlashSale(Request $request): AnonymousResourceCollection
    {
        $request->validate(['slug' => 'required|string']);
        $limit = is_numeric($request->limit) ? (int) $request->limit : 10;
        $language = is_string($request->language) ? $request->language : (is_string(config('shop.default_language')) ? config('shop.default_language') : 'id');
        $slug = is_string($request->slug) ? $request->slug : '';
        $products = $this->flashSaleQueryService->getProductsByFlashSaleSlug($slug, (string) $language, $limit);

        return ProductResource::collection($products);
    }

    public function getFlashSaleInfoByProductID(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer|exists:products,id']);
        $id = is_numeric($request->id) ? (int) $request->id : 0;
        $info = $this->flashSaleQueryService->getFlashSaleInfoByProductId($id);

        return response()->json($info);
    }
}
