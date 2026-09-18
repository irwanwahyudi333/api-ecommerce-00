<?php

declare(strict_types=1);

namespace App\Modules\DeliveryTime\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\DeliveryTime;
use App\Modules\DeliveryTime\DTO\DeliveryTimeData;
use App\Modules\DeliveryTime\Http\Requests\DeliveryTimeRequest;
use App\Modules\DeliveryTime\Http\Resources\DeliveryTimeResource;
use App\Modules\DeliveryTime\Services\DeliveryTimeQueryService;
use App\Modules\DeliveryTime\Services\DeliveryTimeWriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DeliveryTimeController extends BaseController
{
    public function __construct(
        private readonly DeliveryTimeQueryService $deliveryTimeQueryService,
        private readonly DeliveryTimeWriteService $deliveryTimeWriteService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $langParam = $request->language;
        $defaultLang = config('shop.default_language', 'id');
        $language = is_string($langParam) ? $langParam : (is_string($defaultLang) ? $defaultLang : 'id');
        $cacheKey = "delivery_times_{$language}";
        $deliveryTimes = Cache::rememberForever($cacheKey, function () use ($language) {
            return $this->deliveryTimeQueryService->getAll($language);
        });

        return $this->sendSuccess(
            DeliveryTimeResource::collection($deliveryTimes),
            'Delivery times retrieved'
        );
    }

    public function store(DeliveryTimeRequest $request): JsonResponse
    {
        $this->authorize('create', DeliveryTime::class);

        $data = DeliveryTimeData::fromRequest($request->validated());
        $deliveryTime = $this->deliveryTimeWriteService->create($data);
        Cache::forget("delivery_times_{$data->language}");

        return $this->sendSuccess(
            new DeliveryTimeResource($deliveryTime),
            'Delivery time created',
            201
        );
    }

    public function show(Request $request, string $params): JsonResponse
    {
        $langParam = $request->language;
        $defaultLang = config('shop.default_language', 'id');
        $language = is_string($langParam) ? $langParam : (is_string($defaultLang) ? $defaultLang : 'id');
        $deliveryTime = $this->deliveryTimeQueryService->find($params, $language);

        return $this->sendSuccess(
            new DeliveryTimeResource($deliveryTime),
            'Delivery time detail'
        );
    }

    public function update(DeliveryTimeRequest $request, int $id): JsonResponse
    {
        $deliveryTime = DeliveryTime::findOrFail($id);
        $this->authorize('update', $deliveryTime);

        $data = DeliveryTimeData::fromRequest($request->validated());
        $updated = $this->deliveryTimeWriteService->update($deliveryTime, $data);
        Cache::forget("delivery_times_{$data->language}");

        return $this->sendSuccess(
            new DeliveryTimeResource($updated),
            'Delivery time updated'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $deliveryTime = DeliveryTime::findOrFail($id);
        $this->authorize('delete', $deliveryTime);

        $language = $deliveryTime->language;
        $this->deliveryTimeWriteService->delete($deliveryTime);
        Cache::forget("delivery_times_{$language}");

        return $this->sendSuccess(null, 'Delivery time deleted');
    }
}
