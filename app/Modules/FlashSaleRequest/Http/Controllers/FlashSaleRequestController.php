<?php

declare(strict_types=1);

namespace App\Modules\FlashSaleRequest\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\FlashSaleRequest;
use App\Modules\FlashSaleRequest\Actions\ApproveFlashSaleRequestAction;
use App\Modules\FlashSaleRequest\Actions\DeleteFlashSaleRequestAction;
use App\Modules\FlashSaleRequest\Actions\DisapproveFlashSaleRequestAction;
use App\Modules\FlashSaleRequest\DTO\FlashSaleRequestData;
use App\Modules\FlashSaleRequest\Http\Requests\FlashSaleRequestCreateRequest;
use App\Modules\FlashSaleRequest\Http\Requests\FlashSaleRequestUpdateRequest;
use App\Modules\FlashSaleRequest\Http\Resources\FlashSaleRequestResource;
use App\Modules\FlashSaleRequest\Services\FlashSaleRequestQueryService;
use App\Modules\FlashSaleRequest\Services\FlashSaleRequestWriteService;
use App\Modules\Product\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FlashSaleRequestController extends BaseController
{
    public function __construct(
        private readonly FlashSaleRequestQueryService $queryService,
        private readonly FlashSaleRequestWriteService $writeService,
        private readonly DeleteFlashSaleRequestAction $deleteAction,
        private readonly ApproveFlashSaleRequestAction $approveAction,
        private readonly DisapproveFlashSaleRequestAction $disapproveAction,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', FlashSaleRequest::class);
        $limit = is_numeric($request->limit) ? (int) $request->limit : 10;
        $requests = $this->queryService->getRequestsQuery($request)->paginate($limit);

        return FlashSaleRequestResource::collection($requests);
    }

    public function store(FlashSaleRequestCreateRequest $request): FlashSaleRequestResource
    {
        $this->authorize('create', FlashSaleRequest::class);
        $language = is_string($request->language) ? $request->language : null;
        $data = FlashSaleRequestData::fromRequest($request->validated(), $language);
        $flashSaleRequest = $this->writeService->create($data);

        return new FlashSaleRequestResource($flashSaleRequest);
    }

    public function show(Request $request, int $id): FlashSaleRequestResource
    {
        $flashSaleRequest = $this->queryService->findOrFail($id);
        $this->authorize('view', $flashSaleRequest);

        return new FlashSaleRequestResource($flashSaleRequest);
    }

    public function update(FlashSaleRequestUpdateRequest $request, int $id): FlashSaleRequestResource
    {
        $flashSaleRequest = $this->queryService->findOrFail($id);
        $this->authorize('update', $flashSaleRequest);
        $language = is_string($request->language) ? $request->language : null;
        $data = FlashSaleRequestData::fromRequest($request->validated(), $language);
        $updated = $this->writeService->update($flashSaleRequest, $data);

        return new FlashSaleRequestResource($updated);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $flashSaleRequest = $this->queryService->findOrFail($id);
        $this->authorize('delete', $flashSaleRequest);
        $this->deleteAction->execute($flashSaleRequest);

        return $this->sendSuccess(null, 'Flash sale request deleted successfully');
    }

    public function approveFlashSaleProductsRequest(Request $request): JsonResponse
    {
        $this->authorize('approve', FlashSaleRequest::class);
        $request->validate(['id' => 'required|exists:flash_sale_requests,id']);
        $id = is_numeric($request->id) ? (int) $request->id : 0;
        $this->approveAction->execute($id);

        return $this->sendSuccess(null, 'Request approved successfully');
    }

    public function disapproveFlashSaleProductsRequest(Request $request): JsonResponse
    {
        $this->authorize('disapprove', FlashSaleRequest::class);
        $request->validate(['id' => 'required|exists:flash_sale_requests,id']);
        $id = is_numeric($request->id) ? (int) $request->id : 0;
        $this->disapproveAction->execute($id);

        return $this->sendSuccess(null, 'Request disapproved successfully');
    }

    public function getRequestedProductsForFlashSale(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', FlashSaleRequest::class);
        $request->validate(['vendor_request_id' => 'required|exists:flash_sale_requests,id']);
        $limit = is_numeric($request->limit) ? (int) $request->limit : 10;
        $vendorRequestId = is_numeric($request->vendor_request_id) ? (int) $request->vendor_request_id : 0;
        $products = $this->queryService->getRequestedProductsQuery($request, $vendorRequestId)->paginate($limit);

        return ProductResource::collection($products);
    }
}
