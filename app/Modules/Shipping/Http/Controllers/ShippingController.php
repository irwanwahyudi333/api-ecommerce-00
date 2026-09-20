<?php

namespace App\Modules\Shipping\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Shipping;
use App\Modules\Shipping\Actions\CreateShippingAction;
use App\Modules\Shipping\Actions\DeleteShippingAction;
use App\Modules\Shipping\Actions\UpdateShippingAction;
use App\Modules\Shipping\DTO\ShippingData;
use App\Modules\Shipping\Http\Requests\ShippingCreateRequest;
use App\Modules\Shipping\Http\Requests\ShippingUpdateRequest;
use App\Modules\Shipping\Http\Resources\ShippingResource;
use App\Modules\Shipping\Services\ShippingQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends BaseController
{
    public function __construct(
        private readonly ShippingQueryService $queryService,
        private readonly CreateShippingAction $createAction,
        private readonly UpdateShippingAction $updateAction,
        private readonly DeleteShippingAction $deleteAction,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Shipping::class);

        $shippings = $this->queryService->getAll();

        return $this->sendSuccess(ShippingResource::collection($shippings), 'Shippings retrieved');
    }

    public function store(ShippingCreateRequest $request): JsonResponse
    {
        $this->authorize('create', Shipping::class);

        $data = ShippingData::fromRequest($request->validated());
        $shipping = $this->createAction->execute($data);

        return $this->sendSuccess(new ShippingResource($shipping), 'Shipping created', 201);
    }

    public function show(int $id): JsonResponse
    {
        $shipping = $this->queryService->findOrFail($id);
        $this->authorize('view', $shipping);

        return $this->sendSuccess(new ShippingResource($shipping), 'Shipping detail');
    }

    public function update(ShippingUpdateRequest $request, int $id): JsonResponse
    {
        $shipping = $this->queryService->findOrFail($id);
        $this->authorize('update', $shipping);

        $data = ShippingData::fromRequest($request->validated());
        $updated = $this->updateAction->execute($shipping, $data);

        return $this->sendSuccess(new ShippingResource($updated), 'Shipping updated');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $shipping = $this->queryService->findOrFail($id);
        $this->authorize('delete', $shipping);

        $this->deleteAction->execute($shipping);

        return $this->sendSuccess(null, 'Shipping deleted');
    }
}
