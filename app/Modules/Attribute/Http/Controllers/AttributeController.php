<?php

declare(strict_types=1);

namespace App\Modules\Attribute\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\Attribute;
use App\Modules\Attribute\DTO\AttributeData;
use App\Modules\Attribute\Http\Requests\AttributeRequest;
use App\Modules\Attribute\Http\Resources\AttributeResource;
use App\Modules\Attribute\Services\AttributeQueryService;
use App\Modules\Attribute\Services\AttributeWriteService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Cache;

class AttributeController extends BaseController
{
    public function __construct(
        private readonly AttributeQueryService $attributeQueryService,
        private readonly AttributeWriteService $attributeWriteService
    ) {}

    /**
     * GET /attributes - List attributes
     */
    public function index(Request $request): JsonResponse
    {
        $language = $request->language ?? config('shop.default_language', 'id');
        $languageStr = is_scalar($language) ? (string) $language : '';
        $cacheKey = "attributes_{$languageStr}";
        $attributes = Cache::remember($cacheKey, 3600, function () use ($language) {
            return $this->attributeQueryService->getAttributesByLanguage(is_scalar($language) ? (string) $language : '');
        });

        return $this->sendSuccess(
            AttributeResource::collection($attributes),
            'Attributes retrieved'
        );
    }

    /**
     * POST /attributes - Create attribute
     */
    public function store(AttributeRequest $request): JsonResponse
    {
        $this->authorize('create', Attribute::class);

        $data = AttributeData::fromRequest($request->validated());
        $attribute = $this->attributeWriteService->createAttribute($data);
        Cache::forget("attributes_{$data->language}");

        return $this->sendSuccess(
            new AttributeResource($attribute),
            'Attribute created',
            201
        );
    }

    /**
     * GET /attributes/{identifier} - Get attribute by ID or slug
     */
    public function show(Request $request, string $identifier): JsonResponse
    {
        $language = $request->language ?? config('shop.default_language', 'id');
        $attribute = $this->attributeQueryService->getAttributeByIdOrSlug($identifier, is_scalar($language) ? (string) $language : '');

        return $this->sendSuccess(
            new AttributeResource($attribute),
            'Attribute detail'
        );
    }

    /**
     * PUT /attributes/{id} - Update attribute
     */
    public function update(AttributeRequest $request, int $id): JsonResponse
    {
        $attribute = $this->attributeQueryService->getAttributeByIdOrSlug((string) $id, is_scalar($request->language ?? config('shop.default_language', 'id')) ? (string) ($request->language ?? config('shop.default_language', 'id')) : '');
        $this->authorize('update', $attribute);
        $data = AttributeData::fromRequest($request->validated());
        $updated = $this->attributeWriteService->updateAttribute($attribute, $data);
        Cache::forget("attributes_{$data->language}");

        return $this->sendSuccess(
            new AttributeResource($updated),
            'Attribute updated'
        );
    }

    /**
     * DELETE /attributes/{id} - Delete attribute
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $attribute = $this->attributeQueryService->getAttributeByIdOrSlug((string) $id, is_scalar($request->language ?? config('shop.default_language', 'id')) ? (string) ($request->language ?? config('shop.default_language', 'id')) : '');
        $this->authorize('delete', $attribute);

        $language = $attribute->language;
        $this->attributeWriteService->deleteAttribute($attribute);
        Cache::forget("attributes_{$language}");

        return $this->sendSuccess(null, 'Attribute deleted');
    }

    /**
     * GET /attributes/export/{shopId} - Export attributes as CSV
     */
    public function exportAttributes(Request $request, int $shopId): StreamedResponse
    {
        $this->authorize('export', [Attribute::class, $shopId]);

        $user = $request->user();
        if (!$user) { return response()->stream(fn()=>null, 401); }
        $list = $this->attributeQueryService->exportAttributes($shopId, $user);
        $filename = 'attributes-for-shop-id-'.$shopId.'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($list) {
            $handle = fopen('php://output', 'w');
            if ($handle !== false) {
                if (! empty($list)) {
                    fputcsv($handle, array_keys($list[0]));
                    foreach ($list as $row) {
                        /** @var array<int|string, bool|float|int|string|null> $row */
                        fputcsv($handle, $row);
                    }
                }
                fclose($handle);
            }
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * POST /attributes/import - Import attributes from CSV
     */
    public function importAttributes(Request $request): JsonResponse
    {
        $this->authorize('import', [Attribute::class, $request->shop_id]);

        $requestFile = $request->file('csv');
        if (! $requestFile instanceof \Illuminate\Http\UploadedFile) {
            return $this->sendError('CSV file is required', 422);
        }

        try {
            // Ambil shop_id dan user dari request
            $shopId = (int) (is_scalar($request->shop_id) ? $request->shop_id : 0);
            /** @var \App\Models\User|null $user */
            $user = $request->user();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }

            $this->attributeWriteService->importAttributes($requestFile, $shopId, $user);
            
            $defaultLang = config('shop.default_language', 'id');
            $defaultLangStr = is_scalar($defaultLang) ? (string) $defaultLang : 'id';
            Cache::forget('attributes_'.$defaultLangStr);

            return $this->sendSuccess(null, 'Import successful');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 400);
        }
    }


}
