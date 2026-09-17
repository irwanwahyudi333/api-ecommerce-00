<?php

declare(strict_types=1);

namespace App\Modules\AttributeValue\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\AttributeValue;
use App\Modules\AttributeValue\DTO\AttributeValueData;
use App\Modules\AttributeValue\Http\Requests\AttributeValueRequest;
use App\Modules\AttributeValue\Http\Resources\AttributeValueResource;
use App\Modules\AttributeValue\Services\AttributeValueQueryService;
use App\Modules\AttributeValue\Services\AttributeValueWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttributeValueController extends BaseController
{
    public function __construct(
        private readonly AttributeValueQueryService $attributeValueQueryService,
        private readonly AttributeValueWriteService $attributeValueWriteService
    ) {}

    public function index(): JsonResponse
    {
        $values = $this->attributeValueQueryService->getAllAttributeValues();

        return $this->sendSuccess(
            AttributeValueResource::collection($values),
            'Attribute values retrieved'
        );
    }

    public function store(AttributeValueRequest $request): JsonResponse
    {
        $this->authorize('create', [AttributeValue::class, $request->shop_id]);

        $data = AttributeValueData::fromRequest($request->validated());
        $value = $this->attributeValueWriteService->createAttributeValue($data);

        return $this->sendSuccess(
            new AttributeValueResource($value),
            'Attribute value created',
            201
        );
    }

    public function show(int $id): JsonResponse
    {
        $value = $this->attributeValueQueryService->getAttributeValueById($id);

        return $this->sendSuccess(
            new AttributeValueResource($value),
            'Attribute value detail'
        );
    }

    public function update(AttributeValueRequest $request, int $id): JsonResponse
    {
        $value = $this->attributeValueQueryService->getAttributeValueById($id);
        $this->authorize('update', $value);

        $data = AttributeValueData::fromRequest($request->validated());
        $updated = $this->attributeValueWriteService->updateAttributeValue($value, $data);

        return $this->sendSuccess(
            new AttributeValueResource($updated),
            'Attribute value updated'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $value = $this->attributeValueQueryService->getAttributeValueById($id);
        $this->authorize('delete', $value);

        $this->attributeValueWriteService->deleteAttributeValue($value);

        return $this->sendSuccess(null, 'Attribute value deleted');
    }
}
