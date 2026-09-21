<?php

namespace App\Modules\Tax\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Tax;
use App\Modules\Tax\Actions\CreateTaxAction;
use App\Modules\Tax\Actions\DeleteTaxAction;
use App\Modules\Tax\Actions\UpdateTaxAction;
use App\Modules\Tax\DTO\TaxData;
use App\Modules\Tax\Http\Requests\TaxCreateRequest;
use App\Modules\Tax\Http\Requests\TaxUpdateRequest;
use App\Modules\Tax\Http\Resources\TaxResource;
use App\Modules\Tax\Services\TaxQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxController extends BaseController
{
    public function __construct(
        private readonly TaxQueryService $queryService,
        private readonly CreateTaxAction $createAction,
        private readonly UpdateTaxAction $updateAction,
        private readonly DeleteTaxAction $deleteAction,
    ) {}

    public function index(): JsonResponse
    {
        $taxes = $this->queryService->getAll();

        return $this->sendSuccess(TaxResource::collection($taxes), 'Taxes retrieved');
    }

    public function store(TaxCreateRequest $request): JsonResponse
    {
        $this->authorize('create', Tax::class);

        $data = TaxData::fromRequest($request->validated());
        $tax = $this->createAction->execute($data);

        return $this->sendSuccess(new TaxResource($tax), 'Tax created', 201);
    }

    public function show(int|string $id): JsonResponse
    {
        $tax = $this->queryService->findOrFail((int) $id);

        return $this->sendSuccess(new TaxResource($tax), 'Tax detail');
    }

    public function update(TaxUpdateRequest $request, int|string $id): JsonResponse
    {
        $tax = $this->queryService->findOrFail((int) $id);
        $this->authorize('update', $tax);

        $data = TaxData::fromRequest($request->validated());
        $updated = $this->updateAction->execute($tax, $data);

        return $this->sendSuccess(new TaxResource($updated), 'Tax updated');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $tax = $this->queryService->findOrFail((int) $id);
        $this->authorize('delete', $tax);

        $this->deleteAction->execute($tax);

        return $this->sendSuccess(null, 'Tax deleted');
    }
}
