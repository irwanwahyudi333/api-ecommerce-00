<?php

declare(strict_types=1);

namespace App\Modules\RefundReason\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Models\RefundReason;
use App\Modules\RefundReason\Actions\CreateRefundReasonAction;
use App\Modules\RefundReason\Actions\DeleteRefundReasonAction;
use App\Modules\RefundReason\Actions\UpdateRefundReasonAction;
use App\Modules\RefundReason\DTO\RefundReasonData;
use App\Modules\RefundReason\Http\Requests\RefundReasonCreateRequest;
use App\Modules\RefundReason\Http\Requests\RefundReasonUpdateRequest;
use App\Modules\RefundReason\Http\Resources\RefundReasonResource;
use App\Modules\RefundReason\Services\RefundReasonQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundReasonController extends BaseController
{
    public function __construct(
        private readonly RefundReasonQueryService $queryService,
        private readonly CreateRefundReasonAction $createAction,
        private readonly UpdateRefundReasonAction $updateAction,
        private readonly DeleteRefundReasonAction $deleteAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $rawLanguage = $request->input('language');
        $defaultLang = config('shop.default_language', 'id');
        $language = is_string($rawLanguage) ? $rawLanguage : (is_string($defaultLang) ? $defaultLang : 'id');

        $rawLimit = $request->input('limit', 15);
        $limit = is_numeric($rawLimit) ? (int) $rawLimit : 15;

        $reasons = $this->queryService->getRefundReasons($language, $limit);

        return $this->sendPaginated(
            $reasons,
            RefundReasonResource::collection($reasons->getCollection()),
            'Daftar alasan pengembalian berhasil diambil.'
        );
    }

    public function store(RefundReasonCreateRequest $request): JsonResponse
    {
        $this->authorize('create', RefundReason::class);

        $data = RefundReasonData::fromRequest($request->validated());
        $reason = $this->createAction->execute($data);

        return $this->sendSuccess(new RefundReasonResource($reason), 'Refund reason created', 201);
    }

    public function show(Request $request, string $params): JsonResponse
    {
        $rawLanguage = $request->input('language');
        $defaultLang = config('shop.default_language', 'id');
        $language = is_string($rawLanguage) ? $rawLanguage : (is_string($defaultLang) ? $defaultLang : 'id');

        $reason = $this->queryService->find($params, $language);

        return $this->sendSuccess(new RefundReasonResource($reason), 'Refund reason detail');
    }

    public function update(RefundReasonUpdateRequest $request, int|string $id): JsonResponse
    {
        $reason = $this->queryService->findOrFail((int) $id);
        $this->authorize('update', $reason);

        $data = RefundReasonData::fromRequest($request->validated());
        $updated = $this->updateAction->execute($reason, $data);

        return $this->sendSuccess(new RefundReasonResource($updated), 'Refund reason updated');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $reason = $this->queryService->findOrFail((int) $id);
        $this->authorize('delete', $reason);

        $this->deleteAction->execute($reason);

        return $this->sendSuccess(null, 'Refund reason deleted');
    }
}
